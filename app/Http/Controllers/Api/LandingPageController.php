<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\LandingPageResource;
use App\Models\LandingFeature;
use App\Models\LandingPlan;
use App\Models\LandingRole;
use App\Models\LandingSection;
use Illuminate\Http\JsonResponse;

class LandingPageController extends Controller
{
    public function index(): JsonResponse
    {
        $data = [
            'sections' => LandingSection::all(),
            'features' => LandingFeature::where('is_active', true)->orderBy('order')->get(),
            'roles' => LandingRole::where('is_active', true)->orderBy('order')->get(),
            'plans' => LandingPlan::where('is_active', true)->orderBy('order')->get(),
        ];

        return ResponseHelper::success(
            data: new LandingPageResource($data),
            message: __('landing.retrieved_successfully')
        );
    }
}
