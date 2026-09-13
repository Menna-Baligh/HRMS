<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\StoreTaskRequest;
use App\Services\Tasks\TaskService;

class TaskController extends Controller
{
    public function __construct(
        private TaskService $taskService
    ) {}

    public function store(StoreTaskRequest $request)
    {
        $task = $this->taskService->create($request->validated());

        return response()->json([
            'message' => 'Task created successfully.',
            'data' => $task,
        ], 201);
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $task = $this->taskService->update(
            $task,
            $request->validated()
        );

        return response()->json([
            'message' => 'Task updated successfully.',
            'data' => $task,
        ]);
    }

    public function assign(AssignTaskRequest $request, Task $task): JsonResponse
    {
        $assignment = $this->taskService->assign(
            $task,
            $request->integer('employee_id')
        );

        return response()->json([
            'message' => 'Task assigned successfully.',
            'data' => $assignment,
        ], 201);
    }

    /**
     * Update task progress.
     */
    public function updateProgress(UpdateTaskProgressRequest $request, Task $task): JsonResponse
    {
        $task = $this->taskService->updateProgress(
            $task,
            $request->integer('progress')
        );

        return response()->json([
            'message' => 'Task progress updated successfully.',
            'data' => $task,
        ]);
    }

    /**
     * Update task status.
     */
    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): JsonResponse
    {
        $task = $this->taskService->updateStatus(
            $task,
            TaskStatus::from(
                $request->string('status')->toString()
            )
        );

        return response()->json([
            'message' => 'Task status updated successfully.',
            'data' => $task,
        ]);
    }
}
