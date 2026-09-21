<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\AIPolicyAssistantRequest;
use App\Services\AIPolicyAssistantService;
use Illuminate\Http\JsonResponse;
use Throwable;

class AIPolicyAssistantController extends Controller
{
    public function __construct(private AIPolicyAssistantService $aiService) {}

    public function __invoke(AIPolicyAssistantRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            $result = $this->aiService->askPolicyQuestion(
                currentUser: $user,
                targetEmployeeCode: $request->validated('employee_id'),
                question: $request->validated('question'),
                sessionId: $request->validated('session_id')
            );

            if (isset($result['status']) && $result['status'] === 'unsupported') {
                return ResponseHelper::success(
                    $result,
                    __('ai.policy_unsupported_question')
                );
            }

            return ResponseHelper::success(
                $result,
                __('ai.policy_assistant_success')
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
