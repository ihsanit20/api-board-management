<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'registration',
        'name',
        'name_arabic',
        'father_name',
        'father_name_arabic',
        'date_of_birth',
        'address',
        'area_id',
        'institute_id',
        'zamat_id',
        'exam_id',
    ];

    /**
     * The relationships between Student and other models.
     */

    // A student belongs to a specific Zamat (class)
    public function zamat()
    {
        return $this->belongsTo(Zamat::class);
    }

    // A student belongs to a specific Institute
    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    // A student belongs to a specific Area
    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    // A student belongs to a specific Exam
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
}
