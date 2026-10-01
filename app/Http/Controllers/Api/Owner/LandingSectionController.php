<?php

namespace App\Http\Controllers\Api\Owner;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\UpdateLandingSectionRequest;
use App\Models\LandingSection;
use Illuminate\Http\JsonResponse;

class LandingSectionController extends Controller
{
    public function index(): JsonResponse
    {
        $sections = LandingSection::all();

        return ResponseHelper::success(
            data: $sections,
            message: __('landing.sections_retrieved')
        );
    }

    public function update(UpdateLandingSectionRequest $request, string $key): JsonResponse
    {
        $section = LandingSection::where('section_key', $key)->firstOrFail();

        $section->update([
            'content' => $request->validated('content'),
        ]);

        return ResponseHelper::success(
            data: $section,
            message: __('landing.section_updated')
        );
    }
}
