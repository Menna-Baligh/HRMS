<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'title' => $this->title,
            'description' => $this->description,
            'target_value' => $this->target_value,
            'current_value' => $this->current_value,
            'progress_percentage' => $this->progress_percentage,
            'target_date' => $this->target_date?->format('Y-m-d'),
            'status' => $this->status->value ?? $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
