<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\AICareerCoachRequest;
use App\Services\AICareerCoachService;
use Illuminate\Http\JsonResponse;
use Throwable;

class AICareerCoachController extends Controller
{
    public function __construct(private AICareerCoachService $aiService) {}

    public function __invoke(AICareerCoachRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            $result = $this->aiService->getCareerCoachGuidance(
                currentUser: $user,
                targetEmployeeCode: $request->validated('employee_id'),
                period: $request->validated('period')
            );

            return ResponseHelper::success(
                $result,
                __('ai.career_coach_success')
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
