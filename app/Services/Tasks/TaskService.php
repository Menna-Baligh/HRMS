<?php

namespace App\Services\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskAssignment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TaskService
{
    /**
     * Create a new task.
     */
    public function create(array $data): Task
    {

        dd(Auth::id());
        $task = Task::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'priority' => TaskPriority::from($data['priority']),
            'status' => TaskStatus::PENDING,
            'deadline' => $data['deadline'],
            'progress' => 0,
            'created_by' => Auth::id(),
        ]);

        $this->logActivity(
            task: $task,
            employeeId: Auth::id(),
            action: 'created',
            description: 'Task created.'
        );

        return $task;
    }

    public function update(Task $task, array $data): Task
    {
        $this->ensureTaskCanBeUpdated($task);

        $oldValues = $task->only([
            'title',
            'description',
            'priority',
            'deadline',
        ]);

        $task->update($data);

        $newValues = $task->only([
            'title',
            'description',
            'priority',
            'deadline',
        ]);

        $this->logActivity(
            task: $task,
            employeeId: Auth::id(),
            action: 'updated',
            oldValue: json_encode($oldValues),
            newValue: json_encode($newValues),
            description: 'Task details updated.'
        );

        return $task->refresh();
    }

    public function assign(Task $task, int $employeeId)
    {
        $this->ensureTaskCanBeAssigned($task);

        $employee = User::findOrFail($employeeId);

        $this->ensureEmployeeCanBeAssigned($employee);

        if (
            TaskAssignment::where('task_id', $task->id)
                ->where('employee_id', $employeeId)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'employee_id' => 'This employee is already assigned to this task.',
            ]);
        }

        $assignment = TaskAssignment::create([
            'task_id' => $task->id,
            'employee_id' => $employeeId,
            'assigned_by' => Auth::id(),
            'assigned_at' => now(),
        ]);

        $this->logActivity(
            task: $task,
            employeeId: Auth::id(),
            action: 'assigned',
            description: "Task assigned to employee #{$employeeId}."
        );

        return $assignment;
    }

    /**
     * Update task progress.
     */
    public function updateProgress(Task $task, int $progress): Task
    {
        $this->ensureEmployeeAssignedToTask($task);

        if ($task->status === TaskStatus::CLOSED) {
            throw ValidationException::withMessages([
                'task' => 'Closed tasks cannot be updated.',
            ]);
        }

        $oldProgress = $task->progress;

        $task->update([
            'progress' => $progress,
        ]);

        $this->logActivity(
            task: $task,
            employeeId: Auth::id(),
            action: 'progress_updated',
            oldValue: (string) $oldProgress,
            newValue: (string) $progress,
            description: 'Task progress updated.'
        );

        return $task->refresh();
    }

    /**
     * Update task status.
     */
    public function updateStatus(Task $task, TaskStatus $newStatus): Task
    {
        $oldStatus = $task->status;

        $this->validateStatusTransition(
            $task,
            $oldStatus,
            $newStatus
        );

        $task->update([
            'status' => $newStatus,
        ]);

        $this->logActivity(
            task: $task,
            employeeId: Auth::id(),
            action: 'status_changed',
            oldValue: $oldStatus->value,
            newValue: $newStatus->value,
            description: 'Task status changed.'
        );

        return $task->refresh();
    }

    private function ensureTaskCanBeUpdated(Task $task): void
    {
        if ($task->status === TaskStatus::CLOSED) {
            throw ValidationException::withMessages([
                'task' => 'Closed tasks cannot be updated.',
            ]);
        }
    }

    /**
     * Make sure the task can be assigned.
     */
    private function ensureTaskCanBeAssigned(Task $task): void
    {
        if ($task->status === TaskStatus::CLOSED) {
            throw ValidationException::withMessages([
                'task' => 'Closed tasks cannot be assigned.',
            ]);
        }
    }

    private function ensureEmployeeCanBeAssigned(User $employee): void
    {
        if ($employee->role !== 'Employee') {
            throw ValidationException::withMessages([
                'employee_id' => 'The selected user is not an employee.',
            ]);
        }
    }

    private function ensureEmployeeAssignedToTask(Task $task): void
    {
        $isAssigned = TaskAssignment::where('task_id', $task->id)
            ->where('employee_id', Auth::id())
            ->exists();

        if (! $isAssigned) {
            throw ValidationException::withMessages([
                'task' => 'You are not assigned to this task.',
            ]);
        }
    }

    private function validateStatusTransition(
        Task $task,
        TaskStatus $oldStatus,
        TaskStatus $newStatus
    ): void {
        if ($oldStatus === $newStatus) {
            throw ValidationException::withMessages([
                'status' => 'The task already has this status.',
            ]);
        }

        $allowedTransitions = [
            TaskStatus::PENDING->value => [
                TaskStatus::IN_PROGRESS->value,
            ],

            TaskStatus::IN_PROGRESS->value => [
                TaskStatus::COMPLETED->value,
            ],

            TaskStatus::COMPLETED->value => [
                TaskStatus::CLOSED->value,
            ],

            TaskStatus::CLOSED->value => [],
        ];

        $allowed = $allowedTransitions[$oldStatus->value] ?? [];

        if (! in_array($newStatus->value, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "Cannot change task status from {$oldStatus->value} to {$newStatus->value}.",
            ]);
        }
    }

    /**
     * Store task activity history.
     */
    private function logActivity(
        Task $task,
        int $employeeId,
        string $action,
        ?string $oldValue = null,
        ?string $newValue = null,
        ?string $description = null
    ): TaskActivity {
        return TaskActivity::create([
            'task_id' => $task->id,
            'employee_id' => $employeeId,
            'action' => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'description' => $description,
        ]);
    }
}
