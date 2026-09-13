<?php

namespace App\Services\AiGateway;

use App\Models\AiGeneration;
use App\Services\AiGateway\Contracts\AiProviderInterface;
use App\Services\AiGateway\Contracts\AiRequestEnvelope;
use App\Services\AiGateway\Logging\AiUsageLogger;
use App\Services\AiGateway\Validation\AiResponseValidator;

class AiGateway
{
    public function __construct(
        protected AiProviderInterface $provider,
        protected AiResponseValidator $validator,
        protected AiUsageLogger $logger
    ) {}

    /**
     * Get the active provider instance.
     */
    public function getProvider(): AiProviderInterface
    {
        return $this->provider;
    }

    /**
     * Execute an AI request envelope through the full gateway lifecycle.
     *
     * @return array<string, mixed>
     */
    public function execute(
        AiRequestEnvelope $envelope,
        ?int $targetEmployeeId = null,
        bool $persistGeneration = true,
        ?AiGeneration $regeneratedFrom = null
    ): array {
        $userId = $envelope->user['id'] ?? null;
        $feature = $envelope->feature;

        // 1. Send to AI Provider
        $response = $this->provider->generate($envelope);

        // 2. Handle AI Provider Timeout
        if ($response->timeout) {
            $this->logger->log(
                userId: $userId,
                feature: $feature,
                status: 'timeout',
                metadata: [
                    'error' => $response->errorMessage,
                    'retryable' => true,
                ],
                durationMs: $response->durationMs
            );

            return [
                'success' => false,
                'status' => 'ai_timeout',
                'retryable' => true,
                'message' => 'The AI service is temporarily unavailable.',
            ];
        }

        // 3. Handle Provider Failure
        if (! $response->success) {
            $this->logger->log(
                userId: $userId,
                feature: $feature,
                status: 'error',
                metadata: [
                    'error_code' => $response->errorCode,
                    'error_message' => $response->errorMessage,
                ],
                durationMs: $response->durationMs
            );

            return [
                'success' => false,
                'status' => 'ai_error',
                'retryable' => $response->retryable,
                'message' => $response->errorMessage ?? 'Failed to process AI request.',
            ];
        }

        // 4. Validate Response Schema Against Contract
        $validation = $this->validator->validate($feature, $response->data);

        if (! $validation['valid']) {
            $this->logger->log(
                userId: $userId,
                feature: $feature,
                status: 'invalid_response',
                metadata: [
                    'validation_error' => $validation['error_message'],
                    'received_keys' => array_keys($response->data ?? []),
                ],
                durationMs: $response->durationMs
            );

            return [
                'success' => false,
                'status' => 'invalid_ai_response',
                'error_code' => 'AI_INVALID_RESPONSE',
                'message' => 'The AI response does not match the expected contract.',
                'details' => $validation['error_message'],
            ];
        }

        // 5. Compute Context Reference (SHA-256 hash of sanitized context)
        $contextHash = hash('sha256', json_encode($envelope->context, JSON_THROW_ON_ERROR));

        // 6. Persist Generation If Requested
        $generation = null;
        if ($persistGeneration && $userId) {
            $version = 1;
            if ($regeneratedFrom !== null) {
                $version = $regeneratedFrom->version + 1;
            }

            $generation = AiGeneration::create([
                'user_id' => $userId,
                'target_employee_id' => $targetEmployeeId,
                'feature' => $feature,
                'version' => $version,
                'context_reference' => $contextHash,
                'regenerated_from_id' => $regeneratedFrom?->id,
                'request_envelope' => $envelope->toArray(),
                'output_payload' => $response->data,
                'status' => 'success',
            ]);
        }

        // 7. Audit Logging
        $this->logger->log(
            userId: $userId,
            feature: $feature,
            status: 'success',
            metadata: [
                'generation_id' => $generation?->id,
                'version' => $generation?->version ?? 1,
                'context_reference' => $contextHash,
            ],
            durationMs: $response->durationMs,
            tokensUsed: $response->tokensUsed
        );

        // 8. Return Standardized Response
        return [
            'success' => true,
            'status' => 'success',
            'data' => $response->data,
            'metadata' => [
                'generation_id' => $generation?->id,
                'version' => $generation?->version ?? 1,
                'regenerated_from_id' => $generation?->regenerated_from_id,
                'context_reference' => $contextHash,
                'duration_ms' => $response->durationMs,
            ],
        ];
    }
}
