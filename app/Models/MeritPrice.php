<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeritPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'zamat_id',
        'merit_name',
        'price_amount',
    ];

    // 🔗 Exam রিলেশন
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    // 🔗 Zamat রিলেশন
    public function zamat()
    {
        return $this->belongsTo(Zamat::class);
    }
}
