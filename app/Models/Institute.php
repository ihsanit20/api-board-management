<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institute extends Model
{
    use HasFactory;
    
    protected $fillable = ['name', 'phone', 'area_id', 'institute_code', 'is_active', 'is_center'];

    /**
     * Get the area that the institute belongs to.
     */
    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}
