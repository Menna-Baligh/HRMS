<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\EvaluationResource;
use App\Services\EvaluationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class EmployeeEvaluationController extends Controller
{
    public function __construct(private EvaluationService $evaluationService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $employee = $request->user()->employee;

            if (! $employee) {
                return ResponseHelper::error(null, 'Employee profile not found.', 404);
            }

            $evaluations = $this->evaluationService->getEmployeeEvaluationsHistory($employee, 10);

            return ResponseHelper::success(
                EvaluationResource::collection($evaluations)->response()->getData(true),
                'Completed evaluation history retrieved successfully.'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                'Failed to retrieve evaluation history.',
                500
            );
        }
    }
}
