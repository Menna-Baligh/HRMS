<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $statusVal = $this->status->value ?? $this->status;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name,
            'job_title' => $this->user?->job_title,
            'department' => $this->user?->department?->name,
            'evaluator_id' => $this->evaluator_id,
            'evaluator_name' => $this->evaluator?->name,
            'period_id' => $this->period_id,
            'period_name' => $this->period?->name,
            'overall_score' => $this->overall_score,
            'feedback' => $this->feedback,
            'status' => __('evaluation.statuses.'.$statusVal),
            'scores' => EvaluationScoreResource::collection($this->whenLoaded('scores')),
            'evidence' => EvaluationEvidenceResource::collection($this->whenLoaded('evidence')),
            'audit_logs' => $this->whenLoaded('auditLogs', function () {
                return $this->auditLogs->map(fn ($log) => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'performed_by' => $log->user?->name,
                    'details' => $log->details,
                    'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
                ]);
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
