<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LandingRoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role_name' => $this->role_name,
            'title' => $this->title,
            'description' => $this->description,
            'features' => $this->features,
            'order' => $this->order,
        ];
    }
}
