<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationPeriodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $statusVal = $this->status->value ?? $this->status;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'status' => __('evaluation.statuses.'.$statusVal),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
