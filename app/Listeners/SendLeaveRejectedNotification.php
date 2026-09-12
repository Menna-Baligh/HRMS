<?php

namespace App\Listeners;

use App\Events\LeaveRequestRejected;
use App\Notifications\LeaveRequestRejectedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendLeaveRejectedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(LeaveRequestRejected $event): void
    {
        $request = $event->leaveRequest->loadMissing('user');
        $request->user->notify(new LeaveRequestRejectedNotification($request));
    }
}
