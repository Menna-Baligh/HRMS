<?php

namespace App\Http\Resources\Leaves;

use App\Http\Resources\FileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
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
              'user_id' => $this->user_id, 
              'leave_type_id' => $this->leave_type_id,
               'start_date' => $this->start_date, 
               'end_date' => $this->end_date,
                'days' => $this->days, 
                'reason' => $this->reason, 
                'status' => $this->status,
                 'rejection_reason' => $this->rejection_reason,
                  'reviewed_by' => $this->reviewed_by,
                   'reviewed_at' => $this->reviewed_at, 
                   'created_at' => $this->created_at,
                    'updated_at' => $this->updated_at, 
                    'user' => $this->whenLoaded('user'), 
                    'leave_type' => $this->whenLoaded('leaveType'), 
                    'reviewer' => $this->whenLoaded('reviewer'),
                     'decisions' => $this->whenLoaded('decisions'),
                      'image' => FileResource::collection( $this->whenLoaded('files') ), ];
    }
}
