<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AITeamInsightService
{
    public function getTeamInsight(User $currentUser, string $departmentName, string $period): array
    {
        if ($currentUser->isEmployee()) {
            throw new Exception(__('ai.employees_forbidden_team'), 403);
        }

        if ($currentUser->isManager()) {
            $userDepartment = $currentUser->department?->name;
            if ($userDepartment && strcasecmp($userDepartment, $departmentName) !== 0) {
                throw new Exception(__('ai.unauthorized_department'), 403);
            }
        }

        $rawRole = $currentUser->role instanceof \BackedEnum ? $currentUser->role->value : (string) $currentUser->role;
        $aiRoleHeader = match (strtolower($rawRole)) {
            'owner', 'hr', 'hr_admin' => 'hr_admin',
            'manager' => 'manager',
            default => 'employee',
        };

        $payload = [
            'department' => $departmentName,
            'period' => $period,
        ];

        $aiBaseUrl = config('services.ai.base_url', 'http://127.0.0.1:8000/api');

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-Caller-ID' => $currentUser->employee_id ?? (string) $currentUser->id,
                    'X-Role' => $aiRoleHeader,
                ])
                ->post("{$aiBaseUrl}/team-insight", $payload);

            if ($response->failed()) {
                Log::error('AI Team Insight Error', ['response' => $response->body()]);
                throw new Exception(__('ai.service_failed'), 500);
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('AI Team Insight Connection Failed: '.$e->getMessage());
            throw $e;
        }
    }
}
