<?php

namespace App\Services\AiGateway\Validation;

class AiResponseValidator
{
    /**
     * Validate the AI output payload according to the feature contract.
     *
     * @param  array<string, mixed>|null  $payload
     * @return array{valid: bool, error_message?: string}
     */
    public function validate(string $feature, ?array $payload): array
    {
        if ($payload === null || empty($payload)) {
            return [
                'valid' => false,
                'error_message' => 'The AI response does not match the expected contract: payload is empty.',
            ];
        }

        return match ($feature) {
            'career_coach' => $this->validateCareerCoach($payload),
            'policy_assistant' => $this->validatePolicyAssistant($payload),
            'performance_insight' => $this->validatePerformanceInsight($payload),
            'evaluation_draft' => $this->validateEvaluationDraft($payload),
            default => ['valid' => true],
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{valid: bool, error_message?: string}
     */
    protected function validateCareerCoach(array $payload): array
    {
        if (
            ! isset($payload['development_suggestions']) ||
            ! is_array($payload['development_suggestions']) ||
            ! isset($payload['skill_improvements']) ||
            ! is_array($payload['skill_improvements']) ||
            ! isset($payload['action_items']) ||
            ! is_array($payload['action_items'])
        ) {
            return [
                'valid' => false,
                'error_message' => 'Career coach output must contain development_suggestions, skill_improvements, and action_items arrays.',
            ];
        }

        return ['valid' => true];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{valid: bool, error_message?: string}
     */
    protected function validatePolicyAssistant(array $payload): array
    {
        if (
            ! isset($payload['answer']) ||
            ! is_string($payload['answer']) ||
            ! isset($payload['policy_references']) ||
            ! is_array($payload['policy_references'])
        ) {
            return [
                'valid' => false,
                'error_message' => 'Policy assistant output must contain answer string and policy_references array.',
            ];
        }

        return ['valid' => true];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{valid: bool, error_message?: string}
     */
    protected function validatePerformanceInsight(array $payload): array
    {
        if (
            ! isset($payload['summary']) ||
            ! is_string($payload['summary']) ||
            ! isset($payload['highlights']) ||
            ! is_array($payload['highlights'])
        ) {
            return [
                'valid' => false,
                'error_message' => 'Performance insight output must contain summary string and highlights array.',
            ];
        }

        return ['valid' => true];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{valid: bool, error_message?: string}
     */
    protected function validateEvaluationDraft(array $payload): array
    {
        if (
            ! isset($payload['is_draft']) ||
            $payload['is_draft'] !== true ||
            ! isset($payload['strengths']) ||
            ! is_array($payload['strengths']) ||
            ! isset($payload['growth_areas']) ||
            ! is_array($payload['growth_areas']) ||
            ! isset($payload['suggested_rating_rationale'])
        ) {
            return [
                'valid' => false,
                'error_message' => 'Evaluation draft output must contain is_draft boolean set to true, strengths array, growth_areas array, and suggested_rating_rationale string.',
            ];
        }

        return ['valid' => true];
    }
}
