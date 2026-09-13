<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\AiGateway\AiGateway;
use App\Services\AiGateway\ContextBuilders\PerformanceInsightContextBuilder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PerformanceInsightController extends Controller
{
    public function __construct(
        protected PerformanceInsightContextBuilder $contextBuilder,
        protected AiGateway $aiGateway
    ) {}

    /**
     * Generate Performance Insight for authorized user / employee over requested period.
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['nullable', 'integer'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        try {
            $user = $request->user();
            $targetId = isset($validated['employee_id']) ? (int) $validated['employee_id'] : null;

            $contextResult = $this->contextBuilder->build(
                authUser: $user,
                targetEmployeeId: $targetId,
                fromDate: $validated['from_date'] ?? null,
                toDate: $validated['to_date'] ?? null
            );

            if (! $contextResult['success']) {
                return response()->json($contextResult['fallback'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $executionResult = $this->aiGateway->execute(
                envelope: $contextResult['envelope'],
                targetEmployeeId: $contextResult['envelope']->metadata['target_employee_id'] ?? null,
                persistGeneration: true
            );

            $status = $executionResult['status'] ?? 'error';
            $httpCode = match ($status) {
                'success' => Response::HTTP_OK,
                'ai_timeout' => Response::HTTP_GATEWAY_TIMEOUT,
                'invalid_ai_response' => Response::HTTP_UNPROCESSABLE_ENTITY,
                default => Response::HTTP_BAD_GATEWAY,
            };

            return response()->json($executionResult, $httpCode);
        } catch (AuthorizationException $e) {
            return ResponseHelper::error(
                message: $e->getMessage(),
                statusCode: Response::HTTP_FORBIDDEN
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: 'Failed to generate performance insight.',
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
