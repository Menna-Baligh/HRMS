<?php

namespace App\Listeners;

use App\Events\LeaveRequestApproved;
use App\Notifications\LeaveRequestApprovedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendLeaveApprovedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(LeaveRequestApproved $event): void
    {
        $request = $event->leaveRequest->loadMissing('user');
        $request->user->notify(new LeaveRequestApprovedNotification($request));
    }
}
