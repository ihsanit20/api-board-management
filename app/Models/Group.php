<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;
    protected $fillable = [
        'zamat_id',
        'name',
    ];

    public function zamat()
    {
        return $this->belongsTo(Zamat::class);
    }
}
