<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CollectFee extends Model
{
    use HasFactory;

    protected $table = 'collect_fees';

    protected $fillable = [
        'student_ids',
        'total_amount',
        'payment_method',
        'transaction_id',
        'created_at',
        'updated_at',
        'exam_id',
        'institute_id',
        'zamat_id',
    ];

    protected $casts = [
        'student_ids' => 'array',
    ];

    public function students()
    {
        return $this->belongsToMany(Student::class, 'collect_fee_student', 'collect_fee_id', 'student_id');
    }

    public function getStudentCountAttribute()
    {
        return count($this->student_ids ?? []);
    }

    public function isPaid()
    {
        return $this->payment_method === 'online' && $this->transaction_id !== null;
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

}
