<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIPolicyAssistantService
{
    public function askPolicyQuestion(
        User $currentUser,
        string $targetEmployeeCode,
        string $question,
        ?string $sessionId = null
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
            'owner', 'hr', 'hr_admin' => 'hr_admin',
            'manager' => 'manager',
            default => 'employee',
        };

        $payload = array_filter([
            'employee_id' => $targetEmployeeCode,
            'question' => $question,
            'session_id' => $sessionId,
        ], fn ($value) => ! is_null($value));

        $aiBaseUrl = config('services.ai.base_url', 'http://127.0.0.1:8000/api');

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'X-Caller-ID' => $currentUser->employee_id ?? (string) $currentUser->id,
                    'X-Role' => $aiRoleHeader,
                ])
                ->post("{$aiBaseUrl}/policy-assistant", $payload);

            if ($response->failed()) {
                Log::error('AI Policy Assistant Error', ['response' => $response->body()]);
                throw new Exception(__('ai.service_failed'), 500);
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('AI Policy Assistant Connection Failed: '.$e->getMessage());
            throw $e;
        }
    }
}
