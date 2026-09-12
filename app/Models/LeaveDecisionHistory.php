<?php

namespace App\Models;

use App\Enums\LeaveStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveDecisionHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_request_id',
        'reviewer_id',
        'previous_status',
        'new_status',
        'note',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'previous_status' => LeaveStatus::class,
            'new_status' => LeaveStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
