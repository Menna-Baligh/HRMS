<?php

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'Owner';
    case HR = 'HR';
    case Manager = 'Manager';
    case Employee = 'Employee';

    /** Returns true if this role can perform HR-level actions. */
    public function isHrOrAbove(): bool
    {
        return match ($this) {
            self::Owner, self::HR => true,
            default => false,
        };
    }

    /** Returns true if this role can perform Manager-level actions. */
    public function isManagerOrAbove(): bool
    {
        return match ($this) {
            self::Owner, self::HR, self::Manager => true,
            default => false,
        };
    }
}
