<?php

namespace App\Http\Controllers\Api;

use App\Enums\GoalStatus;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGoalRequest;
use App\Http\Requests\UpdateGoalProgressRequest;
use App\Http\Requests\UpdateGoalRequest;
use App\Http\Resources\GoalResource;
use App\Services\GoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class GoalController extends Controller
{
    public function __construct(private GoalService $goalService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (! $employee) {
                return ResponseHelper::error(null, 'Employee profile not found.', 404);
            }

            $status = $request->query('status');
            $goals = $this->goalService->getEmployeeGoals($employee, $status);

            return ResponseHelper::success(
                GoalResource::collection($goals)->response()->getData(true),
                'Employee goals retrieved successfully.'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                'Failed to retrieve goals.',
                500
            );
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (! $employee) {
                return ResponseHelper::error(null, 'Employee profile not found.', 404);
            }

            $goal = $this->goalService->getEmployeeGoalDetails($employee, $id);

            if (! $goal) {
                return ResponseHelper::error(null, 'Goal not found or access denied.', 404);
            }

            return ResponseHelper::success(
                new GoalResource($goal),
                'Goal details retrieved successfully.'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                'Failed to retrieve goal details.',
                500
            );
        }
    }
    public function store(StoreGoalRequest $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (! $employee) {
                return ResponseHelper::error(
                    null,
                    'Employee profile not found for the authenticated user.',
                    404
                );
            }

            $goal = $this->goalService->createGoal($employee, $request->validated());

            return ResponseHelper::success(
                new GoalResource($goal),
                'Goal created successfully.',
                201
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                'Failed to create goal.',
                500
            );
        }
    }
    public function update(UpdateGoalRequest $request, int $id): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (! $employee) {
                return ResponseHelper::error(null, 'Employee profile not found.', 404);
            }

            $goal = $this->goalService->getEmployeeGoalDetails($employee, $id);

            if (! $goal) {
                return ResponseHelper::error(null, 'Goal not found or access denied.', 404);
            }

            if ($goal->status === GoalStatus::COMPLETED || $goal->status === GoalStatus::CANCELLED) {
                return ResponseHelper::error(null, 'Completed or cancelled goals cannot be modified.', 422);
            }

            $updatedGoal = $this->goalService->updateGoal($goal, $request->validated());

            return ResponseHelper::success(
                new GoalResource($updatedGoal),
                'Goal details updated successfully.'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                'Failed to update goal details.',
                500
            );
        }
    }

    public function updateProgress(UpdateGoalProgressRequest $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $employee = $user->employee;

            if (! $employee) {
                return ResponseHelper::error(null, 'Employee profile not found.', 404);
            }

            $goal = $this->goalService->getEmployeeGoalDetails($employee, $id);

            if (! $goal) {
                return ResponseHelper::error(null, 'Goal not found or access denied.', 404);
            }

            if ($goal->status === GoalStatus::CANCELLED) {
                return ResponseHelper::error(null, 'Cannot update progress for a cancelled goal.', 422);
            }

            $updatedGoal = $this->goalService->updateProgress(
                $goal,
                (float) $request->validated('current_value'),
                $user->id,
                $request->validated('note')
            );

            return ResponseHelper::success(
                new GoalResource($updatedGoal),
                'Goal progress updated successfully.'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                'Failed to update goal progress.',
                500
            );
        }
    }

    public function complete(Request $request, int $id): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (! $employee) {
                return ResponseHelper::error(null, 'Employee profile not found.', 404);
            }

            $goal = $this->goalService->getEmployeeGoalDetails($employee, $id);

            if (! $goal) {
                return ResponseHelper::error(null, 'Goal not found or access denied.', 404);
            }

            if ($goal->status === GoalStatus::COMPLETED) {
                return ResponseHelper::error(null, 'Goal is already marked as completed.', 422);
            }

            $completedGoal = $this->goalService->markAsCompleted($goal);

            return ResponseHelper::success(
                new GoalResource($completedGoal),
                'Goal marked as completed successfully.'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                'Failed to mark goal as completed.',
                500
            );
        }
    }
}