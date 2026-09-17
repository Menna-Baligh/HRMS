<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckAttendanceRemindersCommand extends Command
{
    protected $signature = 'attendance:check-reminders {type : reminder or late}';

    protected $description = 'Send automated attendance reminders or late warnings to employees.';

    public function handle(NotificationService $notificationService): int
    {
        $type = $this->argument('type');
        $today = Carbon::today();

        $checkedInEmployeeIds = Attendance::whereDate('date', $today)
            ->pluck('employee_id')
            ->toArray();

        $pendingUsers = User::whereHas('employee', function ($query) use ($checkedInEmployeeIds) {
            $query->whereNotIn('id', $checkedInEmployeeIds);
        })->get();

        foreach ($pendingUsers as $user) {
            if ($type === 'reminder') {
                $notificationService->send(
                    user: $user,
                    type: 'shift_reminder',
                    titleKey: 'notifications.shift_reminder_title',
                    bodyKey: 'notifications.shift_reminder_body',
                    parameters: [],
                    metadata: ['screen' => 'attendance_checkin', 'click_action' => 'FLUTTER_NOTIFICATION_CLICK']
                );
            } elseif ($type === 'late') {
                $notificationService->send(
                    user: $user,
                    type: 'shift_late_warning',
                    titleKey: 'notifications.shift_late_title',
                    bodyKey: 'notifications.shift_late_body',
                    parameters: [],
                    metadata: ['screen' => 'attendance_checkin', 'click_action' => 'FLUTTER_NOTIFICATION_CLICK']
                );
            }
        }

        $this->info("Attendance {$type} notifications sent successfully to ".$pendingUsers->count().' users.');

        return Command::SUCCESS;
    }
}
