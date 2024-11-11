<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fee extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['exam_id', 'zamat_amounts', 'last_date', 'final_date'];


    protected $casts = [
        'zamat_amounts' => 'array',
    ];


    /**
     * Get the exam associated with the fee.
     */
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the zamat associated with the fee.
     */
    public function zamat()
    {
        return $this->belongsTo(Zamat::class);
    }
}
