<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = ['zamat_id', 'name', 'code'];

    public function zamat()
    {
        return $this->belongsTo(Zamat::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($subject) {
            if (!$subject->code) {
                $subject->code = $subject->zamat_id . str_pad($subject->id ?? '', 2, '0', STR_PAD_LEFT);
            }
        });

        static::created(function ($subject) {
            $subject->code = $subject->zamat_id . str_pad($subject->id, 2, '0', STR_PAD_LEFT);
            $subject->save();
        });
    }


}
