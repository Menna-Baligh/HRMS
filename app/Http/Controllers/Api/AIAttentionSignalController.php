<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\AIAttentionSignalRequest;
use App\Services\AIAttentionSignalService;
use Illuminate\Http\JsonResponse;
use Throwable;

class AIAttentionSignalController extends Controller
{
    public function __construct(private AIAttentionSignalService $aiService) {}

    public function __invoke(AIAttentionSignalRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            $result = $this->aiService->getAttentionSignal(
                currentUser: $user,
                targetEmployeeCode: $request->validated('employee_id'),
                targetPeriod: $request->validated('target_period')
            );

            if (isset($result['status']) && $result['status'] === 'insufficient_data') {
                return ResponseHelper::success(
                    $result,
                    __('ai.insufficient_attention_data')
                );
            }

            return ResponseHelper::success(
                $result,
                __('ai.attention_signal_success')
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
