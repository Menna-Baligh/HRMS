<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\AiGateway\AiGateway;
use App\Services\AiGateway\ContextBuilders\CareerCoachContextBuilder;
use App\Services\AiGateway\Contracts\CareerCoachOutputContract;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CareerCoachController extends Controller
{
    public function __construct(
        protected CareerCoachContextBuilder $contextBuilder,
        protected AiGateway $aiGateway
    ) {}

    /**
     * Get authorized Career Coach context envelope.
     */
    public function context(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $targetId = $request->query('employee_id') ? (int) $request->query('employee_id') : null;

            $result = $this->contextBuilder->build($user, $targetId);

            if (! $result['success']) {
                return response()->json($result['fallback'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return ResponseHelper::success(
                data: $result['envelope']->toArray(),
                message: 'Career coach context built successfully.'
            );
        } catch (AuthorizationException $e) {
            return ResponseHelper::error(
                message: $e->getMessage(),
                statusCode: Response::HTTP_FORBIDDEN
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: 'Failed to build career coach context.',
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Generate Career Coach suggestions via the AI Gateway.
     */
    public function generate(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $targetId = $request->input('employee_id') ? (int) $request->input('employee_id') : null;

            $contextResult = $this->contextBuilder->build($user, $targetId);

            if (! $contextResult['success']) {
                return response()->json($contextResult['fallback'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $executionResult = $this->aiGateway->execute(
                envelope: $contextResult['envelope'],
                targetEmployeeId: $contextResult['envelope']->metadata['target_employee_id'] ?? null,
                persistGeneration: true
            );

            // Handle non-success gateway results (timeout, invalid response, error)
            if (empty($executionResult['success'])) {
                $status = $executionResult['status'] ?? 'error';
                $httpCode = match ($status) {
                    'ai_timeout' => Response::HTTP_GATEWAY_TIMEOUT,
                    'invalid_ai_response' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    default => Response::HTTP_BAD_GATEWAY,
                };

                return response()->json($executionResult, $httpCode);
            }

            // Validate AI output against the contract
            try {
                $contract = CareerCoachOutputContract::fromArray(
                    $executionResult['data'] ?? []
                );
            } catch (\InvalidArgumentException $e) {
                return ResponseHelper::error(
                    message: $e->getMessage(),
                    statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            return ResponseHelper::success(
                data: $contract->toArray(),
                message: 'Career coach recommendations generated successfully.'
            );
        } catch (AuthorizationException $e) {
            return ResponseHelper::error(
                message: $e->getMessage(),
                statusCode: Response::HTTP_FORBIDDEN
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: 'Failed to execute career coach AI generation.',
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
