<?php

namespace App\Services\AiGateway\Contracts;

class AiProviderResponse
{
    /**
     * @param  array<string, mixed>|null  $data
     */
    public function __construct(
        public bool $success,
        public ?array $data = null,
        public ?string $errorMessage = null,
        public ?string $errorCode = null,
        public bool $timeout = false,
        public bool $retryable = false,
        public int $durationMs = 0,
        public ?int $tokensUsed = null
    ) {}

    public static function successful(array $data, int $durationMs = 120, ?int $tokensUsed = 150): self
    {
        return new self(
            success: true,
            data: $data,
            durationMs: $durationMs,
            tokensUsed: $tokensUsed
        );
    }

    public static function timedOut(int $durationMs = 5000): self
    {
        return new self(
            success: false,
            errorMessage: 'The AI service is temporarily unavailable.',
            errorCode: 'AI_TIMEOUT',
            timeout: true,
            retryable: true,
            durationMs: $durationMs
        );
    }

    public static function failed(string $message, string $errorCode = 'AI_PROVIDER_ERROR', bool $retryable = false, int $durationMs = 50): self
    {
        return new self(
            success: false,
            errorMessage: $message,
            errorCode: $errorCode,
            retryable: $retryable,
            durationMs: $durationMs
        );
    }
}
