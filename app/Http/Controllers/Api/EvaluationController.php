<?php

namespace App\Http\Controllers\Api;

use App\Enums\EvaluationStatus;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEvaluationRequest;
use App\Http\Requests\UpdateEvaluationRequest;
use App\Http\Resources\EvaluationResource;
use App\Models\Evaluation;
use App\Services\EvaluationService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class EvaluationController extends Controller
{
    public function __construct(private EvaluationService $evaluationService) {}

    public function store(StoreEvaluationRequest $request): JsonResponse
    {
        try {
            $evaluator = $request->user();

            $evaluation = $this->evaluationService->saveDraft($evaluator->id, $request->validated());

            return ResponseHelper::success(
                new EvaluationResource($evaluation),
                'Evaluation draft created successfully.',
                201
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                $e->getMessage() ?: 'Failed to save evaluation draft.',
                400
            );
        }
    }

    public function update(UpdateEvaluationRequest $request, int $id): JsonResponse
    {
        try {
            $evaluation = Evaluation::with(['scores', 'evidence'])->find($id);

            if (! $evaluation) {
                return ResponseHelper::error(null, 'Evaluation not found.', 404);
            }

            if ($evaluation->status === EvaluationStatus::COMPLETED) {
                return ResponseHelper::error(null, 'Completed evaluations cannot be modified.', 422);
            }

            $updatedEvaluation = $this->evaluationService->saveDraft(
                $request->user()->id,
                $request->validated(),
                $evaluation
            );

            return ResponseHelper::success(
                new EvaluationResource($updatedEvaluation),
                'Evaluation draft updated successfully.'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                $e->getMessage() ?: 'Failed to update evaluation.',
                400
            );
        }
    }

    public function complete(Request $request, int $id, NotificationService $notificationService): JsonResponse
    {
        try {
            $evaluation = Evaluation::with(['scores.category', 'employee.user', 'period'])->find($id);

            if (! $evaluation) {
                return ResponseHelper::error(null, 'Evaluation not found.', 404);
            }

            $completedEvaluation = $this->evaluationService->completeEvaluation($evaluation);

            if ($evaluation->employee && $evaluation->employee->user) {
                $notificationService->send(
                    user: $evaluation->employee->user,
                    type: 'evaluation_closed',
                    titleKey: 'notifications.evaluation_closed_title',
                    bodyKey: 'notifications.evaluation_closed_body',
                    parameters: [
                        'period_name' => $evaluation->period?->name ?? 'the evaluation period',
                    ],
                    metadata: [
                        'screen' => 'evaluation_summary',
                        'evaluation_id' => $completedEvaluation->id,
                        'period_id' => $completedEvaluation->evaluation_period_id,
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ]
                );
            }

            return ResponseHelper::success(
                new EvaluationResource($completedEvaluation),
                'Evaluation completed and locked successfully.'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                $e->getMessage() ?: 'Failed to complete evaluation.',
                400
            );
        }
    }

    public function managerEvaluations(Request $request): JsonResponse
    {
        try {
            $manager = $request->user()->employee;

            if (! $manager) {
                return ResponseHelper::error(null, 'Employee profile not found.', 404);
            }

            $status = $request->query('status');
            $periodId = $request->query('period_id') ? (int) $request->query('period_id') : null;

            $evaluations = $this->evaluationService->getManagerTeamEvaluations($manager, $status, $periodId);

            return ResponseHelper::success(
                EvaluationResource::collection($evaluations)->response()->getData(true),
                'Team evaluations retrieved successfully.'
            );
        } catch (Throwable $e) {
            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                'Failed to retrieve team evaluations.',
                500
            );
        }
    }
}
