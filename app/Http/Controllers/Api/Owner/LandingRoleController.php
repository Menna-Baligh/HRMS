<?php

namespace App\Http\Controllers\Api\Owner;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreLandingRoleRequest;
use App\Http\Requests\Owner\UpdateLandingRoleRequest;
use App\Http\Resources\LandingRoleResource;
use App\Models\LandingRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class LandingRoleController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = LandingRole::orderBy('order')->get();

        return ResponseHelper::success(
            data: LandingRoleResource::collection($roles),
            message: __('landing.roles.retrieved')
        );
    }

    public function store(StoreLandingRoleRequest $request): JsonResponse
    {
        $role = LandingRole::create($request->validated());

        return ResponseHelper::success(
            data: new LandingRoleResource($role),
            message: __('landing.roles.created'),
            statusCode: Response::HTTP_CREATED
        );
    }

    public function update(UpdateLandingRoleRequest $request, LandingRole $role): JsonResponse
    {
        $role->update($request->validated());

        return ResponseHelper::success(
            data: new LandingRoleResource($role),
            message: __('landing.roles.updated')
        );
    }

    public function destroy(LandingRole $role): JsonResponse
    {
        $role->delete();

        return ResponseHelper::success(
            data: null,
            message: __('landing.roles.deleted')
        );
    }
}
