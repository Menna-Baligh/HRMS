<?php

namespace App\Models;

use App\Enums\LeaveStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'status',
        'start_date',
        'end_date',
        'requested_days',
        'reason',
        'attachment_path',
        'manager_id',
        'hr_id',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => LeaveStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'requested_days' => 'decimal:2',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function hr(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_id');
    }

    public function decisionHistories(): HasMany
    {
        return $this->hasMany(LeaveDecisionHistory::class)->orderBy('decided_at');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Requests that block date ranges (non-terminal statuses).
     *
     * @param  Builder<LeaveRequest>  $query
     */
    public function scopeActive($query): void
    {
        $query->whereIn('status', array_column(
            array_filter(LeaveStatus::cases(), fn (LeaveStatus $s) => $s->isActive()),
            'value',
        ));
    }

    /**
     * Overlap detection: requests that overlap with [$start, $end].
     *
     * @param  Builder<LeaveRequest>  $query
     */
    public function scopeOverlapping($query, string $start, string $end): void
    {
        $query->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start);
    }
}
