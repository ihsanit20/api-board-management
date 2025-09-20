<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\Exam;
use App\Models\Institute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Msilabs\Bkash\BkashPayment;
use App\Models\ApplicationPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ApplicationController extends Controller
{
    use BkashPayment;

    private static $application = null;

    public function index(Request $request)
    {
        $query = Application::query()
            ->with([
                'exam:id,name',
                'zamat:id,name',
                'area:id,name',
                'institute:id,name,institute_code',
                'center',
                'submittedBy:id,name',
                'approvedBy:id,name',
                'group:id,name',
            ]);

        // exam filter (latest by default)
        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->integer('exam_id'));
        } else {
            $latestExam = Exam::latest('id')->first();
            if ($latestExam) {
                $query->where('exam_id', $latestExam->id);
            }
        }

        // other filters
        if ($request->filled('zamat_id')) {
            $query->where('zamat_id', $request->integer('zamat_id'));
        }

        if ($request->filled('institute_code')) {
            $code = trim((string) $request->input('institute_code'));
            $query->whereHas('institute', function ($q) use ($code) {
                $q->where('institute_code', $code);
            });
        }

        if ($request->filled('application_id')) {
            $query->where('id', $request->integer('application_id'));
        }

        /* -------------------------------
     | payment_method filter
     | accepts: payment_method=Online|Offline|all
     | also supports: method=... or method[]=...
     *-------------------------------- */
        $methodParam = $request->input('payment_method', $request->input('method'));
        if ($methodParam && strtolower(is_array($methodParam) ? 'x' : $methodParam) !== 'all') {
            $allowed = ['Online', 'Offline'];

            if (is_array($methodParam)) {
                $values = array_values(array_intersect($methodParam, $allowed));
                if (!empty($values)) {
                    $query->whereIn('payment_method', $values);
                }
            } else {
                $normalized = ucfirst(strtolower($methodParam)); // online -> Online
                if (in_array($normalized, $allowed, true)) {
                    $query->where('payment_method', $normalized);
                }
            }
        }

        /* --------------------------------------------
     | payment_status filter (string or array)
     | supports aliases: success/completed => Paid
     | accepts: status=Paid|Pending|Failed|all
     *-------------------------------------------- */
        $statusParam = $request->input('status');
        if ($statusParam && strtolower(is_array($statusParam) ? 'x' : $statusParam) !== 'all') {
            $allowed = ['Paid', 'Pending', 'Failed'];

            $normalize = function ($s) {
                $s = strtolower(trim((string) $s));
                $aliases = [
                    'paid'      => 'Paid',
                    'success'   => 'Paid',
                    'completed' => 'Paid',
                    'pending'   => 'Pending',
                    'failed'    => 'Failed',
                ];
                return $aliases[$s] ?? ucfirst($s);
            };

            if (is_array($statusParam)) {
                $values = array_map($normalize, $statusParam);
                $values = array_values(array_intersect($values, $allowed));
                if (!empty($values)) {
                    $query->whereIn('payment_status', $values);
                }
            } else {
                $value = $normalize($statusParam);
                if (in_array($value, $allowed, true)) {
                    $query->where('payment_status', $value);
                }
            }
        }

        // pagination
        $perPage = $request->input('per_page', 15);

        if ($perPage === 'all') {
            $applications = $query->latest('id')->get();

            return response()->json([
                'data'         => ApplicationResource::collection($applications),
                'total'        => $applications->count(),
                'per_page'     => $applications->count(),
                'current_page' => 1,
                'last_page'    => 1,
            ]);
        }

        $applications = $query->latest('id')->paginate((int) $perPage);

        ApplicationResource::withoutWrapping();

        return response()->json([
            'data'         => ApplicationResource::collection($applications),
            'total'        => $applications->total(),
            'per_page'     => $applications->perPage(),
            'current_page' => $applications->currentPage(),
            'last_page'    => $applications->lastPage(),
        ]);
    }

    public function printApplications(Request $request)
    {
        $query = Application::query()
            ->with([
                'exam:id,name',
                'zamat:id,name',
                'area:id,name',
                'institute:id,name,institute_code',
                'center:id,name,institute_code',
                'submittedBy:id,name',
                'approvedBy:id,name',
                'group:id,name',
                'students'
            ]);

        // Apply filters based on request parameters
        if ($request->has('zamat_id') && $request->zamat_id) {
            $query->where('zamat_id', $request->zamat_id);
        }

        if ($request->has('institute_code') && $request->institute_code) {
            $query->whereHas('institute', function ($q) use ($request) {
                $q->where('institute_code', $request->institute_code);
            });
        }

        if ($request->has('application_id') && $request->application_id) {
            $query->where('id', $request->application_id);
        }

        // Fetch filtered applications
        $applications = $query->latest('id')->get();

        ApplicationResource::withoutWrapping();

        // Include students in the print view
        $applications = ApplicationResource::collection($applications->map(function ($application) {
            return new ApplicationResource($application, true); // Pass true to include students
        }));

        // JSON response for print
        return response()->json($applications);
    }

    public function getApplicationCounts(Request $request)
    {
        $examId = $request->input('exam_id') ?? Exam::latest('id')->value('id');

        $query = Application::query()->where('exam_id', $examId);

        $totalApplications   = (clone $query)->count();
        $pendingApplications = (clone $query)->where('payment_status', 'Pending')->count();
        $paidApplications    = (clone $query)->where('payment_status', 'Paid')->count();
        $totalStudents       = (clone $query)->selectRaw('SUM(JSON_LENGTH(students)) as total_students')->value('total_students');

        // ✅ নতুন দুইটা কাউন্ট: payment_method ভিত্তিক
        $onlineApplications  = (clone $query)->where('payment_method', 'Online')->count();
        $offlineApplications = (clone $query)->where('payment_method', 'Offline')->count();

        return response()->json([
            'exam_id'             => $examId,
            'totalApplications'   => $totalApplications,
            'pendingApplications' => $pendingApplications,
            'paidApplications'    => $paidApplications,
            'totalStudents'       => (int) $totalStudents,

            // নতুন ফিল্ড
            'onlineApplications'  => $onlineApplications,
            'offlineApplications' => $offlineApplications,
        ]);
    }


    public function getZamatWiseCounts(Request $request)
    {
        $examId = $request->input('exam_id') ?? Exam::latest('id')->value('id');

        $zamatCounts = Application::query()
            ->where('exam_id', $examId)
            ->select('zamat_id')
            ->selectRaw('COUNT(*) as total_applications')
            ->selectRaw('SUM(JSON_LENGTH(students)) as total_students')
            ->groupBy('zamat_id')
            ->with('zamat:id,name')
            ->get();

        $formattedCounts = $zamatCounts->map(function ($item) {
            return [
                'zamat_id' => $item->zamat_id,
                'zamat_name' => $item->zamat->name ?? 'Unknown',
                'total_applications' => $item->total_applications,
                'total_students' => (int) $item->total_students,
            ];
        });

        return response()->json($formattedCounts);
    }

    public function getUserWiseCounts(Request $request)
    {
        $examId = $request->input('exam_id') ?? Exam::latest('id')->value('id');

        $userCounts = Application::query()
            ->where('exam_id', $examId)
            ->select('submitted_by')
            ->selectRaw('COUNT(*) as total_applications')
            ->selectRaw('SUM(JSON_LENGTH(students)) as total_students')
            ->groupBy('submitted_by')
            ->with(['submittedBy:id,name'])
            ->get();

        $formattedCounts = $userCounts->map(function ($item) {
            return [
                'submitted_by' => $item->submitted_by,
                'submitted_by_name' => $item->submittedBy->name ?? 'Unknown',
                'total_applications' => $item->total_applications,
                'total_students' => (int) $item->total_students,
            ];
        });

        return response()->json($formattedCounts);
    }

    public function show($id)
    {
        $application = Application::query()
            ->with([
                'exam:id,name',
                'zamat:id,name',
                'area:id,name',
                'institute:id,name,institute_code',
                'center',
                'submittedBy',
                'approvedBy',
                'group:id,name'
            ])
            ->findOrFail($id);

        return response()->json($application);
    }

    public function publicShow(Request $request)
    {
        $request->validate([
            'application_id' => 'required|exists:applications,id',
            'institute_code' => 'required|exists:institutes,institute_code', // Validate using institute_code
        ]);

        $application = Application::query()
            ->where('id', $request->application_id)
            ->whereHas('institute', function ($query) use ($request) {
                $query->where('institute_code', $request->institute_code); // Search by institute_code
            })
            ->with([
                'exam:id,name',
                'zamat:id,name',
                'area:id,name',
                'institute:id,name,institute_code',
                'center',
                'submittedBy',
                'approvedBy',
                'group:id,name'
            ])
            ->first();

        if (!$application) {
            return response()->json(['message' => 'Application not found or does not belong to the provided institute.'], 404);
        }

        return response()->json($application);
    }

    public function store(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'institute_id' => 'required|exists:institutes,id',
            'zamat_id' => 'required|exists:zamats,id',
            'group_id' => 'nullable|exists:groups,id',
            'area_id' => 'nullable|exists:areas,id',
            'center_id' => 'nullable|exists:institutes,id',

            'students' => 'required|array|min:1',

            'students.*.name' => 'required|string|max:255',
            'students.*.name_arabic' => 'nullable|string|max:255',
            'students.*.father_name' => 'required|string|max:255',
            'students.*.father_name_arabic' => 'nullable|string|max:255',
            'students.*.date_of_birth' => 'required|date|before:today',
            'students.*.para' => 'nullable|integer|exists:para_groups,id',
            'students.*.address' => 'nullable|string|max:255',

            'total_amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string|in:Online,Offline',
        ]);

        try {
            $application = Application::create([
                'exam_id' => $request->exam_id,
                'area_id' => $request->area_id,
                'institute_id' => $request->institute_id,
                'zamat_id' => $request->zamat_id,
                'group_id' => $request->group_id,
                'center_id' => $request->center_id,
                'payment_status' => 'Pending',
                'total_amount' => $request->total_amount, //
                'payment_method' => $request->payment_method ?? 'Offline',
                'submitted_by' => Auth::guard('sanctum')->id() ?? null,
                'application_date' => $request->application_date ?? now(),
                'students' => $request->students,
            ]);

            $application->load('institute');

            return response()->json([
                'message' => 'Application submitted successfully',
                'application' => $application
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to submit application', 'error' => $e->getMessage()], 500);
        }
    }

    public function bkashCreatePayment(Application $application)
    {
        $response = $this->initiateOnlinePayment($application);

        return response()->json([
            'message'   => 'Application submitted successfully. Redirecting to payment gateway...',
            'response'  => $response,
            'success'   => !!($response->bkashURL ?? false),
            'bkashURL'  => $response->bkashURL ?? '#'
        ], 201);
    }

    private function initiateOnlinePayment($application)
    {
        $callback_url = env('FRONTEND_BASE_URL', 'https://tanjim.madrasah.cc') . "/bkash/callback/{$application->id}/{$application->institute_id}";

        try {
            $response = $this->createPayment($application->total_amount, $application->id, $callback_url);

            return $response;
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to initiate payment', 'error' => $e->getMessage()], 500);
        }
    }

    public function bkashExecutePayment(Application $application, Request $request)
    {
        $paymentID = $request->input('paymentID');
        if (!$paymentID) {
            return response()->json(['message' => 'Payment failed! Try Again', 'status' => false], 200);
        }

        // 1) Execute (gateway)
        $response = $this->executePayment($paymentID);

        // bKash Complete না হলে ফেইল বলে ধরা
        if ((data_get($response, 'transactionStatus')) !== 'Completed') {
            return response()->json(['message' => 'Payment failed! Try Again!', 'status' => false], 200);
        }

        // 2) Normalize fields
        $trxId  = data_get($response, 'trxID') ?? data_get($response, 'trxId') ?? data_get($response, 'transactionId');
        $amount = (float) (data_get($response, 'amount') ?? $application->total_amount);

        $msisdnRaw = data_get($response, 'customerMsisdn')
            ?? data_get($response, 'payerMSISDN')
            ?? data_get($response, 'msisdn')
            ?? data_get($response, 'payer.msisdn')
            ?? optional($application->institute)->phone;
        $msisdn = $msisdnRaw ? preg_replace('/\D+/', '', (string) $msisdnRaw) : null;
        $msisdn = $msisdn ? substr($msisdn, 0, 30) : null;

        $paidAtRaw = data_get($response, 'completedTime')
            ?? data_get($response, 'paymentExecuteTime')
            ?? data_get($response, 'updateTime');

        try {
            $paidAt = $paidAtRaw ? Carbon::parse($paidAtRaw) : now();
        } catch (\Throwable $e) {
            $paidAt = now();
        }

        // ----- NEW: students_count প্রস্তুত করুন -----
        // Application::$casts অনুযায়ী students ইতিমধ্যে array হবে; না হলে fallback হিসেবে 0
        $studentsCount = is_array($application->students) ? count($application->students ?? []) : 0;

        // Gateway response কে array করে নিন
        $gatewayMeta = json_decode(json_encode($response), true);

        // ----- NEW: meta merge করে students_count যোগ করুন -----
        $meta = array_merge($gatewayMeta ?? [], [
            'students_count' => $studentsCount,
            // ইচ্ছা করলে উৎসও ট্যাগ করতে পারেন:
            // 'meta_source' => 'bkash_execute',
            // 'application_id' => $application->id,
        ]);

        // 3) Atomically: Payment create/update, তারপর Application-এ link + status
        DB::transaction(function () use ($application, $trxId, $amount, $msisdn, $paidAt, $meta) {
            // (a) Payment create/update (idempotent by trx_id)
            $payment = ApplicationPayment::updateOrCreate(
                ['trx_id' => $trxId], // unique key
                [
                    'exam_id'        => $application->exam_id,
                    'institute_id'   => $application->institute_id,
                    'zamat_id'       => $application->zamat_id,

                    'amount'         => $amount,
                    'payment_method' => 'bkash',
                    'status'         => 'Completed',
                    'payer_msisdn'   => $msisdn,
                    'meta'           => $meta,     // <-- students_count এখন meta-তে আছে
                    'paid_at'        => $paidAt,
                ]
            );

            // (b) Application update + link
            $application->payment()->associate($payment);
            $application->payment_method = 'Online';
            $application->payment_status = 'Paid';
            $application->save();
        });

        return response()->json(['message' => 'Payment success', 'status' => true], 201);
    }

    private function generateRegistrationNumber($exam_id, &$previous_registration_numbers)
    {
        do {
            $rand = rand(10000, 99999);
            $new_registration_number = $exam_id . $rand;
        } while (in_array($new_registration_number, $previous_registration_numbers));

        $previous_registration_numbers[] = $new_registration_number;

        return $new_registration_number;
    }

    public function updateRegistrationPart(Request $request, $id)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'institute_id' => 'required|exists:institutes,id',
            'zamat_id' => 'required|exists:zamats,id',
            'group_id' => 'nullable|exists:groups,id',
            'area_id' => 'nullable|exists:areas,id',
            'center_id' => 'nullable|exists:institutes,id',
        ]);

        try {
            $application = Application::findOrFail($id);
            $application->update([
                'exam_id' => $request->exam_id,
                'institute_id' => $request->institute_id,
                'zamat_id' => $request->zamat_id,
                'group_id' => $request->group_id,
                'area_id' => $request->area_id,
                'center_id' => $request->center_id,
            ]);

            return response()->json([
                'message' => 'Registration information updated successfully',
                'application' => $application
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update registration information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStudentsPart(Request $request, $id)
    {
        $request->validate([
            'students' => 'required|array|min:1',
            'students.*.name' => 'required|string|max:255',
            'students.*.name_arabic' => 'nullable|string|max:255',
            'students.*.father_name' => 'required|string|max:255',
            'students.*.father_name_arabic' => 'nullable|string|max:255',
            'students.*.date_of_birth' => 'required|date|before:today',
            'students.*.para' => 'nullable|integer|exists:para_groups,id',
            'students.*.address' => 'nullable|string|max:255',
        ]);

        try {
            $application = Application::findOrFail($id);
            $application->update([
                'students' => $request->students,
            ]);

            return response()->json([
                'message' => 'Students information updated successfully',
                'application' => $application
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update students information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $application = Application::findOrFail($id);

            if (strtolower($application->payment_status) === 'paid') {
                return response()->json([
                    'message' => 'Paid application cannot be deleted.'
                ], 422);
            }

            $application->delete();

            return response()->json([
                'message' => 'Application deleted successfully.',
                'id'      => (int) $id,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete application.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    public function matchingPayments(Application $application)
    {
        $payments = ApplicationPayment::query()
            ->where('exam_id', $application->exam_id)
            ->where('institute_id', $application->institute_id)
            ->where('zamat_id', $application->zamat_id)
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->with(['user:id,name'])     // to show who created/paid by
            ->take(100)                  // sane limit
            ->get([
                'id',
                'exam_id',
                'institute_id',
                'zamat_id',
                'amount',
                'payment_method',
                'status',
                'trx_id',
                'paid_at',
                'user_id'
            ]);

        return response()->json([
            'data' => $payments,
            'application' => [
                'id' => $application->id,
                'total_amount' => (int) $application->total_amount,
            ],
        ]);
    }

    /**
     * Update application payment_status and (optionally) link payment_id
     * Only link if:
     * - payment matches exam_id + institute_id + zamat_id, AND
     * - payment.status == 'Completed', AND
     * - payment.amount >= application.total_amount
     * If any condition fails => we still update status but ignore linking.
     */
    public function updatePaymentStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'payment_status' => 'required|in:Pending,Paid',
            'payment_id'     => 'nullable|integer',
        ]);

        return DB::transaction(function () use ($id, $validated) {
            $application = Application::lockForUpdate()->findOrFail($id);
            $linkedPaymentId = null;
            $linkReason = null;

            // Try to link payment if provided
            if (!empty($validated['payment_id'])) {
                $payment = ApplicationPayment::find($validated['payment_id']);

                if ($payment) {
                    $matchesCombo = (
                        (int)$payment->exam_id      === (int)$application->exam_id &&
                        (int)$payment->institute_id === (int)$application->institute_id &&
                        (int)$payment->zamat_id     === (int)$application->zamat_id
                    );

                    if (!$matchesCombo) {
                        $linkReason = 'Combo mismatch (exam/institute/zamat)';
                    } elseif ($payment->status !== 'Completed') {
                        $linkReason = 'Payment not Completed';
                    } elseif ((float)$payment->amount < (float)$application->total_amount) {
                        $linkReason = 'Payment amount is less than application amount';
                    } else {
                        // ✅ eligible to link
                        $linkedPaymentId = $payment->id;
                    }
                } else {
                    $linkReason = 'Payment not found';
                }
            }

            // Update application fields
            $application->payment_status = $validated['payment_status'];
            $application->approved_by = Auth::guard('sanctum')->id() ?? $application->approved_by;

            // Only set payment_id if eligible
            if ($linkedPaymentId) {
                $application->payment_id = $linkedPaymentId;
            }

            $application->save();

            return response()->json([
                'message' => 'Payment status updated',
                'linked_payment_id' => $linkedPaymentId,
                'link_skipped_reason' => $linkedPaymentId ? null : ($linkReason ?? null),
            ]);
        });
    }
}
