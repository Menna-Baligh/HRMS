<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'default_days',
        'is_active',
        'requires_balance',
        'requires_attachment',
    ];

    protected $casts = [
        'default_days' => 'decimal:2',
        'is_active' => 'boolean',
        'requires_balance' => 'boolean',
        'requires_attachment' => 'boolean',
    ];

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
