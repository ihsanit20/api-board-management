<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuranQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'center_id',
        'department_id',
        'zamat_id',
        'questions',
    ];

    protected $casts = [
        'questions' => 'array',
    ];
}