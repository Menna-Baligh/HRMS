<?php
namespace App\DTOs;

class PerformanceMetricData
{
    public function __construct(
        public array $attendance,
        public array $tasks,
        public array $goals,
        public array $evaluations
    ) {}

    public function toArray(): array
    {
        return [
            'attendance' => $this->attendance,
            'tasks' => $this->tasks,
            'goals' => $this->goals,
            'evaluations' => $this->evaluations,
        ];
    }
}
