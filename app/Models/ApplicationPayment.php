<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationPayment extends Model
{
    use HasFactory;

    protected $fillable = [
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
        'user_id',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'meta'    => 'array',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // no-op
    }

    public function applications()
    {
        return $this->hasMany(Application::class, 'payment_id');
    }

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
    public function scopeByExam($q, $id)
    {
        return $q->where('exam_id', $id);
    }
    public function scopeByInstitute($q, $id)
    {
        return $q->where('institute_id', $id);
    }
    public function scopeByZamat($q, $id)
    {
        return $q->where('zamat_id', $id);
    }

    /** Relations */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Optional quick scope
    public function scopeByUser($q, $userId)
    {
        return $q->where('user_id', $userId);
    }
}
