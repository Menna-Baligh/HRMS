<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveRequestApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly LeaveRequest $leaveRequest) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Your leave request for {$this->leaveRequest->requested_days} day(s) from {$this->leaveRequest->start_date->toDateString()} has been approved.",
            'leave_request_id' => $this->leaveRequest->id,
            'status' => $this->leaveRequest->status->value,
        ];
    }
}
