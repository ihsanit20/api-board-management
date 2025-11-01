<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstituteApplication extends Model
{
    use HasFactory;

    // টেবিল নাম ডিফল্ট কনভেনশনেই ঠিক আছে: institute_applications
    protected $fillable = [
        'institute_id',
        'name',
        'phone',
        'area_id',
        'type',
        'payload_json',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_notes',
        // 'approved_snapshot_json',
        // 'merge_map',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'reviewed_at'  => 'datetime',
    ];

    // টাইপ/স্ট্যাটাস কনস্ট্যান্টস
    public const TYPE_NEW          = 'NEW';
    public const TYPE_UPDATE       = 'UPDATE';
    public const TYPE_DETAILS_ONLY = 'DETAILS_ONLY';

    public const STATUS_PENDING    = 'pending';
    public const STATUS_APPROVED   = 'approved';
    public const STATUS_REJECTED   = 'rejected';
    public const STATUS_NEEDS_INFO = 'needs_info';

    /** সম্পর্কগুলো */
    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function reviewer()
    {
        // reviewed_by → users.id
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** ---------- Query Scopes ---------- */

    // status wise
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    // type wise
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // name/phone quick search for admin queue
    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        $t = trim($term);
        return $query->where(function ($q) use ($t) {
            $q->where('name', 'like', "%{$t}%")
                ->orWhere('phone', 'like', "%{$t}%");
        });
    }

    // কোনো নির্দিষ্ট ইনস্টিটিউটের আবেদনগুলো
    public function scopeForInstitute($query, int $instituteId)
    {
        return $query->where('institute_id', $instituteId);
    }
}
