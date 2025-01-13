<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSubject extends Model
{
    use HasFactory;

    protected $fillable = ['exam_id', 'subject_id', 'full_marks', 'pass_marks'];

    protected $casts = [
        'exam_id' => 'int',
        'subject_id' => 'int',
        'full_marks' => 'int',
        'pass_marks' => 'int',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
