<?php

namespace App\Services\AiGateway\Logging;

use App\Models\AiUsageLog;
use Throwable;

class AiUsageLogger
{
    /**
     * Keys that must never be recorded in audit logs.
     */
    protected const REDACTED_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'access_token',
        'refresh_token',
        'api_key',
        'secret',
        'authorization',
        'bearer',
        'ssn',
        'credit_card',
        'phone',
        'address',
    ];

    /**
     * Record an AI usage event.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        ?int $userId,
        string $feature,
        string $status,
        array $metadata = [],
        ?int $durationMs = null,
        ?int $tokensUsed = null
    ): ?AiUsageLog {
        try {
            $sanitizedMetadata = $this->sanitize($metadata);

            return AiUsageLog::create([
                'user_id' => $userId,
                'feature' => $feature,
                'status' => $status,
                'request_time' => now(),
                'response_duration_ms' => $durationMs,
                'tokens_used' => $tokensUsed,
                'metadata' => $sanitizedMetadata,
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Recursively sanitize data to remove secrets, tokens, passwords, and sensitive keys.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function sanitize(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);

            if ($this->isForbiddenKey($lowerKey)) {
                $sanitized[$key] = '[REDACTED]';

                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize($value);
            } elseif (is_string($value)) {
                // If string looks like a JWT or Bearer token, redact it
                if (str_starts_with($value, 'eyJ') || str_starts_with(strtolower($value), 'bearer ')) {
                    $sanitized[$key] = '[REDACTED_TOKEN]';
                } else {
                    $sanitized[$key] = $value;
                }
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    protected function isForbiddenKey(string $key): bool
    {
        foreach (self::REDACTED_KEYS as $forbidden) {
            if (str_contains($key, $forbidden)) {
                return true;
            }
        }

        return false;
    }
}
