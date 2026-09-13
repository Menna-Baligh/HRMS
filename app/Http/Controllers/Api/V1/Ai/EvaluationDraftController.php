<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\AiGateway\AiGateway;
use App\Services\AiGateway\ContextBuilders\EvaluationDraftContextBuilder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EvaluationDraftController extends Controller
{
    public function __construct(
        protected EvaluationDraftContextBuilder $contextBuilder,
        protected AiGateway $aiGateway
    ) {}

    /**
     * Generate an Evaluation Draft for an authorized manager.
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'competencies' => ['nullable', 'array'],
            'competencies.*' => ['string', 'max:100'],
        ]);

        try {
            $user = $request->user();

            $contextResult = $this->contextBuilder->build(
                managerUser: $user,
                targetEmployeeId: (int) $validated['employee_id'],
                managerNotes: [
                    'notes' => $validated['notes'] ?? null,
                    'competencies' => $validated['competencies'] ?? null,
                ]
            );

            if (! $contextResult['success']) {
                return response()->json($contextResult['fallback'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $executionResult = $this->aiGateway->execute(
                envelope: $contextResult['envelope'],
                targetEmployeeId: (int) $validated['employee_id'],
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
                message: 'Failed to generate evaluation draft.',
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
