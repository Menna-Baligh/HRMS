<?php

namespace App\Services\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Jobs\SendNotificationJob;
use App\Models\Employee;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskAssignment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
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
    /** * Assign task to an Employee user. */
    public function assign(Task $task, int $userId): TaskAssignment
    {
        $assignment = DB::transaction(function () use ($task, $userId) {
            $this->ensureTaskCanBeAssigned($task);
            // The assigned employee is a User with Employee role.
            $user = User::findOrFail($userId);
            $this->ensureUserCanBeAssigned($user);
            $alreadyAssigned = TaskAssignment::where('task_id', $task->id)->where('user_id', $user->id)->exists();
            if ($alreadyAssigned) {
                throw ValidationException::withMessages([
                    'user_id' => __('tasks.already_assigned'),
                ]);

            }
            $assignment = TaskAssignment::create([
                'task_id' => $task->id,
                'user_id' => $user->id,
                'assigned_by' => Auth::id(),
                'assigned_at' => now(),
            ]);
            $this->logActivity(
                task: $task,
                userId: Auth::id(),
                action: 'assigned',
                description: "Task assigned to user #{$user->id}."
            );

            return $assignment;
        });
        // Notify the assigned Employee user.
        $user = User::find($userId);
        if ($user) {
            SendNotificationJob::dispatch(
                user: $user,
                type: 'task_assigned',
                titleKey: 'notifications.task_assigned_title',
                bodyKey: 'notifications.task_assigned_body',
                parameters: ['title' => $task->title],
                metadata: ['task_id' => $task->id]
            );
        }

        return $assignment;
    }

    /**
     * Update task progress by the assigned Employee user.
     */
    public function updateProgress(Task $task, int $progress): Task
    {
        return DB::transaction(function () use ($task, $progress) {

            $user = Auth::user();

            // Only Employee users can update task progress.
            if ($user->role !== UserRole::Employee) {
                throw ValidationException::withMessages([
                    'user' => __('tasks.must_be_employee'),
                ]);
            }

            // The authenticated Employee must be assigned to this task.
            $this->ensureUserAssignedToTask(
                task: $task,
                userId: $user->id
            );

            // Closed tasks cannot be updated.
            if ($task->status === TaskStatus::CLOSED) {
                throw ValidationException::withMessages([
                    'task' => __('tasks.closed_cannot_update'),
                ]);
            }

            $oldProgress = $task->progress;

            $task->update([
                'progress' => $progress,
            ]);

            $this->logActivity(
                task: $task,
                userId: $user->id,
                action: 'progress_updated',
                oldValue: (string) $oldProgress,
                newValue: (string) $progress,
                description: __('tasks.progress_updated')
            );

            return $task->refresh();
        });
    }

    /**
     * Update task status by the assigned Employee user.
     */
    /**
     * Update task status by the assigned Employee user.
     */
    public function updateStatus(Task $task, TaskStatus $status): Task
    {
        return DB::transaction(function () use ($task, $status) {

            $user = Auth::user();

            // Only Employee users can update task status.
            if ($user->role !== UserRole::Employee) {
                throw ValidationException::withMessages([
                    'user' => __('tasks.must_be_employee'),
                ]);
            }

            // The authenticated Employee must be assigned to this task.
            $this->ensureUserAssignedToTask(
                task: $task,
                userId: $user->id
            );

            // Closed tasks cannot be updated.
            if ($task->status === TaskStatus::CLOSED) {
                throw ValidationException::withMessages([
                    'task' => __('tasks.closed_cannot_update'),
                ]);
            }

            $oldStatus = $task->status;

            // Validate the status transition.
            $this->validateStatusTransition(
                task: $task,
                oldStatus: $oldStatus,
                newStatus: $status
            );

            // Update task status.
            $task->update([
                'status' => $status->value,
            ]);

            // Log status change.
            $this->logActivity(
                task: $task,
                userId: $user->id,
                action: 'status_changed',
                oldValue: $oldStatus->value,
                newValue: $status->value,
                description: __('tasks.status_updated')
            );

            return $task->refresh();
        });
    }

    /**
     * Get task details.
     */
    public function show(Task $task, User $user): Task
    {
        $this->ensureCanViewTask(
            task: $task,
            user: $user,
        );

        return $task->load([
            'creator:id,name,email',

            'assignments.user:id,name,email',
            'assignments.assignedBy:id,name,email',
        ]);
    }

    /**
     * Get task activity history.
     */
    public function activities(Task $task, User $user)
    {
        $this->ensureCanViewTask(
            task: $task,
            user: $user,
        );

        return $task->activities()
            ->with([
                'user:id,name,email',
            ])
            ->latest('id')
            ->get();
    }

    /**
     * Get tasks visible to the authenticated user.
     *
     * Managers can see tasks they created or manage.
     * Employees can only see tasks assigned to them.
     * HR and Owner can see all tasks.
     */
    public function index(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = $this->visibleTasksQuery($user);

        /*
         * Filter by status.
         */
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        /*
         * Filter by priority.
         */
        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        /*
         * Filter by deadline range.
         */
        if (! empty($filters['deadline_from'])) {
            $query->whereDate(
                'deadline',
                '>=',
                $filters['deadline_from']
            );
        }

        if (! empty($filters['deadline_to'])) {
            $query->whereDate(
                'deadline',
                '<=',
                $filters['deadline_to']
            );
        }

        /*
         * Load only the relations needed for the task list.
         */
        $query->with([
            'creator:id,name,email',
            'assignments.user:id,name,email',
        ]);

        /*
         * Show tasks with the nearest deadline first.
         * Tasks without a deadline are placed at the end.
         */
        $query
            ->orderByRaw('deadline IS NULL')
            ->orderBy('deadline')
            ->orderByDesc('id');

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Build the base query for tasks visible to the user.
     */
    private function visibleTasksQuery(User $user): Builder
    {
        $query = Task::query();

        /*
         * Employee can only see tasks assigned to them.
         */
        if ($user->isEmployee()) {
            return $query->whereHas(
                'assignments',
                function (Builder $assignmentQuery) use ($user) {
                    $assignmentQuery->where('user_id', $user->id);
                }
            );
        }

        /*
         * Manager can see tasks they created
         * or tasks they assigned to employees.
         */
        if ($user->isManager()) {
            return $query->where(function (Builder $taskQuery) use ($user) {

                $taskQuery
                    ->where('created_by', $user->id)
                    ->orWhereHas(
                        'assignments',
                        function (Builder $assignmentQuery) use ($user) {
                            $assignmentQuery->where(
                                'assigned_by',
                                $user->id
                            );
                        }
                    );
            });
        }

        /*
         * HR and Owner can see all tasks.
         */
        if ($user->isHR() || $user->isOwner()) {
            return $query;
        }

        /*
         * Unknown role -> return no tasks.
         */
        return $query->whereRaw('1 = 0');
    }

    /**
     * Make sure the user is assigned to the task.
     */
    private function ensureUserAssignedToTask(Task $task, int $userId): void
    {
        $isAssigned = TaskAssignment::query()
            ->where('task_id', $task->id)
            ->where('user_id', $userId)
            ->exists();

        if (! $isAssigned) {
            throw ValidationException::withMessages([
                'task' => __('tasks.not_assigned'),
            ]);
        }
    }

    /**
     * Ensure that the authenticated user can view the task.
     */
    private function ensureCanViewTask(Task $task, User $user): void
    {
        /*
         * HR and Owner can view all tasks.
         */
        if ($user->isHR() || $user->isOwner()) {
            return;
        }

        /*
         * Employee can only view tasks assigned to them.
         */
        if ($user->isEmployee()) {

            $isAssigned = $task->assignments()
                ->where('user_id', $user->id)
                ->exists();

            if ($isAssigned) {
                return;
            }
        }

        /*
         * Manager can view tasks they created
         * or tasks they assigned.
         */
        if ($user->isManager()) {

            if ((int) $task->created_by === (int) $user->id) {
                return;
            }

            $isAssignedByManager = $task->assignments()
                ->where('assigned_by', $user->id)
                ->exists();

            if ($isAssignedByManager) {
                return;
            }
        }

        throw new AuthorizationException(
            'You are not authorized to view this task.'
        );
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
     * Make sure the selected User is an active Employee.
     */
    private function ensureUserCanBeAssigned(User $user): void
    {
        // The user must have the Employee role.
        if ($user->role !== UserRole::Employee) {
            throw ValidationException::withMessages([
                'user_id' => __('tasks.must_be_employee'),
            ]);
        }

        // The users table uses the "status" column.
        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'user_id' => __('tasks.employee_inactive'),
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
