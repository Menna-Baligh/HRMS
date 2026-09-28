<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $hours = floor($this->worked_seconds / 3600);
        $minutes = floor(($this->worked_seconds % 3600) / 60);

        return [
            'id' => $this->id,
            'date' => $this->date?->format('Y-m-d'),
            'day_name' => $this->date?->translatedFormat('l') ?? $this->date?->format('l'),
            'check_in' => $this->check_in?->format('h:i A'),
            'check_out' => $this->check_out?->format('h:i A'),
            'status' => __('attendance.status.'.$this->status),
            'worked_time' => $this->formatWorkedTime($this->worked_seconds),
            'is_exception' => (bool) $this->is_exception,
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
