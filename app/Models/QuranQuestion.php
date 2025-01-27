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
        'zamat_id',
        'para_group_id',
        'questions',
    ];

    protected $casts = [
        'questions' => 'array',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id', 'id');
    }

    public function center()
    {
        return $this->belongsTo(Institute::class, 'center_id', 'id');
    }

    public function zamat()
    {
        return $this->belongsTo(Zamat::class, 'zamat_id', 'id');
    }

    public function paraGroup()
    {
        return $this->belongsTo(ParaGroup::class, 'para_group_id', 'id');
    }
}
