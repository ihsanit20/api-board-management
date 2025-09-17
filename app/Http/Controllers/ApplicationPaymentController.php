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
                'application:id,exam_id,institute_id,zamat_id,total_amount,payment_status,payment_method',
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
        if ($request->filled('application_id')) {
            $query->where('application_id', (int) $request->application_id);
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
                // Prefer paid_at if available
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
            $query->orderByRaw('paid_at IS NULL') // false(0) first -> not null first; flip to put null last
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
            'application:id,exam_id,institute_id,zamat_id,total_amount,payment_status,payment_method',
            'exam:id,name',
            'institute:id,name,institute_code,phone',
            'zamat:id,name',
        ])->findOrFail($id);

        return response()->json($payment);
    }
}
