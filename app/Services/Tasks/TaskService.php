<?php

namespace App\Services\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Employee;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskAssignment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaskService
{
    /**
     * Create a new task.
     */
    public function create(array $data): Task
    {
        return DB::transaction(function () use ($data) {

            $task = Task::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'priority' => TaskPriority::from($data['priority']),
                'status' => TaskStatus::PENDING,
                'deadline' => $data['deadline'],
                'progress' => 0,
                'created_by' => Auth::id(),
            ]);

            // The activity actor is a User, not an Employee.
            $this->logActivity(
                task: $task,
                userId: Auth::id(),
                action: 'created',
                description: 'Task created.'
            );

            return $task;
        });
    }

    /**
     * Update task details.
     */
    public function update(Task $task, array $data): Task
    {
        return DB::transaction(function () use ($task, $data) {

            $this->ensureTaskCanBeUpdated($task);

            $oldValues = $task->only([
                'title',
                'description',
                'priority',
                'deadline',
            ]);

            $task->update([
                'title' => $data['title'] ?? $task->title,
                'description' => $data['description'] ?? $task->description,
                'priority' => isset($data['priority'])
                    ? TaskPriority::from($data['priority'])
                    : $task->priority,
                'deadline' => $data['deadline'] ?? $task->deadline,
            ]);

            $newValues = $task->only([
                'title',
                'description',
                'priority',
                'deadline',
            ]);

            $this->logActivity(
                task: $task,
                userId: Auth::id(),
                action: 'updated',
                oldValue: json_encode($oldValues),
                newValue: json_encode($newValues),
                description: 'Task details updated.'
            );

            return $task->refresh();
        });
    }

    /**
     * Assign task to an employee.
     */
    public function assign(Task $task, int $employeeId): TaskAssignment
    {
        return DB::transaction(function () use ($task, $employeeId) {

            $this->ensureTaskCanBeAssigned($task);

            // employee_id refers to employees.id, not users.id.
            $employee = Employee::findOrFail($employeeId);

            $this->ensureEmployeeCanBeAssigned($employee);

            $alreadyAssigned = TaskAssignment::where('task_id', $task->id)
                ->where('employee_id', $employee->id)
                ->exists();

            if ($alreadyAssigned) {
                throw ValidationException::withMessages([
                    'employee_id' => 'This employee is already assigned to this task.',
                ]);
            }

            // assigned_by refers to users.id.
            $assignment = TaskAssignment::create([
                'task_id' => $task->id,
                'employee_id' => $employee->id,
                'assigned_by' => Auth::id(),
                'assigned_at' => now(),
            ]);

            // The activity actor is the authenticated User.
            $this->logActivity(
                task: $task,
                userId: Auth::id(),
                action: 'assigned',
                description: "Task assigned to employee #{$employee->id}."
            );

            return $assignment;
        });
    }

    /**
     * Update task progress.
     */
    public function updateProgress(Task $task, int $progress): Task
    {
        return DB::transaction(function () use ($task, $progress) {

            $employee = $this->getAuthenticatedEmployee();

            $this->ensureEmployeeAssignedToTask($task, $employee);

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
                userId: Auth::id(),
                action: 'progress_updated',
                oldValue: (string) $oldProgress,
                newValue: (string) $progress,
                description: 'Task progress updated.'
            );

            return $task->refresh();
        });
    }

    /**
     * Update task status.
     */
    public function updateStatus(
        Task $task,
        TaskStatus $newStatus
    ): Task {
        return DB::transaction(function () use ($task, $newStatus) {

            $employee = $this->getAuthenticatedEmployee();

            $this->ensureEmployeeAssignedToTask($task, $employee);

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
                userId: Auth::id(),
                action: 'status_changed',
                oldValue: $oldStatus->value,
                newValue: $newStatus->value,
                description: 'Task status changed.'
            );

            return $task->refresh();
        });
    }

    /**
     * Get the Employee record linked to the authenticated User.
     *
     * This is required only for employee-specific actions.
     */
    private function getAuthenticatedEmployee(): Employee
    {
        $employee = Employee::where('user_id', Auth::id())->first();

        if (! $employee) {
            throw ValidationException::withMessages([
                'employee' => 'The authenticated user is not linked to an employee.',
            ]);
        }

        return $employee;
    }

    /**
     * Make sure the task can be updated.
     */
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

    /**
     * Make sure the selected employee is active.
     */
    private function ensureEmployeeCanBeAssigned(Employee $employee): void
    {
        if ($employee->status !== 'active') {
            throw ValidationException::withMessages([
                'employee_id' => 'The selected employee is inactive.',
            ]);
        }
    }

    /**
     * Make sure the authenticated employee is assigned to the task.
     */
    private function ensureEmployeeAssignedToTask(
        Task $task,
        Employee $employee
    ): void {
        $isAssigned = TaskAssignment::where('task_id', $task->id)
            ->where('employee_id', $employee->id)
            ->exists();

        if (! $isAssigned) {
            throw ValidationException::withMessages([
                'task' => 'You are not assigned to this task.',
            ]);
        }
    }

    /**
     * Validate task status transition.
     */
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
     *
     * user_id refers to users.id because the actor
     * can be HR, Manager, Admin, or Employee.
     */
    private function logActivity(
        Task $task,
        int $userId,
        string $action,
        ?string $oldValue = null,
        ?string $newValue = null,
        ?string $description = null
    ): TaskActivity {
        return TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => $userId,
            'action' => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'description' => $description,
        ]);
    }
}
