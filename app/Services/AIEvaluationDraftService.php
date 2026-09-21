<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIEvaluationDraftService
{
    public function generateEvaluationDraft(
        User $currentUser,
        string $targetEmployeeCode,
        string $period,
        array $evaluationScores,
        ?string $managerNotes = null
    ): array {
        if ($currentUser->isEmployee()) {
            throw new Exception(__('ai.employees_forbidden_draft'), 403);
        }

        $targetUser = User::where('employee_id', $targetEmployeeCode)->first();

        if (! $targetUser) {
            throw new Exception(__('ai.user_not_found'), 404);
        }

        if ($currentUser->isManager() && $targetUser->manager_id !== $currentUser->id) {
            throw new Exception(__('ai.unauthorized_employee'), 403);
        }

        $rawRole = $currentUser->role instanceof \BackedEnum ? $currentUser->role->value : (string) $currentUser->role;
        $aiRoleHeader = match (strtolower($rawRole)) {
            'owner', 'hr' => 'hr_admin',
            'manager'                 => 'manager',
            default                   => 'employee',
        };

        $payload = array_filter([
            'employee_id'       => $targetEmployeeCode,
            'period'            => $period,
            'evaluation_scores' => $evaluationScores,
            'manager_notes'     => $managerNotes,
        ], fn ($value) => ! is_null($value));

        $aiBaseUrl = config('services.ai.base_url', 'http://127.0.0.1:8000/api');

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-Caller-ID' => $currentUser->employee_id ?? (string) $currentUser->id,
                    'X-Role'      => $aiRoleHeader,
                ])
                ->post("{$aiBaseUrl}/evaluation-draft", $payload);

            if ($response->failed()) {
                Log::error('AI Evaluation Draft Error', ['response' => $response->body()]);
                throw new Exception(__('ai.service_failed'), 500);
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('AI Evaluation Draft Connection Failed: '.$e->getMessage());
            throw $e;
        }
    }
}
