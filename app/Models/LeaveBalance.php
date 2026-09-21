<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    protected $fillable = [
        'user_id',
        'leave_type_id',
        'year',
        'allocated_days',
        'used_days',
    ];

    protected $casts = [
        'year' => 'integer',
        'allocated_days' => 'decimal:2',
        'used_days' => 'decimal:2',
    ];

    protected $appends = [
        'remaining_days',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function getRemainingDaysAttribute(): float
    {
        return max(
            0,
            (float) $this->allocated_days -
            (float) $this->used_days
        );
    }
}
