<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LandingPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'billing_period' => $this->billing_period,
            'is_popular' => (bool) $this->is_popular,
            'features' => $this->features,
            'button_text' => $this->button_text,
            'button_link' => $this->button_link,
            'order' => $this->order,
        ];
    }
}
