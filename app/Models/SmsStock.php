<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsStock extends Model
{
    protected $fillable = ['quantity', 'price'];
}
