<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fee extends Model
{
    use HasFactory;

    protected $fillable = ['exam_id', 'zamat_amounts', 'last_date', 'final_date'];

    protected $casts = [
        'zamat_amounts' => 'array',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
    
    public function zamat()
    {
        return $this->belongsTo(Zamat::class);
    }
}
