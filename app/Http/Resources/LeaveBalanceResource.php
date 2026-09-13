<?php

namespace App\Http\Resources;

use App\Models\LeaveBalance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LeaveBalance */
class LeaveBalanceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'leave_type' => new LeaveTypeResource($this->whenLoaded('leaveType')),
            'year' => $this->year,
            'allocated_days' => (float) $this->allocated_days,
            'used_days' => (float) $this->used_days,
            'remaining_days' => $this->remaining_days,
        ];
    }
}
