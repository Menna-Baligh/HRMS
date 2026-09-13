<?php

namespace App\Http\Resources;

use App\Models\LeaveDecisionHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LeaveDecisionHistory */
class LeaveDecisionHistoryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reviewer' => new UserResource($this->whenLoaded('reviewer')),
            'previous_status' => $this->previous_status->value,
            'new_status' => $this->new_status->value,
            'note' => $this->note,
            'decided_at' => $this->decided_at?->toISOString(),
        ];
    }
}
