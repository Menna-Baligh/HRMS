<?php

namespace App\Http\Controllers\Api;

use App\Enums\TaskStatus;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\AssignTaskRequest;
use App\Http\Requests\Tasks\StoreTaskRequest;
use App\Http\Requests\Tasks\TaskIndexRequest;
use App\Http\Requests\Tasks\UpdateTaskProgressRequest;
use App\Http\Requests\Tasks\UpdateTaskRequest;
use Illuminate\Http\Request;
use App\Http\Requests\Tasks\UpdateTaskStatusRequest;
use App\Models\Task;
use App\Services\Tasks\TaskService;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    public function __construct(
        private TaskService $taskService
    ) {

    }
    /**
 * Get tasks visible to the authenticated user.
 */
public function index(TaskIndexRequest $request)
{
    $tasks = $this->taskService->index(
        user: $request->user(),
        filters: $request->validated(),
    );

    return ResponseHelper::success(
        data: $tasks,
        message: __('tasks.retrieved_successfully')
    );
}

    public function store(StoreTaskRequest $request)
    {
        $task = $this->taskService->create($request->validated());

        return ResponseHelper::success( 
            data: $task,
            message: __('tasks.created'),
            statusCode: 201 
            );
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $task = $this->taskService->update(
            $task,
            $request->validated()
        );

        return ResponseHelper::success(
             data: $task,
              message: __('tasks.updated')
             );
    }

    public function assign(AssignTaskRequest $request, Task $task)
     {
         $assignment = $this->taskService->assign( $task, $request->integer('user_id')
         ); 
         return ResponseHelper::success( 
            data: $assignment,
             message: __('tasks.assigned'),
              statusCode: 201 );
             }

    /**
     * Update task progress.
     */
    public function updateProgress(UpdateTaskProgressRequest $request, Task $task)
    {
        $task = $this->taskService->updateProgress(
            $task,
            $request->integer('progress')
        );

        return ResponseHelper::success(
            data: $task,
            message: __('tasks.progress_updated')
        );
    }
    /**
     * Update task status.
     */
    public function updateStatus(UpdateTaskStatusRequest $request,Task $task)
     {
        $task = $this->taskService->updateStatus(
            $task,
            TaskStatus::from($request->validated('status'))
        );
    
        return ResponseHelper::success(
            data: $task,
            message: __('tasks.status_updated')
        );
    }
        /**
     * Get task details.
     */
     public function show(Request $request, Task $task): JsonResponse
     {
         $task = $this->taskService->show(
             task: $task,
             user: $request->user(),
         );
     
         return ResponseHelper::success(
             data: $task,
             message: __('tasks.details_retrieved_successfully')
            );
     }
   /**
    * Get task activity history.
    */
   public function activities(Request $request, Task $task): JsonResponse
   {
       $activities = $this->taskService->activities(
           task: $task,
           user: $request->user(),
       );

       return ResponseHelper::success(
           data: $activities,
           message: __('tasks.activities_retrieved_successfully')
        );
   }
   }
