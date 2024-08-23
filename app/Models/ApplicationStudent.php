<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationStudent extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'application_id',
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
     * The relationship between ApplicationStudent and other models.
     */

    // Each ApplicationStudent belongs to a specific Application
    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    // An ApplicationStudent belongs to a specific Area
    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    // An ApplicationStudent belongs to a specific Institute
    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    // An ApplicationStudent belongs to a specific Zamat (class)
    public function zamat()
    {
        return $this->belongsTo(Zamat::class);
    }

    // An ApplicationStudent belongs to a specific Exam
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
}
