<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    
    public function toArray(Request $request): array
    {
        $locale = $request->query('lang') 
            ?? $request->query('locale') 
            ?? $request->header('Accept-Language', config('app.locale', 'en'));

        $locale = in_array($locale, ['ar', 'en']) ? $locale : 'en';

        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => __($this->title_key, $this->parameters ?? [], $locale),
            'body' => __($this->body_key, $this->parameters ?? [], $locale),
            'is_read' => (bool) $this->is_read,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}