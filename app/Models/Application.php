<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    // ✅ শুধু এই ফিল্ডগুলো mass-assignable
    protected $fillable = [
        'exam_id',
        'zamat_id',
        'institute_id',
        'group_id',
        'area_id',
        'center_id',
        'gender',
        'payment_status',
        'payment_method',
        'total_amount',
        'submitted_by',
        'approved_by',
        'students',        // ⚠️ নীচের নোট দেখুন (attribute vs relation নাম সংঘর্ষ)
        'application_date',
        'payment_id',      // ✅ নতুন (1-to-1 Payment FK)
    ];

    // (ঐচ্ছিক) DB ডিফল্টের সাথে মিল রেখে model default
    protected $attributes = [
        'payment_status' => 'Pending',
        'payment_method' => 'Offline',
    ];

    protected $casts = [
        'exam_id'          => 'integer',
        'zamat_id'         => 'integer',
        'institute_id'     => 'integer',
        'group_id'         => 'integer',
        'area_id'          => 'integer',
        'center_id'        => 'integer',
        'submitted_by'     => 'integer',
        'approved_by'      => 'integer',
        'total_amount'     => 'integer',
        'students'         => 'array',     // ⚠️ নীচের নোট দেখুন
        'application_date' => 'date:Y-m-d',
        'payment_id'       => 'integer',
    ];

    /* Relations */
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
    public function zamat()
    {
        return $this->belongsTo(Zamat::class);
    }
    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }
    public function group()
    {
        return $this->belongsTo(Group::class);
    }
    public function area()
    {
        return $this->belongsTo(Area::class);
    }
    public function center()
    {
        return $this->belongsTo(Institute::class, 'center_id');
    }
    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ⚠️ যদি টেবিলে সত্যিই Students নামে অন্য টেবিল থাকে ও relation লাগেই,
    // তাহলে নিচের নামটা বদলে নিন (e.g., enrolledStudents) — নইলে attribute 'students' এর সাথে সংঘর্ষ হবে।
    // public function students()
    // {
    //     return $this->hasMany(Student::class);
    // }

    /** ✅ 1-to-1: Application belongsTo ApplicationPayment via payment_id */

    public function payment()
    {
        return $this->belongsTo(ApplicationPayment::class, 'payment_id');
    }

    // ⛔️ আগে যেটা ছিল (hasMany payments), এখন আর দরকার নেই:
    // public function payments() { return $this->hasMany(ApplicationPayment::class); }
}
