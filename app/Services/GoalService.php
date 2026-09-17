<?php

namespace App\Services;

use App\Enums\GoalStatus;
use App\Jobs\SendNotificationJob;
use App\Models\Employee;
use App\Models\Goal;
use App\Models\GoalProgressHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GoalService
{
    public function createGoal(Employee $employee, array $data): Goal
    {
        $goal = Goal::create([
            'employee_id'   => $employee->id,
            'title'         => $data['title'],
            'description'   => $data['description'] ?? null,
            'target_value'  => $data['target_value'],
            'current_value' => 0,
            'target_date'   => $data['target_date'],
            'status'        => GoalStatus::ACTIVE,
        ]);

        if ($employee->manager?->user) {
            SendNotificationJob::dispatch(
                user: $employee->manager->user,
                type: 'goal_created',
                titleKey: 'notifications.goal_created_title',
                bodyKey: 'notifications.goal_created_body',
                parameters: [
                    'employee' => $employee->user?->name ?? 'Employee',
                    'title'    => $goal->title,
                ],
                metadata: ['goal_id' => $goal->id, 'screen' => 'goal_details']
            );
        }

        return $goal;
    }

    public function updateGoal(Goal $goal, array $data): Goal
    {
        $goal->update(array_filter([
            'title'        => $data['title'] ?? $goal->title,
            'description'  => array_key_exists('description', $data) ? $data['description'] : $goal->description,
            'target_value' => $data['target_value'] ?? $goal->target_value,
            'target_date'  => $data['target_date'] ?? $goal->target_date,
        ], fn ($value) => ! is_null($value)));

        return $goal->fresh(['histories.updater']);
    }

    public function updateProgress(Goal $goal, float $newValue, int $updatedByUserId, ?string $note = null): Goal
    {
        return DB::transaction(function () use ($goal, $newValue, $updatedByUserId, $note) {
            $previousValue = $goal->current_value;

            GoalProgressHistory::create([
                'goal_id'        => $goal->id,
                'updated_by'     => $updatedByUserId,
                'previous_value' => $previousValue,
                'new_value'      => $newValue,
                'note'           => $note,
            ]);

            $goal->current_value = $newValue;
            $wasCompletedBefore = $goal->status === GoalStatus::COMPLETED;

            if ($goal->current_value >= $goal->target_value) {
                $goal->status = GoalStatus::COMPLETED;
            }

            $goal->save();

            if ($goal->status === GoalStatus::COMPLETED && ! $wasCompletedBefore) {
                $this->notifyGoalCompletion($goal);
            }

            return $goal->fresh(['histories.updater']);
        });
    }

    public function markAsCompleted(Goal $goal): Goal
    {
        return DB::transaction(function () use ($goal) {
            $wasCompletedBefore = $goal->status === GoalStatus::COMPLETED;

            $goal->status = GoalStatus::COMPLETED;
            $goal->current_value = $goal->target_value;
            $goal->save();

            if (! $wasCompletedBefore) {
                $this->notifyGoalCompletion($goal);
            }

            return $goal->fresh(['histories.updater']);
        });
    }


    private function notifyGoalCompletion(Goal $goal): void
    {
        $employee = $goal->employee;
        $employeeName = $employee?->user?->name ?? 'Employee';

        $recipients = collect();

        if ($employee?->manager?->user) {
            $recipients->push($employee->manager->user);
        }

        $managementUsers = User::whereIn('role', ['HR', 'Owner'])->get();
        $recipients = $recipients->merge($managementUsers)->unique('id');

        foreach ($recipients as $recipient) {
            SendNotificationJob::dispatch(
                user: $recipient,
                type: 'goal_completed',
                titleKey: 'notifications.goal_completed_title',
                bodyKey: 'notifications.goal_completed_body',
                parameters: [
                    'employee' => $employeeName,
                    'title'    => $goal->title,
                ],
                metadata: [
                    'goal_id' => $goal->id,
                    'screen'  => 'goal_details',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]
            );
        }
    }

    public function getEmployeeGoals(Employee $employee, ?string $status = null, int $perPage = 15)
    {
        $query = Goal::where('employee_id', $employee->id);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->latest()->paginate($perPage);
    }

    public function getEmployeeGoalDetails(Employee $employee, int $goalId): ?Goal
    {
        return Goal::with(['histories.updater'])
            ->where('employee_id', $employee->id)
            ->where('id', $goalId)
            ->first();
    }

    public function getManagerTeamGoals(Employee $manager, ?string $status = null, ?int $employeeId = null, int $perPage = 15)
    {
        $teamEmployeeIds = Employee::where('manager_id', $manager->id)->pluck('id');

        $query = Goal::with(['employee.user', 'employee.department'])
            ->whereIn('employee_id', $teamEmployeeIds);

        if ($status) {
            $query->where('status', $status);
        }

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        return $query->latest()->paginate($perPage);
    }

    public function getHrGoalsOverview(?string $status = null, ?int $departmentId = null, ?int $employeeId = null, int $perPage = 15)
    {
        $query = Goal::with(['employee.user', 'employee.department']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        if ($departmentId) {
            $query->whereHas('employee', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        return $query->latest()->paginate($perPage);
    }
}
