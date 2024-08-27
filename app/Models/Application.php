<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'students' => 'array',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function zamat()
    {
        return $this->belongsTo(Zamat::class);
    }

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function center()
    {
        return $this->belongsTo(Institute::class, 'center_id');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }
}