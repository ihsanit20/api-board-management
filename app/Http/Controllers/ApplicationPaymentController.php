<?php

namespace App\Http\Controllers;

use App\Models\ApplicationPayment;
use App\Models\Exam;
use App\Models\Institute;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ApplicationPaymentController extends Controller
{
    /** ------------------------- Helpers ------------------------- */

    /**
     * খুব ছোট, offline-safe trx_id জেনারেটর।
     * bKash ছাড়া (Bank/Cash/others) কেসে কল হবে।
     * ফরম্যাট: {PFX}-{RANDOM6}  e.g., B-9X2K7Q, C-1J8QZW, O-7M4KQ2
     */
    private function generateShortTrxId(?string $method): string
    {
        // Prefix: Bank=B, Cash=C, Others=O
        $prefix = match ($method) {
            'Bank'         => 'B',
            'Cash Payment' => 'C',
            default        => 'O',
        };

        // খুব ছোট random: 6 chars (A-Z0-9)
        // ইউনিক নিশ্চিতে লুপ (সাধারণত ১বারেই unique হবে)
        do {
            $candidate = $prefix . '-' . Str::upper(Str::random(6));
            $exists = ApplicationPayment::where('trx_id', $candidate)->exists();
        } while ($exists);

        return $candidate;
    }

    private function getDefaultExamId(): ?int
    {
        // আপনি চাইলে অন্য ক্রাইটেরিয়া (e.g., latest by date) ব্যবহার করতে পারেন
        return Exam::query()->max('id');
    }

    /** ------------------------- Index ------------------------- */

    public function index(Request $request)
    {
        $query = ApplicationPayment::query()
            ->with([
                // ✅ many applications (hasMany)
                'applications:id,payment_id,exam_id,institute_id,zamat_id,total_amount,payment_status,payment_method',
                'exam:id,name',
                'institute:id,name,institute_code,phone',
                'zamat:id,name',
                'user:id,name',
            ]);

        // ---- Filters ----
        if ($request->filled('exam_id'))      $query->where('exam_id', (int) $request->exam_id);
        if ($request->filled('institute_id')) $query->where('institute_id', (int) $request->institute_id);
        if ($request->filled('zamat_id'))     $query->where('zamat_id', (int) $request->zamat_id);

        // application_id via whereHas('applications')
        if ($request->filled('application_id')) {
            $query->whereHas('applications', function (Builder $q) use ($request) {
                $q->where('id', (int) $request->application_id);
            });
        }

        // status
        $status = $request->input('status');
        if ($status && strtolower($status) !== 'all') $query->where('status', $status);

        // method
        $method = $request->input('method') ?? $request->input('payment_method');
        if ($method && strtolower($method) !== 'all') $query->where('payment_method', $method);

        // search (trx_id / payer_msisdn / institute / applications fields)
        if ($s = trim((string) $request->input('q', ''))) {
            $query->where(function (Builder $qb) use ($s) {
                $qb->where('trx_id', 'like', "%{$s}%")
                    ->orWhere('payer_msisdn', 'like', "%{$s}%")
                    ->orWhereHas('institute', function (Builder $iq) use ($s) {
                        $iq->where('name', 'like', "%{$s}%")
                            ->orWhere('institute_code', 'like', "%{$s}%")
                            ->orWhere('phone', 'like', "%{$s}%");
                    })
                    ->orWhereHas('applications', function (Builder $aq) use ($s) {
                        $aq->where('payment_status', 'like', "%{$s}%")
                            ->orWhere('payment_method', 'like', "%{$s}%");
                    });
            });
        }

        // date range (paid_at fallback created_at)
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');
        if ($dateFrom || $dateTo) {
            $from = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null;
            $to   = $dateTo   ? Carbon::parse($dateTo)->endOfDay() : null;

            $query->where(function (Builder $qb) use ($from, $to) {
                if ($from) {
                    $qb->where(function (Builder $q1) use ($from) {
                        $q1->whereNotNull('paid_at')->where('paid_at', '>=', $from)
                            ->orWhere(function (Builder $q2) use ($from) {
                                $q2->whereNull('paid_at')->where('created_at', '>=', $from);
                            });
                    });
                }
                if ($to) {
                    $qb->where(function (Builder $q1) use ($to) {
                        $q1->whereNotNull('paid_at')->where('paid_at', '<=', $to)
                            ->orWhere(function (Builder $q2) use ($to) {
                                $q2->whereNull('paid_at')->where('created_at', '<=', $to);
                            });
                    });
                }
            });
        }

        // sorting
        $sort  = in_array($request->input('sort'), ['paid_at', 'created_at', 'amount', 'id']) ? $request->input('sort') : 'paid_at';
        $order = strtolower($request->input('order')) === 'asc' ? 'asc' : 'desc';

        if ($sort === 'paid_at') {
            $query->orderByRaw('paid_at IS NULL')
                ->orderBy('paid_at', $order)
                ->orderBy('created_at', $order);
        } else {
            $query->orderBy($sort, $order);
        }

        // pagination
        $perPage = $request->input('per_page', 20);
        if ($perPage === 'all') {
            $rows = $query->get();
            return response()->json([
                'data'         => $rows,
                'total'        => $rows->count(),
                'per_page'     => $rows->count(),
                'current_page' => 1,
                'last_page'    => 1,
            ]);
        }

        $paginated = $query->paginate((int) $perPage);

        return response()->json([
            'data'         => $paginated->items(),
            'total'        => $paginated->total(),
            'per_page'     => $paginated->perPage(),
            'current_page' => $paginated->currentPage(),
            'last_page'    => $paginated->lastPage(),
        ]);
    }

    /** ------------------------- Show ------------------------- */

    public function show($id)
    {
        $payment = ApplicationPayment::with([
            'applications:id,payment_id,exam_id,institute_id,zamat_id,total_amount,payment_status,payment_method',
            'exam:id,name',
            'institute:id,name,institute_code,phone',
            'zamat:id,name',
            'user:id,name',
        ])->findOrFail($id);

        return response()->json($payment);
    }

    /** ------------------------- Store (single + bulk) ------------------------- */
    /**
     * - Single payload: { exam_id, institute_id, zamat_id, amount, ... }
     * - Bulk  payload: { items: [ { ... }, { ... } ] }
     *
     * bKash: trx_id client থেকে আবশ্যক (required_if)
     * Bank/Cash/others: trx_id server auto (ছোট, unique)
     */
    public function store(Request $request)
    {
        $isBulk = is_array($request->input('items'));

        if ($isBulk) {
            $validated = $request->validate([
                'items'                   => ['required', 'array', 'min:1'],
                'items.*.exam_id'         => ['nullable', 'integer'],
                'items.*.institute_id'    => ['nullable', 'integer'],
                'items.*.zamat_id'        => ['nullable', 'integer'],
                'items.*.amount'          => ['required', 'numeric', 'min:0'],
                'items.*.payment_method'  => ['required', 'in:bkash,Bank,Cash Payment'],
                'items.*.status'          => ['nullable', 'in:Pending,Completed,Failed,Cancelled'],
                'items.*.trx_id'          => ['nullable', 'string', 'max:20', 'unique:application_payments,trx_id'],
                'items.*.payer_msisdn'    => ['nullable', 'string', 'max:20'],
                'items.*.meta'            => ['nullable'],
                'items.*.paid_at'         => ['nullable', 'date'],
                'items.*.user_id'         => ['nullable', 'exists:users,id'],
            ]);

            $items = $validated['items'];
            $defaultExamId = $this->getDefaultExamId(); // ⬅️ cache once

            $created = DB::transaction(function () use ($items, $defaultExamId) {
                $rows = [];
                foreach ($items as $row) {
                    // ✅ exam_id default
                    if (empty($row['exam_id'])) {
                        $row['exam_id'] = $defaultExamId;
                    }

                    $row['status'] = $row['status'] ?? 'Pending';

                    $row['user_id'] = $row['user_id'] ?? auth()->id();

                    if (isset($row['meta']) && is_string($row['meta'])) {
                        $decoded = json_decode($row['meta'], true);
                        $row['meta'] = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
                    }

                    if (($row['status'] ?? null) === 'Completed' && empty($row['paid_at'])) {
                        $row['paid_at'] = now();
                    }

                    if (($row['payment_method'] ?? null) === 'bkash') {
                        if (empty($row['trx_id'])) {
                            abort(422, 'trx_id is required for bKash payments.');
                        }
                    } else {
                        $row['trx_id'] = $this->generateShortTrxId($row['payment_method'] ?? null);
                    }

                    $rows[] = ApplicationPayment::create($row);
                }
                return $rows;
            });

            $fresh = collect($created)->map->load([
                'exam:id,name',
                'institute:id,name,institute_code,phone',
                'zamat:id,name',
                'user:id,name',
            ]);

            return response()->json([
                'count' => $fresh->count(),
                'items' => $fresh->values(),
            ], 201);
        } else {
            $data = $request->validate([
                'exam_id'        => ['nullable', 'integer'],
                'institute_id'   => ['nullable', 'integer'],
                'zamat_id'       => ['nullable', 'integer'],
                'amount'         => ['required', 'numeric', 'min:0'],
                'payment_method' => ['required', 'in:bkash,Bank,Cash Payment'],
                'status'         => ['nullable', 'in:Pending,Completed,Failed,Cancelled'],
                'trx_id'         => ['nullable', 'string', 'max:20', 'unique:application_payments,trx_id'],
                'payer_msisdn'   => ['nullable', 'string', 'max:20'],
                'meta'           => ['nullable'],
                'paid_at'        => ['nullable', 'date'],
                'user_id'        => ['nullable', 'exists:users,id'],
            ]);

            // ✅ exam_id default
            if (empty($data['exam_id'])) {
                $data['exam_id'] = $this->getDefaultExamId();
            }

            $data['status'] = $data['status'] ?? 'Pending';

            $data['user_id'] = $data['user_id'] ?? auth()->id();

            if (is_string($data['meta'] ?? null)) {
                $decoded = json_decode($data['meta'], true);
                $data['meta'] = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
            }

            if ($data['status'] === 'Completed' && empty($data['paid_at'])) {
                $data['paid_at'] = now();
            }

            if (($data['payment_method'] ?? null) === 'bkash') {
                if (empty($data['trx_id'])) {
                    return response()->json(['message' => 'trx_id is required for bKash payments.'], 422);
                }
            } else {
                $data['trx_id'] = $this->generateShortTrxId($data['payment_method'] ?? null);
            }

            $payment = ApplicationPayment::create($data);

            return response()->json(
                $payment->fresh()->load(['exam:id,name', 'institute:id,name,institute_code,phone', 'zamat:id,name', 'user:id,name']),
                201
            );
        }
    }

    /**
     * LIST PAGE: /application-payments/{exam}
     * Exam অনুযায়ী Institute-wise summary
     */
    public function byExam(Exam $exam, Request $request)
    {
        // ঐচ্ছিক: institute সার্চ
        $q = trim((string) $request->input('q', ''));

        $rows = ApplicationPayment::query()
            ->with(['institute:id,name,institute_code'])
            ->where('exam_id', $exam->id)
            ->when($q !== '', function ($qb) use ($q) {
                $qb->whereHas('institute', function ($iq) use ($q) {
                    $iq->where('name', 'like', "%{$q}%")
                        ->orWhere('institute_code', 'like', "%{$q}%");
                });
            })
            // ⬇️ created_at নিয়ে আসছি
            ->get(['id', 'exam_id', 'institute_id', 'amount', 'meta', 'created_at']);

        // group by institute_id
        $grouped = $rows->groupBy('institute_id')->map(function ($items) {
            $inst = $items->first()->institute;

            // sum students from meta
            $students = $items->reduce(function ($carry, $p) {
                $meta = is_array($p->meta) ? $p->meta : (json_decode($p->meta ?? 'null', true) ?: []);
                return $carry + (int) ($meta['students_count'] ?? 0);
            }, 0);

            // ⬇️ সর্বশেষ created_at বের করছি
            // নোট: sortByDesc + first() করলে Carbonই পাবো
            $latestCreated = optional($items->sortByDesc('created_at')->first())->created_at;

            return [
                'institute' => [
                    'id'             => $inst?->id,
                    'name'           => $inst?->name,
                    'institute_code' => $inst?->institute_code,
                ],
                'payments_count'    => $items->count(),
                'total_amount'      => (float) $items->sum('amount'),
                'total_students'    => $students,
                // ⬇️ চাইলে ফ্রন্টএন্ডে দেখাতে পারো
                'latest_created_at' => $latestCreated ? $latestCreated->toDateTimeString() : null,
            ];
        })->values();

        // ⬇️ সবসময়: সর্বশেষ পেমেন্ট আগে (latest_created_at DESC)
        $sorted = $grouped->sortByDesc(function ($row) {
            // null-safe: null হলে অনেক পুরোনো ধরে নিলাম
            return $row['latest_created_at'] ?? '1970-01-01 00:00:00';
        })->values();

        return response()->json([
            'exam'  => ['id' => $exam->id, 'name' => $exam->name],
            'items' => $sorted,
        ]);
    }

    /**
     * SHOW PAGE: /application-payments/{exam}/institutes/{institute}
     * Exam + Institute কম্বোর সব পেমেন্ট, pagination ছাড়া
     */
    public function byExamInstitute(Exam $exam, Institute $institute)
    {
        $payments = ApplicationPayment::query()
            ->with([
                'zamat:id,name',
                'user:id,name',
            ])
            ->where('exam_id', $exam->id)
            ->where('institute_id', $institute->id)
            ->orderByRaw('paid_at IS NULL ASC')
            ->orderBy('paid_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get([
                'id',
                'exam_id',
                'institute_id',
                'zamat_id',
                'user_id',
                'amount',
                'payment_method',
                'status',
                'trx_id',
                'payer_msisdn',
                'paid_at',
                'meta',
                'created_at',
            ]);

        // meta থেকে শিক্ষার্থী সংখ্যা বের করার হেল্পার (students_count > students)
        $extractStudents = function ($meta): int {
            $arr = is_array($meta) ? $meta : (json_decode($meta ?? 'null', true) ?: []);
            if (isset($arr['students_count']) && is_numeric($arr['students_count'])) {
                return (int) $arr['students_count'];
            }
            if (isset($arr['students']) && is_numeric($arr['students'])) {
                return (int) $arr['students'];
            }
            return 0;
        };

        // summary for header (দুটোর যেটা আছে সেটা যোগ)
        $totalAmount = (float) $payments->sum('amount');
        $totalStudents = $payments->reduce(function ($carry, $p) use ($extractStudents) {
            return $carry + $extractStudents($p->meta);
        }, 0);

        return response()->json([
            'exam'      => ['id' => $exam->id, 'name' => $exam->name],
            'institute' => [
                'id'             => $institute->id,
                'name'           => $institute->name,
                'institute_code' => $institute->institute_code,
                'phone'          => $institute->phone,
            ],
            'summary'   => [
                'payments_count' => $payments->count(),
                'total_amount'   => $totalAmount,
                'total_students' => $totalStudents, // ✅ students / students_count—দুটোই সাপোর্টেড
            ],
            'payments'  => $payments->map(function ($p) use ($extractStudents) {
                return [
                    'id'             => $p->id,
                    'zamat'          => $p->zamat ? ['id' => $p->zamat->id, 'name' => $p->zamat->name] : null,
                    'user'           => $p->user ? ['id' => $p->user->id, 'name' => $p->user->name] : null,
                    'amount'         => (float) $p->amount,
                    'payment_method' => $p->payment_method,
                    'status'         => $p->status,
                    'trx_id'         => $p->trx_id,
                    'payer_msisdn'   => $p->payer_msisdn,
                    'paid_at'        => optional($p->paid_at)->toDateTimeString(),
                    'created_at'     => optional($p->created_at)->toDateTimeString(),
                    'meta'           => $p->meta,
                    'students'       => $extractStudents($p->meta),
                ];
            })->values(),
        ]);
    }
}
