<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HrAttendanceExceptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $rawStatus = $this->status ?? 'Absent';

        return [
            'attendance_id' => $this->id,
            'date' => $this->date?->format('Y-m-d'),
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'employee_code' => $this->user?->employee_id ?? 'N/A',
                'job_title' => $this->user?->job_title,
                'department' => $this->user?->department?->name ?? 'N/A',
            ],
            'check_in' => $this->check_in?->format('h:i A'),
            'check_out' => $this->check_out?->format('h:i A'),
            'status' => __('attendance.status.'.$rawStatus),
            'exception' => $this->is_exception ? [
                'is_exception' => true,
                'reason' => $this->exception_reason,
                'status' => $this->exception_status ?? 'pending',
                'admin_note' => $this->admin_note,
            ] : null,
            'company_name' => $this->companyLocation?->name,
        ];
    }
}
