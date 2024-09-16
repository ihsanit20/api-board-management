<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;
    protected $fillable = ['area_code', 'name'];
    
    public function institutes()
    {
        return $this->hasMany(Institute::class);
    }
}
