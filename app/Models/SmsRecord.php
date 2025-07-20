<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsRecord extends Model
{
    use HasFactory;

    protected $table = 'sms_records';

    protected $fillable = [
        'message',
        'sms_parts',
        'sms_count',
        'numbers',
        'cost',
        'event',
        'status',
        'institute_id',
    ];

    protected $casts = [
        'numbers' => 'array',
    ];
}
