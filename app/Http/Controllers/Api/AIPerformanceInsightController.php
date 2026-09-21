<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\AIPerformanceInsightRequest;
use App\Services\AIPerformanceInsightService;
use Illuminate\Http\JsonResponse;
use Throwable;

class AIPerformanceInsightController extends Controller
{
    public function __construct(private AIPerformanceInsightService $aiService) {}

    public function __invoke(AIPerformanceInsightRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            $result = $this->aiService->getPerformanceInsight(
                currentUser: $user,
                targetEmployeeCode: $request->validated('employee_id'),
                period: $request->validated('period')
            );

            if (isset($result['status']) && $result['status'] === 'insufficient_data') {
                return ResponseHelper::success(
                    $result,
                    __('ai.insufficient_performance_data')
                );
            }

            return ResponseHelper::success(
                $result,
                __('ai.performance_insight_success')
            );
        } catch (Throwable $e) {
            report($e);

            $statusCode = in_array($e->getCode(), [403, 404, 422, 500]) ? $e->getCode() : 500;

            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                $e->getMessage() ?: __('ai.service_failed'),
                $statusCode
            );
        }
    }
}
