<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentIntent extends Model
{
    protected $table = 'payment_intents';

    protected $fillable = [
        'token',
        'exam_id',
        'institute_id',
        'zamat_id',
        'expected_amount',
        'registrations',
        'status',
        'transaction_id',
        'meta',
        'expires_at',
    ];

    protected $casts = [
        'expected_amount' => 'decimal:2',
        'registrations'   => 'array',
        'meta'            => 'array',
        'expires_at'      => 'datetime',
    ];

    // Relations
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }
    public function zamat()
    {
        return $this->belongsTo(Zamat::class);
    }

    // Scopes
    public function scopeInitiated($q)
    {
        return $q->where('status', 'initiated');
    }
    public function scopeActive($q)
    {
        return $q->where('status', 'initiated')->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }
}
