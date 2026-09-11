<?php

namespace App\Enums;

enum LeaveStatus: string
{
    case Pending = 'pending';
    case ApprovedByManager = 'approved_by_manager';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    /** Status values that allow the employee to cancel. */
    public function isCancellable(): bool
    {
        return match ($this) {
            self::Pending, self::ApprovedByManager => true,
            default => false,
        };
    }

    /** Status values that are considered "active" (block overlap checks). */
    public function isActive(): bool
    {
        return match ($this) {
            self::Pending, self::ApprovedByManager, self::Approved => true,
            default => false,
        };
    }
}
