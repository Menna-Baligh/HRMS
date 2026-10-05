<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TodayAttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $hasCheckedIn = $this['has_checked_in'];
        $hasCheckedOut = $this['has_checked_out'];
        $isInside = $this['is_inside_radius'];

        $statusCode = ($hasCheckedIn && ! $hasCheckedOut) ? 'On shift' : 'Off shift';

        return [
            'status' => __('attendance.status.'.$statusCode),
            'check_in_time' => $this['check_in_time'],
            'check_out_time' => $this['check_out_time'],
            'worked_time' => $this->formatWorkedTime($this['worked_seconds']),
            'distance_meters' => $this['distance_meters'],
            'is_inside_radius' => $isInside,
            'can_check_in' => ! $hasCheckedIn && $isInside,
            'can_check_out' => $hasCheckedIn && ! $hasCheckedOut,

            'widgets' => $this['widgets'],
        ];
    }

    private function formatWorkedTime(?int $seconds): string
    {
        if (! $seconds || $seconds <= 0) {
            return '0h 0m 0s';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
    }
}
