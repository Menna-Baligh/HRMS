<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LandingPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sections = $this['sections']->pluck('content', 'section_key');

        return [
            'hero' => $sections->get('hero'),
            'about' => $sections->get('about'),
            'ai_insights' => $sections->get('ai_insights'),
            'global_stats' => $sections->get('global_stats'),
            'cta' => $sections->get('cta'),
            'footer' => $sections->get('footer'),
            'features' => LandingFeatureResource::collection($this['features']),
            'roles' => LandingRoleResource::collection($this['roles']),
            'plans' => LandingPlanResource::collection($this['plans']),
        ];
    }
}
