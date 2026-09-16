<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    
    public function toArray(Request $request): array
    {
        $title = __($this->title_key, $this->parameters ?? []);
        $body = __($this->body_key, $this->parameters ?? []);

        return [
            'id'         => $this->id,
            'type'       => $this->type,
            'title'      => $title,
            'body'       => $body,
            'title_key'  => $this->title_key,
            'body_key'   => $this->body_key,
            'parameters' => $this->parameters,
            'is_read'    => $this->is_read,
            'metadata'   => $this->metadata,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}