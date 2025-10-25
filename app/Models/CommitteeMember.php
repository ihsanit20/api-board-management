<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommitteeMember extends Model
{
    protected $fillable = [
        'committee_id',
        'user_id',
        'name',
        'designation',
        'phone',
        'email',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class);
    }
}
