<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\AITeamInsightRequest;
use App\Services\AITeamInsightService;
use Illuminate\Http\JsonResponse;
use Throwable;

class AITeamInsightController extends Controller
{
    public function __construct(private AITeamInsightService $aiService) {}

    public function __invoke(AITeamInsightRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            $result = $this->aiService->getTeamInsight(
                currentUser: $user,
                departmentName: $request->validated('department'),
                period: $request->validated('period')
            );

            if (isset($result['status']) && $result['status'] === 'insufficient_data') {
                return ResponseHelper::success(
                    $result,
                    __('ai.insufficient_team_data')
                );
            }

            return ResponseHelper::success(
                $result,
                __('ai.team_insight_success')
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
