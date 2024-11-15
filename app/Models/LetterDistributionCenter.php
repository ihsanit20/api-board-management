<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LetterDistributionCenter extends Model
{
    use HasFactory;

    protected $table = 'letter_distribution_centers';

    protected $fillable = [
        'area_id',
        'institute_id',
        'name',
        'institute_ids',
    ];

    protected $casts = [
        'institute_ids' => 'array',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }
}
