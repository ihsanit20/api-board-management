<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'marks' => 'json',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function zamat()
    {
        return $this->belongsTo(Zamat::class);
    }

    public function examSubject()
    {
        return $this->belongsTo(ExamSubject::class);
    }
}