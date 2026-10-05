<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BonusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_name' => $this->user?->name,
            'role' => $this->user?->role ?? 'Employee',
            'incentive_type' => $this->incentive_type,
            'amount' => (float) $this->amount,
            'target_month' => $this->target_month,
            'approved_by' => $this->approver?->name,
            'status' => $this->status,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
