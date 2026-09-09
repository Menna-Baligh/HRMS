<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
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
            'employee_id' => $this->employee?->employee_id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'job_title' => $this->employee?->job_title,
            'employment_type' => $this->employee?->employment_type,
            'status' => $this->employee?->status,
            'start_date' => $this->employee?->start_date ? date('Y-m-d', strtotime($this->employee->start_date)) : null,
            'phone' => $this->employee?->phone,
            'address' => $this->employee?->address,
            'department' => $this->employee?->department?->name,
            'manager' => $this->employee?->manager ? [
                'id' => $this->employee->manager->id,
                'employee_id' => $this->employee->manager->employee_id,
                'name' => $this->employee->manager->user?->name,
            ] : null,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
