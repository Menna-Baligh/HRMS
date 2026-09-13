<?php

namespace App\Services\AiGateway\Providers;

use App\Services\AiGateway\Contracts\AiProviderInterface;
use App\Services\AiGateway\Contracts\AiProviderResponse;
use App\Services\AiGateway\Contracts\AiRequestEnvelope;

class MockAiProvider implements AiProviderInterface
{
    protected bool $simulateTimeout = false;

    protected bool $simulateInvalidResponse = false;

    protected ?array $customResponse = null;

    public function simulateTimeout(bool $simulate = true): self
    {
        $this->simulateTimeout = $simulate;

        return $this;
    }

    public function simulateInvalidResponse(bool $simulate = true): self
    {
        $this->simulateInvalidResponse = $simulate;

        return $this;
    }

    public function setCustomResponse(?array $response): self
    {
        $this->customResponse = $response;

        return $this;
    }

    public function generate(AiRequestEnvelope $envelope): AiProviderResponse
    {
        if ($this->simulateTimeout) {
            return AiProviderResponse::timedOut(5000);
        }

        if ($this->simulateInvalidResponse) {
            return AiProviderResponse::successful(['corrupted_key' => 'missing required fields']);
        }

        if ($this->customResponse !== null) {
            return AiProviderResponse::successful($this->customResponse);
        }

        $feature = $envelope->feature;
        $data = match ($feature) {
            'career_coach' => [
                'development_suggestions' => [
                    'Deepen domain knowledge in team core responsibilities',
                    'Take on leadership mentorship for junior team members',
                ],
                'skill_improvements' => [
                    'Advanced system architecture',
                    'Cross-functional communication',
                ],
                'action_items' => [
                    'Schedule monthly check-in with direct manager',
                    'Complete internal knowledge sharing presentation within 60 days',
                ],
            ],
            'policy_assistant' => [
                'answer' => 'Based on active company policy, full-time employees accrue 21 days of annual leave per year.',
                'policy_references' => [
                    [
                        'policy_id' => $envelope->context['policies'][0]['id'] ?? 1,
                        'title' => $envelope->context['policies'][0]['title'] ?? 'Annual and Sick Leave Policy',
                        'section' => $envelope->context['policies'][0]['section'] ?? 'Section 1 - Entitlement & Accrual',
                    ],
                ],
            ],
            'performance_insight' => [
                'summary' => 'Consistent performance with high leave balance stability and steady task execution.',
                'highlights' => [
                    'Excellent attendance reliability throughout the requested evaluation window',
                    'Proactive involvement in department deliverables',
                ],
                'areas_for_improvement' => [
                    'Ensure leave requests are scheduled with minimum 48 hours notice',
                ],
            ],
            'evaluation_draft' => [
                'is_draft' => true,
                'disclaimer' => 'AI draft only. Requires manager review, edit, and official submission.',
                'strengths' => [
                    'Strong domain competence and dependable contribution to team goals',
                ],
                'growth_areas' => [
                    'Expand mentoring opportunities with adjacent squads',
                ],
                'suggested_rating_rationale' => 'Consistently meets expectations across core competencies and company values.',
            ],
            default => [
                'result' => 'Operation completed successfully for feature: '.$feature,
            ],
        };

        return AiProviderResponse::successful($data, 150, 180);
    }
}
