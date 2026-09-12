<?php

namespace App\Services;

use Carbon\Carbon;

class LeaveDateCalculator
{
    /**
     * Calculate the number of calendar days for a leave request (inclusive).
     *
     * Example: start=2024-01-01, end=2024-01-03 → 3 days
     */
    public function calculate(string|Carbon $startDate, string|Carbon $endDate): float
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        return (float) ($start->diffInDays($end) + 1);
    }
}
