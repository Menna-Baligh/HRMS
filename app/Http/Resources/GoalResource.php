<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $statusVal = $this->status->value ?? $this->status;

        return [
            'id'                  => $this->id,
            'user'                => $this->whenLoaded('user', function () {
                return [
                    'id'         => $this->user->id,
                    'name'       => $this->user->name,
                    'job_title'  => $this->user->job_title,
                    'department' => $this->user->department?->name,
                ];
            }),
            'title'               => $this->title,
            'description'         => $this->description,
            'target_value'        => $this->target_value,
            'current_value'       => $this->current_value,
            'progress_percentage' => $this->progress_percentage,
            'target_date'         => $this->target_date?->format('Y-m-d'),
            'status'              => __('goal.statuses.' . $statusVal),
            'histories'           => GoalProgressHistoryResource::collection($this->whenLoaded('histories')),
            'created_at'          => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
