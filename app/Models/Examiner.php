<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Examiner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'nid',
        'address',
        'education',
        'experience',
        'ex_experience',
        'institute_id',
        'type',
        'designation',
        'exam_id',
        'center_id',
        'status',
        'examiner_code',
        'student_count',
    ];

    protected $casts = [
        'education' => 'array',
        'experience' => 'json',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class, 'institute_id');
    }

    public function center()
    {
        return $this->belongsTo(Institute::class, 'center_id');
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

}