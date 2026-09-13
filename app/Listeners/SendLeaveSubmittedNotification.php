<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\LeaveRequestSubmitted;
use App\Models\User;
use App\Notifications\LeaveRequestSubmittedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendLeaveSubmittedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(LeaveRequestSubmitted $event): void
    {
        $request = $event->leaveRequest->loadMissing(['user.manager']);
        $employee = $request->user;

        // If employee has a direct manager, notify them
        if ($employee->manager) {
            $employee->manager->notify(new LeaveRequestSubmittedNotification($request));
        } else {
            // Otherwise, notify HR/Owner
            $recipients = User::whereIn('role', [UserRole::HR, UserRole::Owner])->get();
            foreach ($recipients as $recipient) {
                $recipient->notify(new LeaveRequestSubmittedNotification($request));
            }
        }
    }
}
