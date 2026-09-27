<?php

namespace App\Services;

use App\Enums\GoalStatus;
use App\Jobs\SendNotificationJob;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GoalService
{
    public function createGoal(User $creator, array $data): Goal
    {

        $targetUserId = $data['employee_id'];
        $targetUser = User::findOrFail($targetUserId);

        $goal = Goal::create([
            'user_id' => $targetUserId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'target_date' => $data['target_date'],
            'status' => GoalStatus::ACTIVE,
        ]);

        if ($targetUser) {
            SendNotificationJob::dispatch(
                user: $targetUser,
                type: 'goal_created',
                titleKey: 'notifications.goal_created_title',
                bodyKey: 'notifications.goal_created_body',
                parameters: [
                    'employee' => $targetUser->name,
                    'title' => $goal->title,
                ],
                metadata: ['goal_id' => $goal->id, 'screen' => 'goal_details']
            );
        }

        return $goal->load(['user']);
    }

    public function updateGoal(Goal $goal, array $data): Goal
    {
        $goal->update(array_filter([
            'title' => $data['title'] ?? $goal->title,
            'description' => array_key_exists('description', $data) ? $data['description'] : $goal->description,
            'target_date' => $data['target_date'] ?? $goal->target_date,
            'employee_id' => $data['employee_id'] ?? $goal->employee_id,
            'status' => $data['status'] ?? $goal->status,
        ], fn ($value) => ! is_null($value)));

        return $goal->load(['user']);
    }

    public function markAsCompleted(Goal $goal): Goal
    {
        return DB::transaction(function () use ($goal) {
            $wasCompletedBefore = $goal->status === GoalStatus::COMPLETED;

            $goal->status = GoalStatus::COMPLETED;
            $goal->save();

            if (! $wasCompletedBefore) {
                $this->notifyGoalCompletion($goal);
            }

            return $goal->load('user');
        });
    }

    private function notifyGoalCompletion(Goal $goal): void
    {
        $user = $goal->user;
        $employeeName = $user?->name ?? 'Employee';

        $recipients = collect();

        if ($user?->manager) {
            $recipients->push($user->manager);
        }

        $managementUsers = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['HR', 'Owner']);
        })->get();

        $recipients = $recipients->merge($managementUsers)->unique('id');

        foreach ($recipients as $recipient) {
            SendNotificationJob::dispatch(
                user: $recipient,
                type: 'goal_completed',
                titleKey: 'notifications.goal_completed_title',
                bodyKey: 'notifications.goal_completed_body',
                parameters: [
                    'employee' => $employeeName,
                    'title' => $goal->title,
                ],
                metadata: [
                    'goal_id' => $goal->id,
                    'screen' => 'goal_details',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]
            );
        }
    }

    public function getEmployeeGoals(User $user, ?string $status = null, int $perPage = 15)
    {
        $query = Goal::where('user_id', $user->id);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->latest()->paginate($perPage);
    }

    public function getGoalById(int $goalId): ?Goal
    {
        return Goal::with(['histories.updater'])->find($goalId);
    }

    public function getEmployeeGoalDetails(User $user, int $goalId): ?Goal
    {
        return Goal::with(['histories.updater'])
            ->where('user_id', $user->id)
            ->where('id', $goalId)
            ->first();
    }

    public function getManagerTeamGoals(User $manager, ?string $status = null, ?int $userId = null, int $perPage = 15)
    {
        $teamUserIds = User::where('manager_id', $manager->id)->pluck('id');

        $query = Goal::with(['user.department'])
            ->whereIn('user_id', $teamUserIds);

        if ($status) {
            $query->where('status', $status);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->latest()->paginate($perPage);
    }

    public function getHrGoalsOverview(?string $status = null, ?int $departmentId = null, ?int $userId = null, int $perPage = 15)
    {
        $query = Goal::with(['user.department'])
            ->whereHas('user', fn ($q) => $q->excludeOwnerAndSelf());

        if ($status) {
            $query->where('status', $status);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($departmentId) {
            $query->whereHas('user', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        return $query->latest()->paginate($perPage);
    }
}
