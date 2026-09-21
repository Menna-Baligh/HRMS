<?php

namespace App\Services\Calendar;

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CalendarService
{
    /**
     * Get unified calendar events for the authenticated user.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getEvents( User $user,Carbon $from, Carbon $to): Collection
     {
        $events = collect();

        // Add approved leave events.
        $events = $events->merge(
            $this->getApprovedLeaveEvents($user, $from, $to)
        );

        // Add assigned task deadline events.
        $events = $events->merge(
            $this->getTaskDeadlineEvents($user, $from, $to)
        );

        return $events
            ->sortBy([
                ['date', 'asc'],
                ['type', 'asc'],
                ['reference', 'asc'],
            ])
            ->values();
    }

    /**
     * Get approved leave events for the authenticated employee.
     *
     * A leave request can span multiple days, so one calendar
     * event is generated for each day inside the requested range.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function getApprovedLeaveEvents(User $user, Carbon $from, Carbon $to): Collection
     {
        $employee = $user->employee;

        if (! $employee) {
            return collect();
        }

        $leaveRequests = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->where('status', LeaveStatus::Approved)
            ->whereDate('start_date', '<=', $to->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->get([
                'id',
                'start_date',
                'end_date',
            ]);

        return $leaveRequests->flatMap(
            function (LeaveRequest $leaveRequest) use ($from, $to) {
                $start = Carbon::parse($leaveRequest->start_date)
                    ->max($from->copy()->startOfDay());

                $end = Carbon::parse($leaveRequest->end_date)
                    ->min($to->copy()->startOfDay());

                if ($start->gt($end)) {
                    return collect();
                }

                return collect(
                    CarbonPeriod::create($start, $end)
                )->map(
                    fn (Carbon $date) => [
                        'date' => $date->toDateString(),
                        'type' => 'leave',
                        'reference' => $leaveRequest->id,
                    ]
                );
            }
        );
    }

    /**
     * Get deadlines for tasks assigned to the authenticated employee.
     *
     * Only tasks assigned to the current employee are included.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function getTaskDeadlineEvents( User $user, Carbon $from, Carbon $to ): Collection
    {
        $employee = $user->employee;

        if (! $employee) {
            return collect();
        }

        return Task::query()
            ->whereHas(
                'assignments',
                fn ($query) => $query->where(
                    'employee_id',
                    $employee->id
                )
            )
            ->whereBetween('deadline', [
                $from->copy()->startOfDay(),
                $to->copy()->endOfDay(),
            ])
            ->get([
                'id',
                'deadline',
            ])
            ->map(
                fn (Task $task) => [
                    'date' => $task->deadline->toDateString(),
                    'type' => 'task_deadline',
                    'reference' => $task->id,
                ]
            );
    }
}