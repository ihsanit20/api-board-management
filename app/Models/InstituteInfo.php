<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstituteInfo extends Model
{
    use HasFactory;

    // টেবিল নাম singular: 'institute_info'
    protected $table = 'institute_info';

    protected $fillable = [
        'institute_id',
        'address',
        'established_on',
        'founder_name',
        'muhtamim',
        'upto_class',
        'students',
        'teachers',
        'has_hostel',
        'land_info',
        'building_summary',
        'has_library_for_students',
        'has_kutubkhana',
        'kutubkhana',
    ];

    protected $casts = [
        'established_on'          => 'date',
        'muhtamim'                => 'array',
        'students'                => 'array',
        'teachers'                => 'array',
        'has_hostel'              => 'boolean',
        'has_library_for_students' => 'boolean',
        'has_kutubkhana'          => 'boolean',
        'kutubkhana'              => 'array',
    ];

    /** Relations */
    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    /** Convenience accessor (optional): kitab_count from JSON */
    public function getKitabCountAttribute(): ?int
    {
        $val = $this->kutubkhana['kitab_count'] ?? null;
        return is_numeric($val) ? (int) $val : null;
    }
}
