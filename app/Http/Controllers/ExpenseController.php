<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    // তালিকা (সিম্পল ফিল্টার+পেজিনেশন)
    public function index(Request $r)
    {
        $perPage = (int) ($r->per_page ?? 20);

        $query = Expense::query()
            ->with(['mediumUser:id,name']) // ইউজারের নাম দেখাতে
            ->when($r->search, function ($q) use ($r) {
                $s = $r->search;
                $q->where(function ($w) use ($s) {
                    $w->where('purpose', 'like', "%{$s}%")
                        ->orWhere('voucher_no', 'like', "%{$s}%")
                        ->orWhere('description', 'like', "%{$s}%");
                });
            })
            ->when($r->medium_user_id, fn($q, $id) => $q->where('medium_user_id', $id))
            ->when($r->date_from, fn($q, $d) => $q->whereDate('date', '>=', $d))
            ->when($r->date_to, fn($q, $d) => $q->whereDate('date', '<=', $d))
            ->orderByDesc('date')
            ->orderByDesc('id');

        return $query->paginate($perPage);
    }

    // নতুন খরচ যোগ করা
    public function store(Request $r)
    {
        $data = $r->validate([
            'purpose'        => 'required|string|max:255',
            'amount'         => 'required|numeric|min:0.01',
            'medium_user_id' => 'nullable|exists:users,id',
            'description'    => 'nullable|string',
            'date'           => 'nullable|date',
            'voucher_no'     => 'nullable|string|max:100',
        ]);

        $expense = Expense::create($data);
        return response()->json($expense->load('mediumUser:id,name'), 201);
    }

    // একক খরচ দেখানো
    public function show(Expense $expense)
    {
        return $expense->load('mediumUser:id,name');
    }

    // আপডেট
    public function update(Request $r, Expense $expense)
    {
        $data = $r->validate([
            'purpose'        => 'required|string|max:255',
            'amount'         => 'required|numeric|min:0.01',
            'medium_user_id' => 'nullable|exists:users,id',
            'description'    => 'nullable|string',
            'date'           => 'nullable|date',
            'voucher_no'     => 'nullable|string|max:100',
        ]);

        $expense->update($data);
        return $expense->load('mediumUser:id,name');
    }

    // ডিলিট
    public function destroy(Expense $expense)
    {
        $expense->delete();
        return response()->noContent();
    }
}
