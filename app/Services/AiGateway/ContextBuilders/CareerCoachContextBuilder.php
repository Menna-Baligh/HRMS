<?php

namespace App\Services\AiGateway\ContextBuilders;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\AiGateway\Contracts\AiRequestEnvelope;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;

class CareerCoachContextBuilder
{
    /**
     * Build authorized Career Coach context envelope.
     *
     * @return array{success: bool, envelope?: AiRequestEnvelope, fallback?: array<string, mixed>}
     *
     * @throws AuthorizationException
     */
    public function build(User $authUser, ?int $targetEmployeeId = null): array
    {
        // 1. Resolve Target Employee
        $targetEmployee = null;

        if ($targetEmployeeId !== null) {
            $targetEmployee = Employee::with(['department', 'user'])->find($targetEmployeeId);
            if (! $targetEmployee) {
                return [
                    'success' => false,
                    'fallback' => [
                        'success' => false,
                        'status' => 'insufficient_data',
                        'message' => 'Employee profile not found.',
                    ],
                ];
            }
        } else {
            // Default to authenticated user's employee profile
            $targetEmployee = $authUser->employee()->with('department')->first();
        }

        if (! $targetEmployee) {
            return [
                'success' => false,
                'fallback' => [
                    'success' => false,
                    'status' => 'insufficient_data',
                    'message' => 'There is not enough authorized data to generate this response.',
                ],
            ];
        }

        // 2. Enforce Permission Boundaries
        $this->authorizeAccess($authUser, $targetEmployee);

        // 3. Check for Insufficient Data
        if (empty($targetEmployee->job_title) || empty($targetEmployee->start_date)) {
            return [
                'success' => false,
                'fallback' => [
                    'success' => false,
                    'status' => 'insufficient_data',
                    'message' => 'There is not enough authorized data to generate this response.',
                ],
            ];
        }

        // 4. Calculate Tenure
        $startDate = Carbon::parse($targetEmployee->start_date);
        $tenureMonths = max(0, (int) $startDate->diffInMonths(now()));

        // 5. Gather Authorized Leave Context
        $leaveBalances = LeaveBalance::with('leaveType')
            ->where('user_id', $targetEmployee->user_id)
            ->get()
            ->map(fn ($b) => [
                'type' => $b->leaveType->name ?? 'Standard',
                'allocated_days' => $b->allocated_days,
                'used_days' => $b->used_days,
                'remaining_days' => $b->remaining_days,
            ])
            ->toArray();

        $completedLeavesCount = LeaveRequest::where('user_id', $targetEmployee->user_id)
            ->where('status', 'approved')
            ->count();

        // 6. Build Clean Context (Strictly No Passwords, Tokens, or Restricted PII)
        $context = [
            'employee' => [
                'employee_code' => $targetEmployee->employee_id,
                'job_title' => $targetEmployee->job_title,
                'employment_type' => $targetEmployee->employment_type,
                'department' => $targetEmployee->department?->name ?? 'General',
                'tenure_months' => $tenureMonths,
                'status' => $targetEmployee->status,
            ],
            'career_history' => [
                'tenure_months' => $tenureMonths,
                'leaves_completed' => $completedLeavesCount,
                'leave_balances' => $leaveBalances,
            ],
            'focus_areas' => [
                'professional_growth',
                'skill_advancement',
                'action_planning',
            ],
        ];

        $userRole = $authUser->role instanceof \BackedEnum ? $authUser->role->value : (string) ($authUser->role ?? 'Employee');

        $envelope = new AiRequestEnvelope(
            version: '1.0',
            feature: 'career_coach',
            user: [
                'id' => $authUser->id,
                'role' => $userRole,
            ],
            context: $context,
            metadata: [
                'target_employee_id' => $targetEmployee->id,
            ]
        );

        return [
            'success' => true,
            'envelope' => $envelope,
        ];
    }

    /**
     * Verify that $authUser has permission to view $targetEmployee data.
     *
     * @throws AuthorizationException
     */
    protected function authorizeAccess(User $authUser, Employee $targetEmployee): void
    {
        if ($authUser->isOwner() || $authUser->isHR()) {
            return;
        }

        if ($authUser->employee?->id === $targetEmployee->id) {
            return;
        }

        if ($authUser->isManager() && $targetEmployee->manager_id === $authUser->employee?->id) {
            return;
        }

        throw new AuthorizationException('You are not authorized to access this employee AI context.');
    }
}
