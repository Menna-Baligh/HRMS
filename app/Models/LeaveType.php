<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'requires_balance',
        'requires_attachment',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'requires_balance' => 'boolean',
            'requires_attachment' => 'boolean',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /** @param Builder<LeaveType> $query */
    public function scopeActive($query): void
    {
        $query->where('is_active', true);
    }
}
