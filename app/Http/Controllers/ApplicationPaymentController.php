<?php

namespace App\Http\Controllers;

use App\Models\ApplicationPayment;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class ApplicationPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = ApplicationPayment::query()
            ->with([
                // Payment → Application (hasOne via applications.payment_id)
                'application:id,payment_id,exam_id,institute_id,zamat_id,total_amount,payment_status,payment_method',
                'exam:id,name',
                'institute:id,name,institute_code,phone',
                'zamat:id,name',
            ]);

        // ---- Filters ----
        if ($request->filled('exam_id')) {
            $query->where('exam_id', (int) $request->exam_id);
        }
        if ($request->filled('institute_id')) {
            $query->where('institute_id', (int) $request->institute_id);
        }
        if ($request->filled('zamat_id')) {
            $query->where('zamat_id', (int) $request->zamat_id);
        }

        // ✅ application_id এখন relation দিয়ে ফিল্টার হবে
        if ($request->filled('application_id')) {
            $query->whereHas('application', function (Builder $q) use ($request) {
                $q->where('id', (int) $request->application_id);
            });
        }

        // status
        $status = $request->input('status');
        if ($status && strtolower($status) !== 'all') {
            $query->where('status', $status);
        }

        // method
        $method = $request->input('method') ?? $request->input('payment_method');
        if ($method && strtolower($method) !== 'all') {
            $query->where('payment_method', $method);
        }

        // search (trx_id / payer_msisdn / institute name/code/phone)
        if ($s = trim((string) $request->input('q', ''))) {
            $query->where(function (Builder $qb) use ($s) {
                $qb->where('trx_id', 'like', "%{$s}%")
                    ->orWhere('payer_msisdn', 'like', "%{$s}%")
                    ->orWhereHas('institute', function (Builder $iq) use ($s) {
                        $iq->where('name', 'like', "%{$s}%")
                            ->orWhere('institute_code', 'like', "%{$s}%")
                            ->orWhere('phone', 'like', "%{$s}%");
                    })
                    // চাইলে Application fields দিয়েও সার্চ করতে পারেন
                    ->orWhereHas('application', function (Builder $aq) use ($s) {
                        $aq->where('payment_status', 'like', "%{$s}%")
                            ->orWhere('payment_method', 'like', "%{$s}%");
                    });
            });
        }

        // date range on paid_at (fallback created_at)
        $dateFrom = $request->input('date_from'); // 'YYYY-MM-DD'
        $dateTo   = $request->input('date_to');   // 'YYYY-MM-DD'
        if ($dateFrom || $dateTo) {
            $from = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null;
            $to   = $dateTo   ? Carbon::parse($dateTo)->endOfDay()   : null;

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
            // NULL paid_at last
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

    public function show($id)
    {
        $payment = ApplicationPayment::with([
            'application:id,payment_id,exam_id,institute_id,zamat_id,total_amount,payment_status,payment_method',
            'exam:id,name',
            'institute:id,name,institute_code,phone',
            'zamat:id,name',
        ])->findOrFail($id);

        return response()->json($payment);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'exam_id'        => ['nullable', 'integer'],
            'institute_id'   => ['nullable', 'integer'],
            'zamat_id'       => ['nullable', 'integer'],
            'amount'         => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:bkash,Bank,Cash Payment'],
            'status'         => ['nullable', 'in:Pending,Completed,Failed,Cancelled'],
            'trx_id'         => ['nullable', 'string', 'max:100'],
            'payer_msisdn'   => ['nullable', 'string', 'max:20'],
            'meta'           => ['nullable'], // array বা JSON string
            'paid_at'        => ['nullable', 'date'],
        ]);

        // ডিফল্ট ও হালকা নরমালাইজেশন
        $data['status'] = $data['status'] ?? 'Pending';

        if (is_string($data['meta'] ?? null)) {
            $decoded = json_decode($data['meta'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data['meta'] = $decoded;
            } else {
                unset($data['meta']); // invalid JSON হলে বাদ
            }
        }

        if ($data['status'] === 'Completed' && empty($data['paid_at'])) {
            $data['paid_at'] = now();
        }

        $payment = ApplicationPayment::create($data);

        // চাইলে সঙ্গে সঙ্গেই কিছু lookup relation লোড করাতে পারেন
        return response()->json(
            $payment->fresh()->load(['exam:id,name', 'institute:id,name,institute_code,phone', 'zamat:id,name']),
            201
        );
    }
}
