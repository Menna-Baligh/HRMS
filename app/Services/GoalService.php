<?php
namespace App\Services;

use App\Enums\GoalStatus;
use App\Events\GoalCompleted;
use App\Models\Employee;
use App\Models\Goal;
use App\Models\GoalProgressHistory;
use Illuminate\Support\Facades\DB;

class GoalService
{

    public function createGoal(Employee $employee, array $data): Goal
    {
        return Goal::create([
            'employee_id' => $employee->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'target_value' => $data['target_value'],
            'current_value' => 0,
            'target_date' => $data['target_date'],
            'status' => GoalStatus::ACTIVE,
        ]);
    }


    public function updateGoal(Goal $goal, array $data): Goal
    {
        $goal->update(array_filter([
            'title' => $data['title'] ?? $goal->title,
            'description' => array_key_exists('description', $data) ? $data['description'] : $goal->description,
            'target_value' => $data['target_value'] ?? $goal->target_value,
            'target_date' => $data['target_date'] ?? $goal->target_date,
        ], fn ($value) => ! is_null($value)));

        return $goal->fresh(['histories.updater']);
    }

    
    public function updateProgress(Goal $goal, float $newValue, int $updatedByUserId, ?string $note = null): Goal
    {
        return DB::transaction(function () use ($goal, $newValue, $updatedByUserId, $note) {
            $previousValue = $goal->current_value;

            GoalProgressHistory::create([
                'goal_id' => $goal->id,
                'updated_by' => $updatedByUserId,
                'previous_value' => $previousValue,
                'new_value' => $newValue,
                'note' => $note,
            ]);

            $goal->current_value = $newValue;
            $wasCompletedBefore = $goal->status === GoalStatus::COMPLETED;

            if ($goal->current_value >= $goal->target_value) {
                $goal->status = GoalStatus::COMPLETED;
            }

            $goal->save();

            if ($goal->status === GoalStatus::COMPLETED && ! $wasCompletedBefore) {
                event(new GoalCompleted($goal));
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
                event(new GoalCompleted($goal));
            }

            return $goal->fresh(['histories.updater']);
        });
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
}