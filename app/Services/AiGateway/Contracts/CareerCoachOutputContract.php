<?php

namespace App\Services\AiGateway\Contracts;

use InvalidArgumentException;

/**
 * DTO that defines the exact shape of the AI output for the Career Coach feature.
 *
 * Expected structure:
 *   - recommendations: array<string>
 *   - skills_to_improve: array<string>
 *   - development_actions: array<string>
 *   - suggested_goals: array<string>
 *   - learning_resources: array<string>
 */
class CareerCoachOutputContract
{
    public array $recommendations;

    public array $skills_to_improve;

    public array $development_actions;

    public array $suggested_goals;

    public array $learning_resources;

    private function __construct(array $data)
    {
        $this->recommendations = $data['recommendations'];
        $this->skills_to_improve = $data['skills_to_improve'];
        $this->development_actions = $data['development_actions'];
        $this->suggested_goals = $data['suggested_goals'];
        $this->learning_resources = $data['learning_resources'];
    }

    public static function fromArray(array $data): self
    {
        $required = [
            'recommendations',
            'skills_to_improve',
            'development_actions',
            'suggested_goals',
            'learning_resources',
        ];
        foreach ($required as $key) {
            if (! array_key_exists($key, $data) || ! is_array($data[$key])) {
                throw new InvalidArgumentException("CareerCoachOutputContract: missing or invalid '$key'.");
            }
        }

        return new self($data);
    }

    public function toArray(): array
    {
        return [
            'recommendations' => $this->recommendations,
            'skills_to_improve' => $this->skills_to_improve,
            'development_actions' => $this->development_actions,
            'suggested_goals' => $this->suggested_goals,
            'learning_resources' => $this->learning_resources,
        ];
    }
}
