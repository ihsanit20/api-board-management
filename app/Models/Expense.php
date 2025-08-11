<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'purpose',
        'amount',
        'medium_user_id',
        'description',
        'date',
        'voucher_no',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    // মাধ্যম(ব্যক্তি) রিলেশন
    public function mediumUser()
    {
        return $this->belongsTo(User::class, 'medium_user_id');
    }
}