<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CollectFee extends Model
{
    use HasFactory;

    protected $table = 'collect_fees';

    /**
     * ফিলেবল প্রপার্টি: যেগুলো ইনসার্ট/আপডেট করা যাবে।
     */
    protected $fillable = [
        'student_ids',
        'total_amount',
        'payment_method',
        'transaction_id',
        'created_at',
        'updated_at',
    ];

    /**
     * কাস্টম কাস্টিং: student_ids ফিল্ডটি JSON হিসেবে কাস্ট করা হবে।
     */
    protected $casts = [
        'student_ids' => 'array',
    ];

    /**
     * শিক্ষার্থীদের সম্পর্ক তৈরি করা (Many-to-Many Relationship)
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'collect_fee_student', 'collect_fee_id', 'student_id');
    }

    /**
     * শিক্ষার্থীদের সংখ্যা গণনা
     */
    public function getStudentCountAttribute()
    {
        return count($this->student_ids ?? []);
    }

    /**
     * পেমেন্ট স্ট্যাটাস চেক
     */
    public function isPaid()
    {
        return $this->payment_method === 'online' && $this->transaction_id !== null;
    }
}
