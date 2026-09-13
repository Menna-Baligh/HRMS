<?php

namespace App\Services\AiGateway\ContextBuilders;

use App\Models\CompanyPolicy;
use App\Models\LeaveBalance;
use App\Models\User;
use App\Services\AiGateway\Contracts\AiRequestEnvelope;

class PolicyAssistantContextBuilder
{
    /**
     * Build Policy Assistant context combining active policies and authorized employee facts.
     *
     * @return array{success: bool, envelope?: AiRequestEnvelope, fallback?: array<string, mixed>}
     */
    public function build(User $authUser, ?string $query = null): array
    {
        // 1. Fetch Active Policies
        $activePolicies = CompanyPolicy::active()->get();

        if ($activePolicies->isEmpty()) {
            return [
                'success' => false,
                'fallback' => [
                    'success' => false,
                    'status' => 'insufficient_data',
                    'message' => 'There are no active company policies available.',
                ],
            ];
        }

        // Format policies with traceable source metadata
        $policySources = $activePolicies->map(fn (CompanyPolicy $p) => [
            'id' => $p->id,
            'policy_code' => $p->policy_code,
            'title' => $p->title,
            'category' => $p->category,
            'section' => $p->section,
            'content' => $p->content,
        ])->toArray();

        // 2. Fetch Allowed Personal Facts for Authenticated User Only
        $employee = $authUser->employee()->with('department')->first();

        $personalFacts = [
            'authenticated_user_id' => $authUser->id,
            'role' => $authUser->role instanceof \BackedEnum ? $authUser->role->value : (string) $authUser->role,
            'department' => $employee?->department?->name ?? 'Unassigned',
            'employment_type' => $employee?->employment_type ?? 'Standard',
            'status' => $employee?->status ?? 'active',
        ];

        // Attach leave balances if any
        $leaveBalances = LeaveBalance::with('leaveType')
            ->where('user_id', $authUser->id)
            ->get()
            ->map(fn ($b) => [
                'type' => $b->leaveType->name ?? 'Standard',
                'remaining_days' => $b->remaining_days,
            ])
            ->toArray();

        $personalFacts['leave_balances'] = $leaveBalances;

        // 3. Assemble Context Envelope
        $context = [
            'query' => $query,
            'policies' => $policySources,
            'authorized_personal_facts' => $personalFacts,
        ];

        $userRole = $authUser->role instanceof \BackedEnum ? $authUser->role->value : (string) ($authUser->role ?? 'Employee');

        $envelope = new AiRequestEnvelope(
            version: '1.0',
            feature: 'policy_assistant',
            user: [
                'id' => $authUser->id,
                'role' => $userRole,
            ],
            context: $context,
            metadata: [
                'active_policy_count' => count($policySources),
            ]
        );

        return [
            'success' => true,
            'envelope' => $envelope,
        ];
    }
}
