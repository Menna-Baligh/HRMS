<?php

namespace App\Services\Aggregators;

use App\Enums\EvaluationStatus;
use App\Models\Evaluation;

class EvaluationAggregatorService
{
    public function getMetrics(int $userId, ?int $periodId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = Evaluation::with('period')
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->where('status', EvaluationStatus::COMPLETED)
                    ->orWhere('status', 'completed');
            });

        if ($periodId) {
            $query->where('period_id', $periodId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59']);
        }

        $evaluations = $query->latest('created_at')->get();

        $latestEvaluation = $evaluations->first();
        $previousEvaluation = $evaluations->skip(1)->first();

        $latestScore = $latestEvaluation ? (float) $latestEvaluation->overall_score : 0.0;
        $previousScore = $previousEvaluation ? (float) $previousEvaluation->overall_score : 0.0;

        $scoreChange = $previousScore > 0
            ? round($latestScore - $previousScore, 2)
            : 0.0;

        return [
            'total_evaluations'     => $evaluations->count(),
            'latest_overall_score'  => $latestScore,
            'previous_overall_score'=> $previousScore,
            'score_change'          => $scoreChange,
            'trend'                 => $scoreChange >= 0 ? 'improving' : 'declining',
        ];
    }
}
