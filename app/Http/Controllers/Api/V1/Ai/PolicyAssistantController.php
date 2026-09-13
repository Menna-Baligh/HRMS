<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\AiGateway\AiGateway;
use App\Services\AiGateway\ContextBuilders\PolicyAssistantContextBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class PolicyAssistantController extends Controller
{
    public function __construct(
        protected PolicyAssistantContextBuilder $contextBuilder,
        protected AiGateway $aiGateway
    ) {}

    /**
     * Get authorized Policy Assistant context envelope.
     */
    public function context(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $query = $request->query('query');

            $result = $this->contextBuilder->build($user, $query);

            if (! $result['success']) {
                return response()->json($result['fallback'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return ResponseHelper::success(
                data: $result['envelope']->toArray(),
                message: 'Policy assistant context built successfully.'
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: 'Failed to build policy assistant context.',
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Ask a question to the Policy Assistant.
     */
    public function ask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        try {
            $user = $request->user();
            $contextResult = $this->contextBuilder->build($user, $validated['query']);

            if (! $contextResult['success']) {
                return response()->json($contextResult['fallback'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $executionResult = $this->aiGateway->execute(
                envelope: $contextResult['envelope'],
                targetEmployeeId: $user->employee?->id,
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
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: 'Failed to process policy assistant query.',
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
