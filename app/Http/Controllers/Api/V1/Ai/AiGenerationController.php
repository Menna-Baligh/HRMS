<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\AiFeedback;
use App\Models\AiGeneration;
use App\Services\AiGateway\AiGateway;
use App\Services\AiGateway\Contracts\AiRequestEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AiGenerationController extends Controller
{
    public function __construct(
        protected AiGateway $aiGateway
    ) {}

    /**
     * List authorized generations for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = AiGeneration::with('feedbacks')
            ->latest('id');

        if (! $user->isOwner() && ! $user->isHR()) {
            $query->where('user_id', $user->id);
        }

        $generations = $query->paginate(15);

        return ResponseHelper::success(
            data: $generations,
            message: 'Generations retrieved successfully.'
        );
    }

    /**
     * Regenerate an AI generation into a new version.
     */
    public function regenerate(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $generation = AiGeneration::find($id);
        if (! $generation) {
            return ResponseHelper::error(
                message: 'AI generation not found.',
                statusCode: Response::HTTP_NOT_FOUND
            );
        }

        // Authorization check: User must own the generation or be HR/Owner
        if ($generation->user_id !== $user->id && ! $user->isOwner() && ! $user->isHR()) {
            return ResponseHelper::error(
                message: 'You are not authorized to regenerate this generation.',
                statusCode: Response::HTTP_FORBIDDEN
            );
        }

        try {
            $storedEnvelope = $generation->request_envelope ?? [];

            $envelope = new AiRequestEnvelope(
                version: '1.0',
                feature: $generation->feature,
                user: [
                    'id' => $user->id,
                    'role' => $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role,
                ],
                context: $storedEnvelope['context'] ?? [],
                metadata: [
                    'regenerated_from_id' => $generation->id,
                    'target_employee_id' => $generation->target_employee_id,
                ]
            );

            // Execute with reference to previous generation to increment version
            $executionResult = $this->aiGateway->execute(
                envelope: $envelope,
                targetEmployeeId: $generation->target_employee_id,
                persistGeneration: true,
                regeneratedFrom: $generation
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
                message: 'Failed to regenerate AI output.',
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Submit feedback for an authorized AI generation.
     */
    public function feedback(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'in:useful,not_useful,flagged'],
            'comments' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        $generation = AiGeneration::find($id);
        if (! $generation) {
            return ResponseHelper::error(
                message: 'AI generation not found.',
                statusCode: Response::HTTP_NOT_FOUND
            );
        }

        // Authorization check: An employee must not be able to submit feedback for another user's restricted generation
        if ($generation->user_id !== $user->id && ! $user->isOwner() && ! $user->isHR()) {
            return ResponseHelper::error(
                message: 'You are not authorized to submit feedback for this generation.',
                statusCode: Response::HTTP_FORBIDDEN
            );
        }

        $feedback = AiFeedback::create([
            'ai_generation_id' => $generation->id,
            'user_id' => $user->id,
            'rating' => $validated['rating'],
            'comments' => $validated['comments'] ?? null,
        ]);

        return ResponseHelper::success(
            data: $feedback,
            message: 'Feedback submitted successfully.',
            statusCode: Response::HTTP_CREATED
        );
    }
}
