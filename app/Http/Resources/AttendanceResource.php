<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'date' => $this->date?->format('Y-m-d'),
            'check_in' => $this->check_in?->format('h:i A'),
            'check_out' => $this->check_out?->format('h:i A'),
            'status' => __('attendance.status.'.$this->status),
            'worked_seconds' => $this->worked_seconds,
            'is_exception' => (bool) $this->is_exception,
        ];
    }
}
