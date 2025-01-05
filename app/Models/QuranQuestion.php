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

    // Center সম্পর্ক
    public function center()
    {
        return $this->belongsTo(Center::class, 'center_id', 'id');
    }

    // Zamat সম্পর্ক
    public function zamat()
    {
        return $this->belongsTo(Zamat::class, 'zamat_id', 'id');
    }
}