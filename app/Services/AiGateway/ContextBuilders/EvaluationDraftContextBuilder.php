<?php

namespace App\Services\AiGateway\ContextBuilders;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\AiGateway\Contracts\AiRequestEnvelope;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;

class EvaluationDraftContextBuilder
{
    /**
     * Build Evaluation Draft context for authorized managers.
     *
     * @param  array<string, mixed>  $managerNotes
     * @return array{success: bool, envelope?: AiRequestEnvelope, fallback?: array<string, mixed>}
     *
     * @throws AuthorizationException
     */
    public function build(
        User $managerUser,
        int $targetEmployeeId,
        array $managerNotes = []
    ): array {
        // 1. Enforce Manager / HR / Owner Role
        if (! $managerUser->isManager() && ! $managerUser->isHR() && ! $managerUser->isOwner()) {
            throw new AuthorizationException('Only managers and HR administrators can generate evaluation drafts.');
        }

        // 2. Resolve Target Employee
        $targetEmployee = Employee::with(['department', 'user'])->find($targetEmployeeId);
        if (! $targetEmployee) {
            return [
                'success' => false,
                'fallback' => [
                    'success' => false,
                    'status' => 'insufficient_data',
                    'message' => 'Target employee not found.',
                ],
            ];
        }

        // 3. Verify Manager Has Authority Over This Employee
        if ($managerUser->isManager() && ! $managerUser->isOwner() && ! $managerUser->isHR()) {
            if ($targetEmployee->manager_id !== $managerUser->employee?->id) {
                throw new AuthorizationException('You can only generate evaluation drafts for employees who report directly to you.');
            }
        }

        // 4. Gather Relevant Performance and Attendance Facts
        $startDate = Carbon::parse($targetEmployee->start_date);
        $tenureMonths = max(0, (int) $startDate->diffInMonths(now()));

        $recentLeaves = LeaveRequest::where('user_id', $targetEmployee->user_id)
            ->where('start_date', '>=', now()->subYear()->toDateString())
            ->get();

        $workOutcomes = [
            'tenure_months' => $tenureMonths,
            'job_title' => $targetEmployee->job_title,
            'department' => $targetEmployee->department?->name ?? 'General',
            'employment_type' => $targetEmployee->employment_type,
            'attendance_reliability_score' => $recentLeaves->where('status', 'approved')->count() > 0 ? 'Normal' : 'High',
            'manager_input_notes' => $managerNotes['notes'] ?? 'General performance cycle review.',
            'focus_competencies' => $managerNotes['competencies'] ?? ['Technical Execution', 'Team Collaboration', 'Dependability'],
        ];

        // 5. Explicit Draft Constraints & Disclaimers
        $context = [
            'is_draft' => true,
            'requires_manager_review' => true,
            'disclaimer' => 'This evaluation is an AI-generated draft strictly for manager review and editing. It does NOT constitute a final or official HR decision.',
            'employee' => [
                'employee_code' => $targetEmployee->employee_id,
                'job_title' => $targetEmployee->job_title,
                'department' => $targetEmployee->department?->name ?? 'General',
                'tenure_months' => $tenureMonths,
            ],
            'performance_data' => $workOutcomes,
        ];

        $userRole = $managerUser->role instanceof \BackedEnum ? $managerUser->role->value : (string) ($managerUser->role ?? 'Manager');

        $envelope = new AiRequestEnvelope(
            version: '1.0',
            feature: 'evaluation_draft',
            user: [
                'id' => $managerUser->id,
                'role' => $userRole,
            ],
            context: $context,
            metadata: [
                'target_employee_id' => $targetEmployee->id,
                'is_draft' => true,
            ]
        );

        return [
            'success' => true,
            'envelope' => $envelope,
        ];
    }
}
