<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsRecord extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sms_records';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'message',
        'receiver_info',
        'receiver_count',
        'sms_parts',
        'total_cost',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'receiver_info' => 'array', // JSON ফিল্ডকে array হিসেবে কাস্ট করা হয়েছে
    ];

    /**
     * Get the total number of receivers.
     *
     * @return int
     */
    public function getTotalReceiversAttribute()
    {
        return $this->receiver_count;
    }
}
