<?php
namespace App\Services;

use App\Enums\EvaluationPeriodStatus;
use App\Enums\EvaluationStatus;
use App\Models\Evaluation;
use App\Models\EvaluationEvidence;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationScore;
use Exception;
use Illuminate\Support\Facades\DB;

class EvaluationService
{

    public function saveDraft(int $evaluatorUserId, array $data, ?Evaluation $evaluation = null): Evaluation
    {
        return DB::transaction(function () use ($evaluatorUserId, $data, $evaluation) {
            if ($evaluation) {
                if ($evaluation->status === EvaluationStatus::COMPLETED) {
                    throw new Exception('Completed evaluations cannot be modified as draft.');
                }
            } else {
                $period = EvaluationPeriod::findOrFail($data['period_id']);
                if ($period->status === EvaluationPeriodStatus::CLOSED) {
                    throw new Exception('Cannot create evaluation for a closed period.');
                }

                $evaluation = Evaluation::create([
                    'employee_id' => $data['employee_id'],
                    'evaluator_id' => $evaluatorUserId,
                    'period_id' => $data['period_id'],
                    'feedback' => $data['feedback'] ?? null,
                    'status' => EvaluationStatus::DRAFT,
                ]);
            }

            if (isset($data['feedback'])) {
                $evaluation->update(['feedback' => $data['feedback']]);
            }

            if (isset($data['scores'])) {
                foreach ($data['scores'] as $scoreData) {
                    EvaluationScore::updateOrCreate(
                        [
                            'evaluation_id' => $evaluation->id,
                            'category_id' => $scoreData['category_id'],
                        ],
                        ['score' => $scoreData['score']]
                    );
                }
            }

            if (isset($data['evidence_goal_ids'])) {
                EvaluationEvidence::where('evaluation_id', $evaluation->id)->delete();
                foreach ($data['evidence_goal_ids'] as $goalId) {
                    EvaluationEvidence::create([
                        'evaluation_id' => $evaluation->id,
                        'goal_id' => $goalId,
                    ]);
                }
            }

            return $evaluation->fresh(['scores.category', 'evidence.goal']);
        });
    }


    public function calculateOverallScore(Evaluation $evaluation): float
    {
        $scores = $evaluation->scores()->with('category')->get();

        if ($scores->isEmpty()) {
            return 0.0;
        }

        $weightedScoreSum = 0;
        $weightedMaxSum = 0;

        foreach ($scores as $item) {
            $category = $item->category;
            if ($category) {
                $weight = $category->weight > 0 ? $category->weight : 1;
                $weightedScoreSum += ($item->score * $weight);
                $weightedMaxSum += ($category->max_score * $weight);
            }
        }

        if ($weightedMaxSum <= 0) {
            return 0.0;
        }

        $percentage = ($weightedScoreSum / $weightedMaxSum) * 100;

        return round($percentage, 2);
    }


    public function completeEvaluation(Evaluation $evaluation): Evaluation
    {
        if ($evaluation->status === EvaluationStatus::COMPLETED) {
            throw new Exception('Evaluation is already completed.');
        }

        if ($evaluation->scores()->count() === 0) {
            throw new Exception('Cannot complete evaluation without category scores.');
        }

        return DB::transaction(function () use ($evaluation) {
            $overallScore = $this->calculateOverallScore($evaluation);

            $evaluation->update([
                'overall_score' => $overallScore,
                'status' => EvaluationStatus::COMPLETED,
            ]);

            return $evaluation->fresh(['scores.category', 'evidence.goal', 'employee.user']);
        });
    }
}
