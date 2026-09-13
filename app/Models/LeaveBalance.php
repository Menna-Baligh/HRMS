<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'year',
        'allocated_days',
        'used_days',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'allocated_days' => 'decimal:2',
            'used_days' => 'decimal:2',
        ];
    }

    // ─── Computed ─────────────────────────────────────────────────────────────

    /** Remaining days — never stored, always derived. */
    public function getRemainingDaysAttribute(): float
    {
        return (float) $this->allocated_days - (float) $this->used_days;
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

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * @param  Builder<LeaveBalance>  $query
     */
    public function scopeForYear($query, int $year): void
    {
        $query->where('year', $year);
    }
}
