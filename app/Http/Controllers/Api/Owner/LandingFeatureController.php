<?php

namespace App\Http\Controllers\Api\Owner;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreLandingFeatureRequest;
use App\Http\Requests\Owner\UpdateLandingFeatureRequest;
use App\Http\Resources\LandingFeatureResource;
use App\Models\LandingFeature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class LandingFeatureController extends Controller
{
    public function index(): JsonResponse
    {
        $features = LandingFeature::orderBy('order')->get();

        return ResponseHelper::success(
            data: LandingFeatureResource::collection($features),
            message: __('landing.features.retrieved')
        );
    }

    public function store(StoreLandingFeatureRequest $request): JsonResponse
    {
        $feature = LandingFeature::create($request->validated());

        return ResponseHelper::success(
            data: new LandingFeatureResource($feature),
            message: __('landing.features.created'),
            statusCode: Response::HTTP_CREATED
        );
    }

    public function update(UpdateLandingFeatureRequest $request, LandingFeature $feature): JsonResponse
    {
        $feature->update($request->validated());

        return ResponseHelper::success(
            data: new LandingFeatureResource($feature),
            message: __('landing.features.updated')
        );
    }

    public function destroy(LandingFeature $feature): JsonResponse
    {
        $feature->delete();

        return ResponseHelper::success(
            data: null,
            message: __('landing.features.deleted')
        );
    }
}
