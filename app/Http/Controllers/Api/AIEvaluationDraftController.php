<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\AIEvaluationDraftRequest;
use App\Services\AIEvaluationDraftService;
use Illuminate\Http\JsonResponse;
use Throwable;

class AIEvaluationDraftController extends Controller
{
    public function __construct(private AIEvaluationDraftService $aiService) {}

    public function __invoke(AIEvaluationDraftRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            $result = $this->aiService->generateEvaluationDraft(
                currentUser: $user,
                targetEmployeeCode: $request->validated('employee_id'),
                period: $request->validated('period'),
                evaluationScores: $request->validated('evaluation_scores'),
                managerNotes: $request->validated('manager_notes')
            );

            if (isset($result['status']) && $result['status'] === 'insufficient_data') {
                return ResponseHelper::success(
                    $result,
                    __('ai.insufficient_evaluation_data')
                );
            }

            return ResponseHelper::success(
                $result,
                __('ai.evaluation_draft_success')
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
