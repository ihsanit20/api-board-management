<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'reg_last_date', 'reg_final_date', 'registration_fee', 'late_fee'];

    protected $casts = [
        "late_fee" => "int",
        "registration_fee" => "int",
    ];
}