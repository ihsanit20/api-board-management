<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Center extends Model
{
    use HasFactory;

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function zamat()
    {
        return $this->belongsTo(Zamat::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
