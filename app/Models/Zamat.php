<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zamat extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active',
        'department_id',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
