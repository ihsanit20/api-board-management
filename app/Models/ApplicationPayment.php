<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationPayment extends Model
{
    use HasFactory;

    // Mass-assignable fields
    protected $fillable = [
        'application_id',
        'amount',
        'payment_method',
        'status',
        'trx_id',
        'payer_msisdn',
        'meta',
        'paid_at',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'meta'    => 'array',
        'paid_at' => 'datetime',
    ];

    // Relations
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    // (Optional) Quick scopes
    public function scopeCompleted($q)
    {
        return $q->where('status', 'Completed');
    }
    public function scopePending($q)
    {
        return $q->where('status', 'Pending');
    }
}
