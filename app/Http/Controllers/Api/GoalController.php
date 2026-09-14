<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGoalRequest;
use App\Http\Resources\GoalResource;
use App\Services\GoalService;
use Illuminate\Http\JsonResponse;
use Throwable;

class GoalController extends Controller
{
    public function __construct(private GoalService $goalService) {}

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