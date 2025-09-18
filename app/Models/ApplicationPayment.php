<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationPayment extends Model
{
    use HasFactory;

    // ✅ application_id আর নেই
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
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'meta'    => 'array',
        'paid_at' => 'datetime',
    ];

    // ⛔️ পুরনো booted() ব্লক লাগবে না, কারণ application_id ডিনর্মালাইজ করা হচ্ছে না
    protected static function booted(): void
    {
        // no-op
    }

    /** ✅ 1-to-1 forward lookup: এই Payment কোন Application-এ যুক্ত */
    public function application()
    {
        return $this->hasOne(Application::class, 'payment_id');
    }

    // (ঐচ্ছিক) লুকআপ/রিপোর্টিং এর জন্য এগুলো রাখতে পারেন
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
}
