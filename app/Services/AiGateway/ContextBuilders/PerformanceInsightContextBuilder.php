<?php

namespace App\Services\AiGateway\ContextBuilders;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\AiGateway\Contracts\AiRequestEnvelope;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;

class PerformanceInsightContextBuilder
{
    /**
     * Build Performance Insight context for authorized user / period.
     *
     * @return array{success: bool, envelope?: AiRequestEnvelope, fallback?: array<string, mixed>}
     *
     * @throws AuthorizationException
     */
    public function build(
        User $authUser,
        ?int $targetEmployeeId = null,
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        // 1. Resolve Target Employee
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

        // 2. Authorize
        $this->authorizeAccess($authUser, $targetEmployee);

        // 3. Resolve Period
        $from = $fromDate ? Carbon::parse($fromDate) : now()->subMonths(6)->startOfDay();
        $to = $toDate ? Carbon::parse($toDate) : now()->endOfDay();

        // 4. Calculate Authorized Performance & Attendance Metrics
        $leaveRecords = LeaveRequest::where('user_id', $targetEmployee->user_id)
            ->whereBetween('start_date', [$from->toDateString(), $to->toDateString()])
            ->get();

        $totalApprovedLeaveDays = $leaveRecords->where('status', 'approved')->sum('days_requested');
        $pendingLeaveCount = $leaveRecords->where('status', 'pending')->count();
        $cancelledLeaveCount = $leaveRecords->where('status', 'cancelled')->count();

        $startDate = Carbon::parse($targetEmployee->start_date);
        $tenureMonths = max(0, (int) $startDate->diffInMonths(now()));

        $metrics = [
            'tenure_months' => $tenureMonths,
            'period_approved_leave_days' => $totalApprovedLeaveDays,
            'period_pending_leave_requests' => $pendingLeaveCount,
            'period_cancelled_requests' => $cancelledLeaveCount,
            'employment_status' => $targetEmployee->status,
            'employment_type' => $targetEmployee->employment_type,
            'department' => $targetEmployee->department?->name ?? 'General',
        ];

        $sources = [
            'employee_master_record',
            'leave_management_audit',
            'department_roster',
        ];

        $context = [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'metrics' => $metrics,
            'sources' => $sources,
        ];

        $userRole = $authUser->role instanceof \BackedEnum ? $authUser->role->value : (string) ($authUser->role ?? 'Employee');

        $envelope = new AiRequestEnvelope(
            version: '1.0',
            feature: 'performance_insight',
            user: [
                'id' => $authUser->id,
                'role' => $userRole,
            ],
            context: $context,
            metadata: [
                'target_employee_id' => $targetEmployee->id,
                'period_from' => $from->toDateString(),
                'period_to' => $to->toDateString(),
            ]
        );

        return [
            'success' => true,
            'envelope' => $envelope,
        ];
    }

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

        throw new AuthorizationException('You are not authorized to view performance insights for this employee.');
    }
}
