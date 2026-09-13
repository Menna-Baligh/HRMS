<?php

namespace Database\Seeders;

use App\Models\CompanyPolicy;
use Illuminate\Database\Seeder;

class CompanyPolicySeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            [
                'policy_code' => 'POL-LEAVE-001',
                'title' => 'Annual and Sick Leave Policy',
                'category' => 'Leave',
                'section' => 'Section 1 - Entitlement & Accrual',
                'content' => 'Full-time employees accrue 21 days of paid annual leave per year. Sick leave is granted up to 14 days with medical documentation required for absences extending beyond 2 consecutive business days.',
                'is_active' => true,
                'effective_date' => '2026-01-01',
            ],
            [
                'policy_code' => 'POL-REMOTE-002',
                'title' => 'Remote and Hybrid Work Policy',
                'category' => 'Workplace',
                'section' => 'Section 2 - Eligibility & Core Hours',
                'content' => 'Employees who have completed probation may request up to 2 remote work days per week upon manager approval. Core working hours are 10:00 AM to 4:00 PM local time.',
                'is_active' => true,
                'effective_date' => '2026-01-01',
            ],
            [
                'policy_code' => 'POL-PERF-003',
                'title' => 'Performance Evaluation and Promotion Policy',
                'category' => 'Performance',
                'section' => 'Section 3 - Bi-annual Review Cycle',
                'content' => 'Performance evaluations occur twice yearly. AI-generated performance or evaluation summaries are strictly advisory drafts and require thorough manager review and HR approval before becoming part of the permanent employee record.',
                'is_active' => true,
                'effective_date' => '2026-01-01',
            ],
            [
                'policy_code' => 'POL-CONDUCT-004',
                'title' => 'Workplace Code of Conduct',
                'category' => 'Ethics',
                'section' => 'Section 4 - Confidentiality and Professionalism',
                'content' => 'All employees are required to uphold strict confidentiality regarding company and colleague information. Retaliation of any form against good-faith reporting is strictly prohibited.',
                'is_active' => true,
                'effective_date' => '2026-01-01',
            ],
        ];

        foreach ($policies as $policy) {
            CompanyPolicy::updateOrCreate(
                ['policy_code' => $policy['policy_code']],
                $policy
            );
        }
    }
}
