<?php

namespace App\Services\Aggregators;

use App\Models\Attendance;

class AttendanceAggregatorService
{
    public function getMetrics(int $employeeId, string $startDate, string $endDate): array
    {
        $attendances = Attendance::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $totalRecords = $attendances->count();
        $presentCount = $attendances->whereIn('status', ['Present', 'Late'])->count();
        $lateCount = $attendances->where('status', 'Late')->count();
        $absentCount = $attendances->where('status', 'Absent')->count();
        $totalWorkedSeconds = $attendances->sum('worked_seconds');

        $attendanceRate = $totalRecords > 0
            ? round(($presentCount / $totalRecords) * 100, 2)
            : 0.0;

        return [
            'total_days' => $totalRecords,
            'present_days' => $presentCount,
            'late_days' => $lateCount,
            'absent_days' => $absentCount,
            'attendance_rate' => $attendanceRate,
            'total_worked_hours' => round($totalWorkedSeconds / 3600, 2),
        ];
    }
}
