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
        'para_group_id', // নতুন ফিল্ড যোগ করা হয়েছে
        'questions',
    ];

    protected $casts = [
        'questions' => 'array',
    ];

    // Center সম্পর্ক
    public function center()
    {
        return $this->belongsTo(Institute::class, 'center_id', 'id');
    }

    // Zamat সম্পর্ক
    public function zamat()
    {
        return $this->belongsTo(Zamat::class, 'zamat_id', 'id');
    }

    // Para Group সম্পর্ক
    public function paraGroup()
    {
        return $this->belongsTo(ParaGroup::class, 'para_group_id', 'id');
    }
}
