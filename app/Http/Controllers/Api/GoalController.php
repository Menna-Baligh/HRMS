<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGoalRequest;
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
}