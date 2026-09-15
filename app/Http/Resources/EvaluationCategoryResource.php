<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'max_score' => (float) $this->max_score,
            'weight' => (float) $this->weight,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
