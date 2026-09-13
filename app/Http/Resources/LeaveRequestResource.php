<?php

namespace App\Http\Resources;

use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LeaveRequest */
class LeaveRequestResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'leave_type' => new LeaveTypeResource($this->whenLoaded('leaveType')),
            'status' => $this->status->value,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'requested_days' => (float) $this->requested_days,
            'reason' => $this->reason,
            'has_attachment' => ! is_null($this->attachment_path),
            'rejection_reason' => $this->rejection_reason,
            'manager' => new UserResource($this->whenLoaded('manager')),
            'hr' => new UserResource($this->whenLoaded('hr')),
            'decision_histories' => LeaveDecisionHistoryResource::collection($this->whenLoaded('decisionHistories')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
