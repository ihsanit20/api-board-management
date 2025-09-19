<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institute extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'area_id', 'institute_code', 'is_active', 'is_center'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_center' => 'boolean',
    ];

    /**
     * Get the area that the institute belongs to.
     */
    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class, 'institute_id', 'id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'institute_id', 'id');
    }

    public function applicationPayments()
    {
        return $this->hasMany(ApplicationPayment::class, 'institute_id');
    }
}