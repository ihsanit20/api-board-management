<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Examiner extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'designation', 'institute', 'address', 'phone'];
}
