<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Center extends Model
{
    use HasFactory;

    // Allow mass assignment on these attributes
    protected $fillable = [
        'institute_id',
        'zamat_id',
        'group_id',
        'gender',
    ];

    // Define the relationship with the Institute model
    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    // Define the relationship with the Zamat model
    public function zamat()
    {
        return $this->belongsTo(Zamat::class);
    }

    // Define the relationship with the Group model
    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
