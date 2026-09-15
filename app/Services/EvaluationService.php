<?php

namespace App\Services;

use App\Enums\EvaluationPeriodStatus;
use App\Enums\EvaluationStatus;
use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\EvaluationAuditLog;
use App\Models\EvaluationEvidence;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationScore;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class EvaluationService
{
    public function saveDraft(int $evaluatorUserId, array $data, ?Evaluation $evaluation = null): Evaluation
    {
        return DB::transaction(function () use ($evaluatorUserId, $data, $evaluation) {
            $evaluator = User::with('employee')->findOrFail($evaluatorUserId);
            $targetEmployeeId = $evaluation ? $evaluation->employee_id : $data['employee_id'];

            if (! $evaluator->hasAnyRole(['HR', 'Owner'])) {
                $isDirectReport = Employee::where('id', $targetEmployeeId)
                    ->where('manager_id', $evaluator->employee?->id)
                    ->exists();

                if (! $isDirectReport) {
                    throw new Exception('You can only evaluate employees within your direct team.');
                }
            }

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
            $action = $evaluation->wasRecentlyCreated ? 'created_draft' : 'updated_draft';

            EvaluationAuditLog::create([
                'evaluation_id' => $evaluation->id,
                'user_id' => $evaluatorUserId,
                'action' => $action,
                'details' => [
                    'scores_count' => count($data['scores'] ?? []),
                    'feedback_provided' => ! empty($data['feedback']),
                ],
            ]);

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
            EvaluationAuditLog::create([
                'evaluation_id' => $evaluation->id,
                'user_id' => auth()->id() ?? $evaluation->evaluator_id,
                'action' => 'completed',
                'details' => [
                    'final_overall_score' => $overallScore,
                    'completed_at' => now()->toDateTimeString(),
                ],
            ]);

            return $evaluation->fresh(['scores.category', 'evidence.goal', 'employee.user']);
        });
    }

    public function getManagerTeamEvaluations(Employee $manager, ?string $status = null, ?int $periodId = null, int $perPage = 15)
    {
        $teamEmployeeIds = Employee::where('manager_id', $manager->id)->pluck('id');

        $query = Evaluation::with(['employee.user', 'employee.department', 'evaluator', 'period', 'scores.category', 'evidence.goal'])
            ->whereIn('employee_id', $teamEmployeeIds);

        if ($status) {
            $query->where('status', $status);
        }

        if ($periodId) {
            $query->where('period_id', $periodId);
        }

        return $query->latest()->paginate($perPage);
    }

    public function getHrEvaluationsOverview(
        ?string $status = null,
        ?int $periodId = null,
        ?int $departmentId = null,
        ?int $employeeId = null,
        ?int $evaluatorId = null,
        int $perPage = 10
    ) {
        $query = Evaluation::with([
            'employee.user',
            'employee.department',
            'evaluator',
            'period',
            'scores.category',
            'evidence.goal',
        ]);

        if ($status) {
            $query->where('status', $status);
        }

        if ($periodId) {
            $query->where('period_id', $periodId);
        }

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        if ($evaluatorId) {
            $query->where('evaluator_id', $evaluatorId);
        }

        if ($departmentId) {
            $query->whereHas('employee', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        return $query->latest()->paginate($perPage);
    }

    public function getEmployeeEvaluationsHistory(Employee $employee, int $perPage = 10)
    {
        return Evaluation::with(['period', 'evaluator', 'scores.category', 'evidence.goal', 'auditLogs.user'])
            ->where('employee_id', $employee->id)
            ->where('status', EvaluationStatus::COMPLETED)
            ->latest()
            ->paginate($perPage);
    }
}
