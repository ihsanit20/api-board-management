<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'students' => 'json'
    ];

    // Relationships

    // An application belongs to an exam
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    // An application belongs to a zamat
    public function zamat()
    {
        return $this->belongsTo(Zamat::class);
    }

    // An application belongs to an institute
    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    // An application was submitted by a user
    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    // An application was approved by a user
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // An application has many application students
    public function applicationStudents()
    {
        return $this->hasMany(ApplicationStudent::class);
    }

    // An application has one payment
    // public function payment()
    // {
    //     return $this->hasOne(Payment::class);
    // }
}
