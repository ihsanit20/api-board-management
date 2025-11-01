<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeePayment extends Model
{
    protected $table = 'fee_payments';

    protected $fillable = [
        'exam_id',
        'institute_id',
        'zamat_id',
        'channel',
        'method',
        'status',
        'gross_amount',
        'net_amount',
        'service_charge',
        'students_count',
        'trx_id',
        'payment_id',
        'payer_msisdn',
        'paid_at',
        'user_id',
        'meta',
    ];

    protected $casts = [
        'gross_amount'   => 'decimal:2',
        'net_amount'     => 'decimal:2',
        'service_charge' => 'decimal:2',
        'students_count' => 'integer',
        'paid_at'        => 'datetime',
        'meta'           => 'array',
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
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeCompleted($q)
    {
        return $q->where('status', 'Completed');
    }
    public function scopeOnline($q)
    {
        return $q->where('channel', 'online');
    }
    public function scopeOffline($q)
    {
        return $q->where('channel', 'offline');
    }
}
