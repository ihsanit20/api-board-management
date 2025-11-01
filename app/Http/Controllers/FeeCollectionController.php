<?php

namespace App\Http\Controllers;

use App\Models\CollectFee;
use App\Models\Student;
use App\Models\Application;
use App\Models\Institute;
use App\Models\Exam;
use App\Models\PaymentIntent;   // ✅ NEW
use App\Models\FeePayment;      // ✅ NEW
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;      // ✅ NEW
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Msilabs\Bkash\BkashPayment;

class FeeCollectionController extends Controller
{
    use BkashPayment;

    /* =========================
     * Listing / Details (unchanged)
     * ========================= */
    public function index(Request $request)
    {
        $query = CollectFee::with([
            'exam:id,name',
            'institute:id,name,institute_code',
            'zamat:id,name'
        ]);

        $query->where(function ($subQuery) {
            $subQuery->where('payment_method', '!=', 'online')
                ->orWhere(function ($sub) {
                    $sub->where('payment_method', 'online')
                        ->whereNotNull('transaction_id');
                });
        });

        // Latest exam as default
        $selectedExamId = null;
        if ($request->filled('exam_id') && $request->exam_id !== 'all') {
            $selectedExamId = (int) $request->exam_id;
        } else {
            $selectedExamId = Exam::query()->latest('id')->value('id');
        }
        if ($selectedExamId) {
            $query->where('exam_id', $selectedExamId);
        }

        if ($request->filled('institute_code')) {
            $query->whereHas('institute', function ($q) use ($request) {
                $q->where('institute_code', $request->institute_code);
            });
        }

        if ($request->filled('zamat_id')) {
            $query->where('zamat_id', $request->zamat_id);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $from = Carbon::parse($request->date_from)->startOfDay();
            $to   = Carbon::parse($request->date_to)->endOfDay();
            $query->whereBetween('created_at', [$from, $to]);
        }

        if ($request->filled('transaction_id')) {
            $query->where('transaction_id', $request->transaction_id);
        }

        $feeCollections = $query->orderByDesc('created_at')->get();

        return response()->json([
            'message' => 'Fee collection list retrieved successfully.',
            'selected_exam_id' => $selectedExamId,
            'data' => $feeCollections,
        ], 200);
    }

    public function show($id)
    {
        try {
            $feeCollection = CollectFee::with([
                'institute:id,name,institute_code,phone',
                'zamat:id,name',
                'exam:id,name'
            ])->findOrFail($id);

            $studentIds = $feeCollection->student_ids ?? [];
            $students = [];
            if (!empty($studentIds)) {
                $students = Student::whereIn('id', $studentIds)
                    ->get(['id', 'name', 'roll_number', 'registration_number']);
            }

            return response()->json([
                'message' => 'Fee collection details retrieved successfully.',
                'data' => [
                    'feeCollection' => $feeCollection,
                    'students' => $students
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve fee collection details.',
                'error'   => $e->getMessage(),
            ], 404);
        }
    }

    /* =========================
     * Create (Online → Intent) / (Offline → Finalize)
     * ========================= */
    public function store(Request $request)
    {
        // Online = registrations প্রয়োজন; Offline = student_ids বা registrations
        $request->validate([
            'payment_method'  => 'required|in:online,offline',
            'total_amount'    => 'required|numeric',
            'exam_id'         => 'required|exists:exams,id',
            'zamat_id'        => 'required|exists:zamats,id',

            // institute resolve: id OR code (যেকোনো একটি)
            'institute_id'    => 'required_without:institute_code|nullable|exists:institutes,id',
            'institute_code'  => 'required_without:institute_id|nullable|exists:institutes,institute_code',

            // দুটি ইনপুট চ্যানেল
            'student_ids'     => 'sometimes|array',
            'student_ids.*'   => 'integer|exists:students,id',

            'registrations'   => 'sometimes|array',
            'registrations.*' => 'integer',

            'transaction_id'  => 'nullable|string',     // offline/cash রসিদ/চেক নম্বর ইত্যাদি
            'method'          => 'sometimes|string|in:bkash,nagad,card,cash,bank,other', // offline method override
        ]);

        $examId  = (int) $request->exam_id;
        $zamatId = (int) $request->zamat_id;

        // resolve institute_id from code if not provided
        $instituteId = $request->institute_id;
        if (!$instituteId && $request->filled('institute_code')) {
            $instituteId = Institute::where('institute_code', $request->institute_code)->value('id');
            if (!$instituteId) {
                abort(422, 'Invalid institute_code.');
            }
        }
        $instituteId = (int) $instituteId;

        $registrations = array_map('intval', $request->registrations ?? []);

        /* ---------- ONLINE: create intent (no DB side-effects yet) ---------- */
        if ($request->payment_method === 'online') {
            if (empty($registrations)) {
                abort(422, 'Online payment requires registrations array.');
            }

            // durable pending intent
            $intent = PaymentIntent::create([
                'token'           => Str::uuid()->toString(),
                'exam_id'         => $examId,
                'institute_id'    => $instituteId,
                'zamat_id'        => $zamatId,
                'expected_amount' => (float) $request->total_amount,
                'registrations'   => $registrations,
                'status'          => 'initiated',
                'expires_at'      => now()->addMinutes(60),
            ]);

            $callback_url = env('FRONTEND_BASE_URL', 'https://tanjim.madrasah.cc') . "/bkash/callback/intent/{$intent->token}";
            $response = $this->createPayment($intent->expected_amount, $intent->token, $callback_url);

            return response()->json([
                'message'  => 'Payment is in progress. Redirecting to payment gateway...',
                'response' => $response,
                'success'  => !!(data_get($response, 'bkashURL')),
                'bkashURL' => data_get($response, 'bkashURL', '#'),
                // FE চাইলে intent token স্টোর করতে পারে
                'token'    => $intent->token,
            ], 201);
        }

        /* ---------- OFFLINE: finalize immediately ---------- */
        return DB::transaction(function () use ($request, $examId, $zamatId, $instituteId, $registrations) {
            $studentIds = [];

            if (!empty($request->student_ids)) {
                $studentIds = array_map('intval', $request->student_ids);
            } else {
                if (empty($registrations)) {
                    abort(422, 'Offline payment requires student_ids or registrations.');
                }
                $studentIds = $this->upsertStudentsFromRegistrations($registrations, $examId, $zamatId, $instituteId);
            }

            // roll assign first
            if (!empty($studentIds)) {
                $this->assignRollNumbers($studentIds);
            }

            // Ledger row
            $feePayment = FeePayment::create([
                'exam_id'        => $examId,
                'institute_id'   => $instituteId,
                'zamat_id'       => $zamatId,
                'channel'        => 'offline',
                'method'         => $request->input('method', 'cash'),
                'status'         => 'Completed',
                'gross_amount'   => (float) $request->total_amount,
                'net_amount'     => null,
                'service_charge' => 0,
                'students_count' => count($studentIds),
                'trx_id'         => null,
                'payment_id'     => null,
                'payer_msisdn'   => null,
                'paid_at'        => now(),
                'user_id'        => Auth::id(),
                'meta'           => null,
            ]);

            // Receipt row (existing table)
            $collect = CollectFee::create([
                'student_ids'    => $studentIds,
                'registrations'  => null,
                'total_amount'   => (float) $request->total_amount,
                'payment_method' => 'offline',
                'transaction_id' => $request->transaction_id,
                'exam_id'        => $examId,
                'institute_id'   => $instituteId,
                'zamat_id'       => $zamatId,
            ]);

            return response()->json([
                'message' => 'Fee collected successfully and roll numbers assigned.',
                'data'    => [
                    'fee_payment_id'  => $feePayment->id,
                    'collect_fee_id'  => $collect->id,
                    'student_ids'     => $studentIds,
                ],
            ], 201);
        });
    }

    /* =========================
     * bKash Execute (Success path → finalize)
     * Route param should be {token}
     * ========================= */
    public function bkashExecutePayment(string $token, Request $request)
    {
        $paymentID = $request->input('paymentID');
        if (!$paymentID) {
            return response()->json([
                'message' => 'Invalid payment ID or status.',
                'status'  => false,
            ], 200);
        }

        $response = $this->executePayment($paymentID);
        if (!($response && (data_get($response, 'transactionStatus') === 'Completed'))) {
            return response()->json([
                'message' => 'Payment failed. Please try again.',
                'status'  => false,
            ], 200);
        }

        // SUCCESS: intent → verify + students upsert + roll + fee_payments + collect_fees
        return DB::transaction(function () use ($token, $response, $paymentID) {
            /** @var PaymentIntent $intent */
            $intent = PaymentIntent::lockForUpdate()
                ->where('token', $token)
                ->firstOrFail();

            if ($intent->status !== 'initiated') {
                // idempotent return
                return response()->json([
                    'message' => 'Payment already processed.',
                    'status'  => true,
                ], 201);
            }

            $amount = (float) (data_get($response, 'amount') ?? $intent->expected_amount);
            if ($amount + 0.0001 < (float) $intent->expected_amount) {
                // Optional: strict amount check (allow >= expected)
                throw new \Exception('Paid amount is less than expected.');
            }

            // Normalize gateway fields
            $trxId      = data_get($response, 'trxID') ?? data_get($response, 'trxId') ?? data_get($response, 'transactionId');
            $payer      = data_get($response, 'customerMsisdn') ?? data_get($response, 'payerMSISDN') ?? data_get($response, 'msisdn') ?? null;
            $paidAtRaw  = data_get($response, 'completedTime') ?? data_get($response, 'paymentExecuteTime') ?? data_get($response, 'updateTime');
            try {
                $paidAt = $paidAtRaw ? Carbon::parse($paidAtRaw) : now();
            } catch (\Throwable $e) {
                $paidAt = now();
            }

            $regs = $intent->registrations ?? [];
            if (empty($regs)) {
                throw new \Exception('No registrations found on intent.');
            }

            // From applications → upsert students
            $studentIds = $this->upsertStudentsFromRegistrations(
                $regs,
                (int) $intent->exam_id,
                (int) $intent->zamat_id,
                (int) $intent->institute_id
            );

            // Assign roll numbers
            if (!empty($studentIds)) {
                $this->assignRollNumbers($studentIds);
            }

            // Ledger row (fee_payments)
            $feePayment = FeePayment::create([
                'exam_id'        => (int) $intent->exam_id,
                'institute_id'   => (int) $intent->institute_id,
                'zamat_id'       => (int) $intent->zamat_id,
                'channel'        => 'online',
                'method'         => 'bkash',
                'status'         => 'Completed',
                'gross_amount'   => $amount,
                'net_amount'     => null,   // ইচ্ছা হলে সেটেল্ড অ্যামাউন্ট বসান
                'service_charge' => 0,      // প্রয়োজনে নিয়ম অনুযায়ী হিসাব
                'students_count' => count($studentIds),
                'trx_id'         => $trxId,
                'payment_id'     => $paymentID,
                'payer_msisdn'   => $payer ? substr(preg_replace('/\D+/', '', (string) $payer), 0, 30) : null,
                'paid_at'        => $paidAt,
                'user_id'        => null,
                'meta'           => json_decode(json_encode($response), true),
            ]);

            // Receipt row (existing table)
            $collect = CollectFee::create([
                'student_ids'    => $studentIds,
                'registrations'  => null,
                'total_amount'   => $intent->expected_amount,
                'payment_method' => 'online',
                'transaction_id' => $trxId,
                'exam_id'        => (int) $intent->exam_id,
                'institute_id'   => (int) $intent->institute_id,
                'zamat_id'       => (int) $intent->zamat_id,
            ]);

            // Mark intent completed
            $intent->update([
                'status'         => 'completed',
                'transaction_id' => $trxId,
                'meta'           => json_decode(json_encode($response), true),
            ]);

            // Optional SMS (যদি আগে ব্যবহার করেন)
            // if (method_exists($this, 'sendSmsWithStore')) { ... }

            return response()->json([
                'message' => 'Payment successful. Fee collected and students created with roll numbers.',
                'status'  => true,
                'data'    => [
                    'fee_payment_id' => $feePayment->id,
                    'collect_fee_id' => $collect->id,
                    'student_ids'    => $studentIds,
                ],
            ], 201);
        });
    }

    /* =========================
     * Helper: registrations → students upsert
     * ========================= */
    private function upsertStudentsFromRegistrations(array $registrations, int $examId, int $zamatId, int $instituteId): array
    {
        $regs = array_values(array_unique(array_map('intval', $registrations)));
        if (empty($regs)) return [];

        $apps = Application::query()
            ->where('exam_id', $examId)
            ->where('zamat_id', $zamatId)
            ->where('institute_id', $instituteId)
            ->where('payment_status', 'Paid')
            ->get(['id', 'students', 'group_id', 'area_id', 'center_id']);

        // reg → payload map
        $regMap = [];
        foreach ($apps as $app) {
            foreach (($app->students ?? []) as $s) {
                if (!empty($s['registration'])) {
                    $reg = (int) $s['registration'];
                    $regMap[$reg] = $s + [
                        'application_id' => $app->id,
                        'group_id'       => $app->group_id,
                        'area_id'        => $app->area_id,
                        'center_id'      => $app->center_id,
                    ];
                }
            }
        }

        if (empty($regMap)) {
            throw new \Exception('No registered students found under paid applications for this combination.');
        }

        $ids = [];
        foreach ($regs as $reg) {
            $payload = $regMap[$reg] ?? null;
            if (!$payload) {
                throw new \Exception("Registration {$reg} not found under paid applications.");
            }

            $student = Student::firstOrCreate(
                [
                    'exam_id'             => $examId,
                    'zamat_id'            => $zamatId,
                    'institute_id'        => $instituteId,
                    'registration_number' => $reg,
                ],
                [
                    'name'           => (string) ($payload['name'] ?? ''),
                    'father_name'    => (string) ($payload['father_name'] ?? ''),
                    'date_of_birth'  => $payload['date_of_birth'] ?? null,
                    'para'           => isset($payload['para']) && $payload['para'] !== ''
                        ? str_pad((string)$payload['para'], 2, '0', STR_PAD_LEFT)
                        : null,
                    'address'        => $payload['address'] ?? null,
                    'application_id' => $payload['application_id'] ?? null,

                    // 👇 NEW: create সময়েই সেট করুন
                    'group_id'       => $payload['group_id']   ?? null,
                    'area_id'        => $payload['area_id']    ?? null,
                    'center_id'      => $payload['center_id']  ?? null,
                ]
            );

            $ids[] = (int) $student->id;
        }

        return $ids;
    }

    /* =========================
     * Helper: assign roll numbers safely
     * ========================= */
    private function assignRollNumbers(array $studentIds)
    {
        foreach ($studentIds as $studentId) {
            /** @var Student $student */
            $student = Student::lockForUpdate()->find($studentId);
            if (!$student) continue;

            if (!$student->roll_number) {
                $previousMaxRollNumber = Student::query()
                    ->where('exam_id', $student->exam_id)
                    ->where('zamat_id', $student->zamat_id)
                    ->max('roll_number');

                $student->roll_number = $previousMaxRollNumber
                    ? ($previousMaxRollNumber + 1)
                    : ($student->exam_id . $student->zamat_id . "0001");

                $student->save();
            }
        }
    }
}
