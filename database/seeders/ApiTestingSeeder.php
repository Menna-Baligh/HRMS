<?php

namespace Database\Seeders;

use App\Enums\EvaluationPeriodStatus;
use App\Enums\EvaluationStatus;
use App\Enums\GoalStatus;
use App\Enums\LeaveStatus;
use App\Enums\PolicyStatus;
use App\Enums\PolicyVersionStatus;
use App\Enums\SubmissionStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Bonus;
use App\Models\CompanyEvent;
use App\Models\Deduction;
use App\Models\Evaluation;
use App\Models\EvaluationAuditLog;
use App\Models\EvaluationCategory;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationScore;
use App\Models\Goal;
use App\Models\Holiday;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\Policy;
use App\Models\PolicyAudit;
use App\Models\PolicyVersion;
use App\Models\Submission;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * ApiTestingSeeder
 *
 * Seeds only the minimum records required to test all implemented API endpoints.
 *
 * Safe to run multiple times: fully idempotent.
 * Does NOT truncate, delete, or overwrite existing business data.
 *
 * Target environment: local development only.
 * Run with: php artisan db:seed --class=ApiTestingSeeder
 */
class ApiTestingSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('=== ApiTestingSeeder: Starting ===');

        // ---------------------------------------------------------------------------
        // Resolve existing users dynamically — never assume IDs
        // ---------------------------------------------------------------------------
        $owner   = User::role('Owner')->where('status', 'active')->firstOrFail();
        $hr      = User::role('HR')->where('status', 'active')->firstOrFail();
        $manager = User::role('Manager')->where('status', 'active')->firstOrFail();
        $employee = User::role('Employee')
            ->where('status', 'active')
            ->where('manager_id', $manager->id)
            ->firstOrFail();

        $this->command->line("  Resolved: Owner #{$owner->id}, HR #{$hr->id}, Manager #{$manager->id}, Employee #{$employee->id}");

        // ---------------------------------------------------------------------------
        // 1. HOLIDAYS — needed for holiday endpoints + CalendarService
        // ---------------------------------------------------------------------------
        $this->command->info('  [1] Seeding holidays...');
        Holiday::firstOrCreate(
            ['name' => '[TEST] National Day'],
            [
                'start_date'  => Carbon::now()->addDays(10)->toDateString(),
                'end_date'    => Carbon::now()->addDays(10)->toDateString(),
                'description' => 'Test national public holiday.',
                'is_active'   => true,
            ]
        );
        Holiday::firstOrCreate(
            ['name' => '[TEST] Year-End Break'],
            [
                'start_date'  => Carbon::now()->addDays(60)->toDateString(),
                'end_date'    => Carbon::now()->addDays(62)->toDateString(),
                'description' => 'Test year-end company closure.',
                'is_active'   => true,
            ]
        );

        // ---------------------------------------------------------------------------
        // 2. COMPANY EVENTS — needed for company-event endpoints + CalendarService
        // ---------------------------------------------------------------------------
        $this->command->info('  [2] Seeding company events...');
        CompanyEvent::firstOrCreate(
            ['name' => '[TEST] Company Offsite Meeting'],
            [
                'start_date'  => Carbon::now()->addDays(5)->toDateString(),
                'end_date'    => Carbon::now()->addDays(5)->toDateString(),
                'description' => 'Test company-wide offsite meeting.',
                'is_active'   => true,
                'created_by'  => $owner->id,
            ]
        );
        CompanyEvent::firstOrCreate(
            ['name' => '[TEST] Q4 All-Hands'],
            [
                'start_date'  => Carbon::now()->addDays(30)->toDateString(),
                'end_date'    => Carbon::now()->addDays(30)->toDateString(),
                'description' => 'Test Q4 all-hands event.',
                'is_active'   => true,
                'created_by'  => $owner->id,
            ]
        );

        // ---------------------------------------------------------------------------
        // 3. LEAVE BALANCES — CRITICAL: leave requests fail without these
        //    Only create for users who don't already have a balance for this year
        // ---------------------------------------------------------------------------
        $this->command->info('  [3] Seeding leave balances...');
        $currentYear = (int) Carbon::now()->year;

        // Resolve leave types dynamically
        $annualLeaveType  = \App\Models\LeaveType::where('name', 'Annual Leave')
            ->where('is_active', true)
            ->first();
        $sickLeaveType    = \App\Models\LeaveType::where('name', 'Sick Leave')
            ->where('is_active', true)
            ->first();
        $emergencyLeaveType = \App\Models\LeaveType::where('name', 'Emergency Leave')
            ->where('is_active', true)
            ->first();

        if (! $annualLeaveType || ! $sickLeaveType) {
            $this->command->warn('  WARNING: Annual or Sick leave type not found — skipping leave balances.');
        } else {
            // Employee leave balances
            LeaveBalance::firstOrCreate(
                ['user_id' => $employee->id, 'leave_type_id' => $annualLeaveType->id, 'year' => $currentYear],
                ['allocated_days' => 21.00, 'used_days' => 0.00]
            );
            LeaveBalance::firstOrCreate(
                ['user_id' => $employee->id, 'leave_type_id' => $sickLeaveType->id, 'year' => $currentYear],
                ['allocated_days' => 14.00, 'used_days' => 0.00]
            );
            if ($emergencyLeaveType) {
                LeaveBalance::firstOrCreate(
                    ['user_id' => $employee->id, 'leave_type_id' => $emergencyLeaveType->id, 'year' => $currentYear],
                    ['allocated_days' => 5.00, 'used_days' => 0.00]
                );
            }
            // Manager leave balances (needed for manager pending queue testing)
            LeaveBalance::firstOrCreate(
                ['user_id' => $manager->id, 'leave_type_id' => $annualLeaveType->id, 'year' => $currentYear],
                ['allocated_days' => 21.00, 'used_days' => 0.00]
            );
        }

        // ---------------------------------------------------------------------------
        // 4. LEAVE REQUESTS — one pending (for approve/reject + manager queue)
        //    One approved (for decision history, calendar events)
        // ---------------------------------------------------------------------------
        $this->command->info('  [4] Seeding leave requests...');

        if ($annualLeaveType) {
            // Pending leave request — needed for manager queue, approve, reject
            $pendingLeave = LeaveRequest::firstOrCreate(
                [
                    'user_id'       => $employee->id,
                    'leave_type_id' => $annualLeaveType->id,
                    'start_date'    => Carbon::now()->addDays(15)->toDateString(),
                    'status'        => LeaveStatus::Pending->value,
                ],
                [
                    'end_date'  => Carbon::now()->addDays(16)->toDateString(),
                    'days'      => 2,
                    'reason'    => '[TEST] Annual leave request for API testing.',
                ]
            );
        }

        // ---------------------------------------------------------------------------
        // 5. GOALS — Manager needs at least one goal (for manager team-goals endpoint)
        // ---------------------------------------------------------------------------
        $this->command->info('  [5] Seeding goals...');

        Goal::firstOrCreate(
            [
                'user_id' => $manager->id,
                'title'   => '[TEST] Q4 Team Performance Improvement',
            ],
            [
                'description' => 'Improve team code review coverage and delivery speed.',
                'target_date' => Carbon::now()->addDays(90)->toDateString(),
                'status'      => GoalStatus::ACTIVE->value,
            ]
        );

        // Employee needs at least one active goal (others exist but are completed)
        Goal::firstOrCreate(
            [
                'user_id' => $employee->id,
                'title'   => '[TEST] Complete API Integration',
            ],
            [
                'description' => 'Integrate the HRMS API with the mobile app.',
                'target_date' => Carbon::now()->addDays(45)->toDateString(),
                'status'      => GoalStatus::ACTIVE->value,
            ]
        );

        // ---------------------------------------------------------------------------
        // 6. EVALUATION PERIODS — needed for evaluation create, list, toggle status
        // ---------------------------------------------------------------------------
        $this->command->info('  [6] Seeding evaluation periods...');

        $activePeriod = EvaluationPeriod::firstOrCreate(
            ['name' => '[TEST] Q4 2026 Review'],
            [
                'start_date' => Carbon::now()->startOfQuarter()->toDateString(),
                'end_date'   => Carbon::now()->endOfQuarter()->toDateString(),
                'status'     => EvaluationPeriodStatus::ACTIVE->value,
            ]
        );

        $closedPeriod = EvaluationPeriod::firstOrCreate(
            ['name' => '[TEST] Q3 2026 Review'],
            [
                'start_date' => Carbon::now()->subQuarter()->startOfQuarter()->toDateString(),
                'end_date'   => Carbon::now()->subQuarter()->endOfQuarter()->toDateString(),
                'status'     => EvaluationPeriodStatus::CLOSED->value,
            ]
        );

        // ---------------------------------------------------------------------------
        // 7. EVALUATION CATEGORIES — needed for evaluation scores
        // ---------------------------------------------------------------------------
        $this->command->info('  [7] Seeding evaluation categories...');

        $catPerformance = EvaluationCategory::firstOrCreate(
            ['name' => '[TEST] Technical Performance'],
            ['max_score' => 100.0, 'weight' => 40.0]
        );
        $catCommunication = EvaluationCategory::firstOrCreate(
            ['name' => '[TEST] Communication'],
            ['max_score' => 100.0, 'weight' => 30.0]
        );
        $catTeamwork = EvaluationCategory::firstOrCreate(
            ['name' => '[TEST] Teamwork'],
            ['max_score' => 100.0, 'weight' => 30.0]
        );

        // ---------------------------------------------------------------------------
        // 8. EVALUATIONS — one draft evaluation by manager on employee
        //    EvaluationService checks: evaluator must be HR/Owner OR direct manager
        //    Manager(3) is the manager_id of employee(6) ✅
        // ---------------------------------------------------------------------------
        $this->command->info('  [8] Seeding evaluations...');

        $draftEvaluation = Evaluation::firstOrCreate(
            [
                'user_id'      => $employee->id,
                'evaluator_id' => $manager->id,
                'period_id'    => $activePeriod->id,
            ],
            [
                'feedback' => '[TEST] Initial draft evaluation for API testing.',
                'status'   => EvaluationStatus::DRAFT->value,
            ]
        );

        // Seed evaluation scores for the draft evaluation
        EvaluationScore::firstOrCreate(
            ['evaluation_id' => $draftEvaluation->id, 'category_id' => $catPerformance->id],
            ['score' => 78.0]
        );
        EvaluationScore::firstOrCreate(
            ['evaluation_id' => $draftEvaluation->id, 'category_id' => $catCommunication->id],
            ['score' => 85.0]
        );
        EvaluationScore::firstOrCreate(
            ['evaluation_id' => $draftEvaluation->id, 'category_id' => $catTeamwork->id],
            ['score' => 90.0]
        );

        // Audit log for the draft evaluation (created_draft action)
        EvaluationAuditLog::firstOrCreate(
            [
                'evaluation_id' => $draftEvaluation->id,
                'user_id'       => $manager->id,
                'action'        => 'created_draft',
            ],
            ['details' => ['note' => '[TEST] Draft created for API testing.']]
        );

        // ---------------------------------------------------------------------------
        // 9. POLICIES — needed for policy.manage, policy.view, policy.version_*
        // ---------------------------------------------------------------------------
        $this->command->info('  [9] Seeding policies...');

        // Policy with a draft version (to test storeVersion, activateVersion)
        $draftPolicy = Policy::firstOrCreate(
            ['title' => '[TEST] Remote Work Policy'],
            [
                'description' => 'Policy governing remote work arrangements.',
                'status'      => PolicyStatus::Draft->value,
                'created_by'  => $owner->id,
            ]
        );

        $draftPolicyVersion = PolicyVersion::firstOrCreate(
            ['policy_id' => $draftPolicy->id, 'version' => 1],
            [
                'content'        => 'Employees may work remotely up to 3 days per week with manager approval.',
                'status'         => PolicyVersionStatus::Draft->value,
                'effective_date' => Carbon::now()->addDays(30)->toDateString(),
                'created_by'     => $owner->id,
            ]
        );

        PolicyAudit::firstOrCreate(
            [
                'policy_id'         => $draftPolicy->id,
                'policy_version_id' => $draftPolicyVersion->id,
                'action'            => 'version_created',
            ],
            [
                'performed_by' => $owner->id,
                'old_status'   => null,
                'new_status'   => PolicyVersionStatus::Draft->value,
                'description'  => '[TEST] Draft version created.',
            ]
        );

        // Policy with an active version (to test active endpoint + audit history)
        $activePolicy = Policy::firstOrCreate(
            ['title' => '[TEST] Code of Conduct Policy'],
            [
                'description' => 'Company-wide code of conduct and ethics policy.',
                'status'      => PolicyStatus::Active->value,
                'created_by'  => $owner->id,
            ]
        );

        $activePolicyVersion = PolicyVersion::firstOrCreate(
            ['policy_id' => $activePolicy->id, 'version' => 1],
            [
                'content'        => 'All employees must adhere to the highest standards of professional conduct.',
                'status'         => PolicyVersionStatus::Active->value,
                'effective_date' => Carbon::now()->subDays(30)->toDateString(),
                'created_by'     => $owner->id,
            ]
        );

        PolicyAudit::firstOrCreate(
            [
                'policy_id'         => $activePolicy->id,
                'policy_version_id' => $activePolicyVersion->id,
                'action'            => 'version_activated',
            ],
            [
                'performed_by' => $owner->id,
                'old_status'   => PolicyVersionStatus::Draft->value,
                'new_status'   => PolicyVersionStatus::Active->value,
                'description'  => '[TEST] Version activated.',
            ]
        );

        // ---------------------------------------------------------------------------
        // 10. SECOND TASK (In Progress) — for testing updateStatus, updateProgress, show
        //     Task 1 already exists (Pending). Create a second task for deeper coverage.
        // ---------------------------------------------------------------------------
        $this->command->info('  [10] Seeding second test task...');

        $secondTask = Task::firstOrCreate(
            [
                'title'      => '[TEST] Implement Leave Management API',
                'created_by' => $hr->id,
            ],
            [
                'description' => '[TEST] Second task for API testing: leave management.',
                'priority'    => TaskPriority::MEDIUM->value,
                'status'      => TaskStatus::IN_PROGRESS->value,
                'progress'    => 40,
                'deadline'    => Carbon::now()->addDays(14)->toDateString(),
            ]
        );

        // Assign second task to employee
        TaskAssignment::firstOrCreate(
            ['task_id' => $secondTask->id, 'user_id' => $employee->id],
            [
                'assigned_by' => $hr->id,
                'assigned_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        TaskActivity::firstOrCreate(
            [
                'task_id'     => $secondTask->id,
                'user_id'     => $hr->id,
                'action'      => 'created',
                'description' => 'Task created.',
            ]
        );

        // ---------------------------------------------------------------------------
        // 11. SECOND SUBMISSION (for the second task) — for approve/reject/request-changes
        //     The existing submission (id=1) is already Pending Review on task 1.
        //     We seed a second one on task 2 to test without modifying existing data.
        // ---------------------------------------------------------------------------
        $this->command->info('  [11] Seeding second submission...');

        $secondSubmission = Submission::firstOrCreate(
            [
                'task_id' => $secondTask->id,
                'user_id' => $employee->id,
                'status'  => SubmissionStatus::PENDING_REVIEW->value,
            ],
            [
                'note'         => '[TEST] Completed leave management API implementation.',
                'submitted_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        // ---------------------------------------------------------------------------
        // 12. PENDING SALARY ADVANCE (for testing advance status update)
        //     Existing advances are all "approved". We need a "pending" one.
        // ---------------------------------------------------------------------------
        $this->command->info('  [12] Seeding pending salary advance...');

        \App\Models\SalaryAdvance::firstOrCreate(
            [
                'user_id' => $employee->id,
                'status'  => 'pending',
                'reason'  => '[TEST] Pending advance for API status-update testing.',
            ],
            [
                'requested_amount'  => 2000.00,
                'repayment_months'  => 4,
                'monthly_deduction' => 500.00,
            ]
        );

        // ---------------------------------------------------------------------------
        // 13. BONUS with pending status — for testing bonus listing
        //     Existing bonuses are "approved". Create a "pending" one.
        // ---------------------------------------------------------------------------
        $this->command->info('  [13] Seeding pending bonus...');

        Bonus::firstOrCreate(
            [
                'user_id'       => $employee->id,
                'incentive_type' => '[TEST] Annual Excellence Bonus',
                'status'        => 'pending',
            ],
            [
                'amount'               => 1500.00,
                'target_month'         => Carbon::now()->format('Y-m'),
                'approved_by_user_id'  => null,
            ]
        );

        // ---------------------------------------------------------------------------
        // 14. DEDUCTION with queued status — for testing deduction listing
        // ---------------------------------------------------------------------------
        $this->command->info('  [14] Seeding queued deduction...');

        Deduction::firstOrCreate(
            [
                'user_id' => $employee->id,
                'reason'  => '[TEST] Unapproved absence deduction',
                'status'  => 'queued',
            ],
            [
                'amount' => 150.00,
                'date'   => Carbon::now()->toDateString(),
                'type'   => 'manual',
            ]
        );

        $this->command->info('=== ApiTestingSeeder: Completed Successfully ===');
        $this->command->table(
            ['Entity', 'Action'],
            [
                ['Holidays',                    'Seeded 2 test holidays'],
                ['Company Events',              'Seeded 2 test events'],
                ['Leave Balances',              'Seeded for employee & manager'],
                ['Leave Request (Pending)',      'Seeded 1 pending request for employee'],
                ['Goals',                       'Seeded active goal for manager & employee'],
                ['Evaluation Period (Active)',   "ID: {$activePeriod->id}"],
                ['Evaluation Period (Closed)',   "ID: {$closedPeriod->id}"],
                ['Evaluation Categories',       'Seeded 3 categories'],
                ['Draft Evaluation',            "ID: {$draftEvaluation->id}, Manager→Employee"],
                ['Policies',                    'Seeded draft + active policy with versions'],
                ['Second Task (In Progress)',    "ID: {$secondTask->id}, assigned to employee"],
                ['Second Submission',           "ID: {$secondSubmission->id}, Pending Review"],
                ['Pending Salary Advance',       'Seeded for employee'],
                ['Pending Bonus',               'Seeded for employee'],
                ['Queued Deduction',            'Seeded for employee'],
            ]
        );
    }
}
