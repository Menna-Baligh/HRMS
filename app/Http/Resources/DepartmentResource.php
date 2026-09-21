<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status ? __('statuses.'.$this->status) : null,
            'manager' => $this->relationLoaded('manager') && $this->manager ? [
                'id' => $this->manager->id,
                'name' => $this->manager->name,
                'email' => $this->manager->email,
            ] : null,
            'employees_count' => $this->whenCounted('employees', $this->employees_count, fn () => $this->employees()->count()),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
