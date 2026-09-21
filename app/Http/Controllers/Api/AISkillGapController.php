<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\AISkillGapRequest;
use App\Services\AISkillGapService;
use Illuminate\Http\JsonResponse;
use Throwable;

class AISkillGapController extends Controller
{
    public function __construct(private AISkillGapService $aiService) {}

    public function __invoke(AISkillGapRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            $result = $this->aiService->getSkillGapAnalysis(
                currentUser: $user,
                targetEmployeeCode: $request->validated('employee_id'),
                period: $request->validated('period'),
                targetRole: $request->validated('target_role'),
                targetSkills: $request->validated('target_skills')
            );

            if (isset($result['status']) && $result['status'] === 'insufficient_data') {
                return ResponseHelper::success(
                    $result,
                    __('ai.insufficient_skill_data')
                );
            }

            return ResponseHelper::success(
                $result,
                __('ai.skill_gap_success')
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
