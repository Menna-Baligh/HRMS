<?php

namespace App\Http\Controllers\Api;

use App\Enums\EvaluationPeriodStatus;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEvaluationCategoryRequest;
use App\Http\Requests\StoreEvaluationPeriodRequest;
use App\Http\Resources\EvaluationCategoryResource;
use App\Http\Resources\EvaluationPeriodResource;
use App\Models\EvaluationCategory;
use App\Models\EvaluationPeriod;
use Illuminate\Http\JsonResponse;
use Throwable;

class HrEvaluationSetupController extends Controller
{
    public function listPeriods(): JsonResponse
    {
        try {
            $periods = EvaluationPeriod::latest()->paginate(10);
            return ResponseHelper::success(EvaluationPeriodResource::collection($periods)->response()->getData(true), 'Evaluation periods retrieved successfully.');
        } catch (Throwable $e) {
            return ResponseHelper::error(null, 'Failed to retrieve periods.', 500);
        }
    }

    public function storePeriod(StoreEvaluationPeriodRequest $request): JsonResponse
    {
        try {
            $period = EvaluationPeriod::create($request->validated());
            return ResponseHelper::success(new EvaluationPeriodResource($period), 'Evaluation period created successfully.', 201);
        } catch (Throwable $e) {
            return ResponseHelper::error(null, 'Failed to create period.', 500);
        }
    }

    public function togglePeriodStatus(int $id): JsonResponse
    {
        try {
            $period = EvaluationPeriod::find($id);
            if (! $period) {
                return ResponseHelper::error(null, 'Period not found.', 404);
            }

            $newStatus = $period->status === EvaluationPeriodStatus::ACTIVE
                ? EvaluationPeriodStatus::CLOSED
                : EvaluationPeriodStatus::ACTIVE;

            $period->update(['status' => $newStatus]);

            return ResponseHelper::success(new EvaluationPeriodResource($period), "Evaluation period status changed to {$newStatus->value}.");
        } catch (Throwable $e) {
            return ResponseHelper::error(null, 'Failed to update period status.', 500);
        }
    }

    public function listCategories(): JsonResponse
    {
        try {
            $categories = EvaluationCategory::latest()->paginate(10);
            return ResponseHelper::success(EvaluationCategoryResource::collection($categories)->response()->getData(true), 'Evaluation categories retrieved successfully.');
        } catch (Throwable $e) {
            return ResponseHelper::error(null, 'Failed to retrieve categories.', 500);
        }
    }

    public function storeCategory(StoreEvaluationCategoryRequest $request): JsonResponse
    {
        try {
            $category = EvaluationCategory::create($request->validated());
            return ResponseHelper::success(new EvaluationCategoryResource($category), 'Evaluation category created successfully.', 201);
        } catch (Throwable $e) {
            return ResponseHelper::error(null, 'Failed to create category.', 500);
        }
    }
}
