<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AICareerCoachService
{
    public function getCareerCoachGuidance(User $currentUser, string $targetEmployeeCode, ?string $period = null): array
    {
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

        $payload = [
            'employee_id' => $targetEmployeeCode,
            'period'      => $period ?? now()->format('Y-\Q').ceil(now()->month / 3),
        ];

        $aiBaseUrl = config('services.ai.base_url', 'http://ai-service-url/api');
        $rawRole = $currentUser->role instanceof \BackedEnum
        ? $currentUser->role->value
        : (string) $currentUser->role;

        $aiRoleHeader = match (strtolower($rawRole)) {
            'owner', 'hr' => 'hr_admin',
            'manager'                 => 'manager',
            default                   => 'employee',
        };

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-Caller-ID' => $currentUser->employee_id ?? (string) $currentUser->id,
                    'X-Role'      => $aiRoleHeader,
        ])->post("{$aiBaseUrl}/career-coach", $payload);

            if ($response->failed()) {
                Log::error('AI Service Error', ['response' => $response->body()]);
                throw new Exception(__('ai.service_failed'), 500);
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('AI Career Coach Connection Failed: '.$e->getMessage());
            throw $e;
        }
    }
}
