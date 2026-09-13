<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveRequestRejectedNotification extends Notification
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
            'message' => "Your leave request from {$this->leaveRequest->start_date->toDateString()} has been rejected.",
            'leave_request_id' => $this->leaveRequest->id,
            'rejection_reason' => $this->leaveRequest->rejection_reason,
            'status' => $this->leaveRequest->status->value,
        ];
    }
}
