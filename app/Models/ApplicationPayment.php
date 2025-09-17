<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationPayment extends Model
{
    use HasFactory;

    // Mass-assignable fields
    protected $fillable = [
        'application_id',
        'exam_id',
        'institute_id',
        'zamat_id',
        'amount',
        'payment_method', // 'bkash' | 'Bank' | 'Cash Payment'
        'status',         // 'Pending' | 'Completed' | 'Failed' | 'Cancelled'
        'trx_id',
        'payer_msisdn',
        'meta',
        'paid_at',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'meta'    => 'array',
        'paid_at' => 'datetime',
    ];

    // Auto-fill denormalized FKs from Application if not provided
    protected static function booted(): void
    {
        static::creating(function (self $payment) {
            if ($payment->application_id && (! $payment->exam_id || ! $payment->institute_id || ! $payment->zamat_id)) {
                if ($app = Application::query()
                    ->select(['exam_id', 'institute_id', 'zamat_id'])
                    ->find($payment->application_id)
                ) {
                    $payment->exam_id      = $payment->exam_id      ?? $app->exam_id;
                    $payment->institute_id = $payment->institute_id ?? $app->institute_id;
                    $payment->zamat_id     = $payment->zamat_id     ?? $app->zamat_id;
                }
            }
        });
    }

    /** Relations */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    public function zamat(): BelongsTo
    {
        return $this->belongsTo(Zamat::class);
    }

    /** Quick scopes */
    public function scopeCompleted($q)
    {
        return $q->where('status', 'Completed');
    }
    public function scopePending($q)
    {
        return $q->where('status', 'Pending');
    }
    public function scopeOnline($q)
    {
        return $q->where('payment_method', 'bkash');
    }
    public function scopeByExam($q, $examId)
    {
        return $q->where('exam_id', $examId);
    }
    public function scopeByInstitute($q, $instId)
    {
        return $q->where('institute_id', $instId);
    }
    public function scopeByZamat($q, $zamatId)
    {
        return $q->where('zamat_id', $zamatId);
    }
}
