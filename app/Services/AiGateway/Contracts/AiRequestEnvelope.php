<?php

namespace App\Services\AiGateway\Contracts;

class AiRequestEnvelope
{
    /**
     * @param  array{id: int|string, role: string}  $user
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $version,
        public string $feature,
        public array $user,
        public array $context,
        public array $metadata = []
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'feature' => $this->feature,
            'user' => [
                'id' => $this->user['id'],
                'role' => $this->user['role'],
            ],
            'context' => $this->context,
            'metadata' => $this->metadata,
        ];
    }
}
