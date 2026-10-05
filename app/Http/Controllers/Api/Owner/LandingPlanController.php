<?php

namespace App\Http\Controllers\Api\Owner;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreLandingPlanRequest;
use App\Http\Requests\Owner\UpdateLandingPlanRequest;
use App\Http\Resources\LandingPlanResource;
use App\Models\LandingPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class LandingPlanController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = LandingPlan::orderBy('order')->get();

        return ResponseHelper::success(
            data: LandingPlanResource::collection($plans),
            message: __('landing.plans.retrieved')
        );
    }

    public function store(StoreLandingPlanRequest $request): JsonResponse
    {
        $plan = LandingPlan::create($request->validated());

        return ResponseHelper::success(
            data: new LandingPlanResource($plan),
            message: __('landing.plans.created'),
            statusCode: Response::HTTP_CREATED
        );
    }

    public function update(UpdateLandingPlanRequest $request, LandingPlan $plan): JsonResponse
    {
        $plan->update($request->validated());

        return ResponseHelper::success(
            data: new LandingPlanResource($plan),
            message: __('landing.plans.updated')
        );
    }

    public function destroy(LandingPlan $plan): JsonResponse
    {
        $plan->delete();

        return ResponseHelper::success(
            data: null,
            message: __('landing.plans.deleted')
        );
    }
}
