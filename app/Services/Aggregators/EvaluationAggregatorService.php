<?php
namespace App\Services\Aggregators;

use App\Enums\EvaluationStatus;
use App\Models\Evaluation;

class EvaluationAggregatorService
{
    
    public function getMetrics(int $employeeId, ?int $periodId = null): array
    {
        $query = Evaluation::with('period')
            ->where('employee_id', $employeeId)
            ->where('status', EvaluationStatus::COMPLETED);

        if ($periodId) {
            $query->where('period_id', $periodId);
        }

        $evaluations = $query->latest()->get();

        $latestEvaluation = $evaluations->first();
        $previousEvaluation = $evaluations->skip(1)->first();

        $latestScore = $latestEvaluation ? (float) $latestEvaluation->overall_score : 0.0;
        $previousScore = $previousEvaluation ? (float) $previousEvaluation->overall_score : 0.0;

        $scoreChange = $previousScore > 0
            ? round($latestScore - $previousScore, 2)
            : 0.0;

        return [
            'total_evaluations' => $evaluations->count(),
            'latest_overall_score' => $latestScore,
            'previous_overall_score' => $previousScore,
            'score_change' => $scoreChange,
            'trend' => $scoreChange >= 0 ? 'improving' : 'declining',
        ];
    }
}
