<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AISkillGapService
{
    public function getSkillGapAnalysis(
        User $currentUser,
        string $targetEmployeeCode,
        ?string $period = null,
        ?string $targetRole = null,
        ?array $targetSkills = null
    ): array {
        $targetUser = User::where('employee_id', $targetEmployeeCode)->first();

        if (! $targetUser) {
            throw new Exception(__('ai.user_not_found'), 404);
        }

        if ($currentUser->isEmployee() && $currentUser->id !== $targetUser->id) {
            throw new Exception(__('ai.unauthorized_employee'), 403);
        }

        if ($currentUser->isManager() && $currentUser->id !== $targetUser->id && $targetUser->manager_id !== $currentUser->id) {
            throw new Exception(__('ai.unauthorized_employee'), 403);
        }

        $rawRole = $currentUser->role instanceof \BackedEnum ? $currentUser->role->value : (string) $currentUser->role;
        $aiRoleHeader = match (strtolower($rawRole)) {
            'owner', 'hr' => 'hr_admin',
            'manager' => 'manager',
            default => 'employee',
        };

        $payload = array_filter([
            'employee_id' => $targetEmployeeCode,
            'period' => $period ?? now()->format('Y-\Q').ceil(now()->month / 3),
            'target_role' => $targetRole,
            'target_skills' => $targetSkills,
        ], fn ($value) => ! is_null($value));

        $aiBaseUrl = config('services.ai.base_url', 'http://127.0.0.1:8000/api');

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-Caller-ID' => $currentUser->employee_id ?? (string) $currentUser->id,
                    'X-Role' => $aiRoleHeader,
                ])
                ->post("{$aiBaseUrl}/skill-gap", $payload);

            if ($response->failed()) {
                Log::error('AI Skill Gap Error', ['response' => $response->body()]);
                throw new Exception(__('ai.service_failed'), 500);
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('AI Skill Gap Connection Failed: '.$e->getMessage());
            throw $e;
        }
    }
}
