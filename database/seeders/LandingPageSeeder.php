<?php

namespace Database\Seeders;

use App\Models\LandingFeature;
use App\Models\LandingPlan;
use App\Models\LandingRole;
use App\Models\LandingSection;
use Illuminate\Database\Seeder;

class LandingPageSeeder extends Seeder
{
    public function run(): void
    {
        // -------------------------------------------------------------
        // 1. Landing Sections (Hero, About, AI Insights, Stats, CTA, Footer)
        // -------------------------------------------------------------

        // Hero Section
        LandingSection::updateOrCreate(
            ['section_key' => 'hero'],
            [
                'content' => [
                    'badge' => 'UNIFIED PEOPLE OPERATIONS PLATFORM',
                    'title' => 'Smarter HR Management. Empowered Teams.',
                    'description' => 'WiseWork transforms traditional HR workflows into an intelligent, centralized system. Streamline employee directory, track attendance, manage leaves, evaluate performance, and uncover organizational talent seamlessly.',
                    'primary_button' => 'Get Started Free',
                    'secondary_button' => 'Explore Features',
                    'trust_text' => '10,000+ Trusted by modern organizations & forward-thinking teams',
                    'ticker_tags' => [
                        'GEOFENCING ATTENDANCE',
                        'AI CAREER & TALENT INSIGHTS',
                        'LIVE PAYROLL RECONCILIATION',
                        'WORKFORCE SKILL-GAP ANALYTICS',
                        'ENTERPRISE ROLE GOVERNANCE',
                        'CENTRALIZED WORKFORCE PROFILE',
                    ],
                ],
            ]
        );

        // About Section
        LandingSection::updateOrCreate(
            ['section_key' => 'about'],
            [
                'content' => [
                    'badge' => 'ABOUT WISEWORK',
                    'title' => 'Engineered for Modern People Operations',
                    'description' => 'We eliminate fragmented spreadsheets and chaotic manual workflows, bringing clarity, efficiency, and intelligence to your workforce.',
                    'stats' => [
                        ['label' => 'Silos', 'value' => '0', 'subtext' => 'Unified workforce records'],
                        ['label' => 'Faster', 'value' => '45%', 'subtext' => 'Daily approval workflows'],
                        ['label' => 'Audit-Ready', 'value' => '100%', 'subtext' => 'Compliance & policy tracking'],
                    ],
                    'highlights' => [
                        [
                            'title' => 'The WiseWork Solution',
                            'description' => 'A single, unified workspace combining core HR administration with cutting-edge talent intelligence and intuitive self-service portals.',
                        ],
                        [
                            'title' => 'The Challenge in Today\'s Workplace',
                            'description' => 'HR departments struggle with scattered employee data, disconnected attendance tools, delayed review cycles, and blind talent matching without actionable data.',
                        ],
                        [
                            'title' => 'Real-Time Operational Clarity',
                            'description' => 'Everything from attendance punches to leave balances update live across all departments.',
                        ],
                    ],
                ],
            ]
        );

        // AI Insights Section
        LandingSection::updateOrCreate(
            ['section_key' => 'ai_insights'],
            [
                'content' => [
                    'badge' => 'SMARTER DECISIONS, POWERED BY DATA',
                    'title' => 'Turn HR data into smarter decisions.',
                    'description' => 'Smart HR helps you see what is happening across your organization and understand what to do next.',
                    'button_text' => 'Discover AI Insights',
                ],
            ]
        );

        // Global Stats Bar
        LandingSection::updateOrCreate(
            ['section_key' => 'global_stats'],
            [
                'content' => [
                    ['value' => '99.8%', 'label' => 'System Reliability & Uptime'],
                    ['value' => '45%', 'label' => 'Faster Routine HR Operations'],
                    ['value' => '10,000+', 'label' => 'Active Employees Managed'],
                    ['value' => '100%', 'label' => 'Data Privacy & Security Compliance'],
                ],
            ]
        );

        // Call to Action (CTA) Section
        LandingSection::updateOrCreate(
            ['section_key' => 'cta'],
            [
                'content' => [
                    'badge' => 'READY TO TRANSFORM YOUR WORKPLACE?',
                    'title' => 'Experience Smarter People Operations Today',
                    'description' => 'Join hundreds of forward-thinking companies streamlining their HR with WiseWork.',
                    'button_text' => 'Start Free Trial',
                    'subnotes' => 'No credit card required • Instant setup • Cancel anytime',
                ],
            ]
        );

        // Footer Section
        LandingSection::updateOrCreate(
            ['section_key' => 'footer'],
            [
                'content' => [
                    'brand_description' => 'Corporate, trustworthy, and operational workforce management system built for modern teams.',
                    'columns' => [
                        'platform' => ['Employee Directory', 'Attendance Tracking', 'Leave Management', 'Performance & Goals'],
                        'resources' => ['Documentation', 'Security & Compliance', 'Help Center'],
                        'company' => ['About WiseWork', 'Careers', 'Privacy Policy'],
                    ],
                    'contact' => [
                        'email' => 'support@wisework.io',
                        'phone' => '+1 (800) 555-0199',
                        'address' => 'Enterprise Tower, Innovation District',
                    ],
                    'copyright' => '© 2026 WiseWork. All rights reserved.',
                    'bottom_tagline' => 'Enterprise Grade People Operations Platform',
                ],
            ]
        );

        // -------------------------------------------------------------
        // 2. Landing Features
        // -------------------------------------------------------------
        $features = [
            [
                'icon' => 'user-group',
                'title' => 'Employee Directory & Profiles',
                'description' => 'Centralized database for contracts, documents, departmental structure, and complete workforce profiles.',
                'order' => 1,
            ],
            [
                'icon' => 'clock',
                'title' => 'Smart Attendance & Shifts',
                'description' => 'Real-time check-ins, automated shift assignments, overtime calculations, and instant presence tracking.',
                'order' => 2,
            ],
            [
                'icon' => 'calendar',
                'title' => 'Leave & Absence Management',
                'description' => 'Customizable leave policies, multi-tier approval flows, automated balance calculations, and team calendars.',
                'order' => 3,
            ],
            [
                'icon' => 'trending-up',
                'title' => 'Performance & OKRs',
                'description' => 'Quarterly reviews, KPI metrics, transparent 360° feedback, and objective goal achievement tracking.',
                'order' => 4,
            ],
            [
                'icon' => 'chart-bar',
                'title' => 'Analytics & Audit-Ready Reports',
                'description' => 'Comprehensive executive reports on retention, attendance rates, payroll readiness, and productivity.',
                'order' => 5,
            ],
            [
                'icon' => 'sparkles',
                'title' => 'AI Insights & Recommendations',
                'description' => 'Native data intelligence that identifies operational bottlenecks and highlights workforce trends.',
                'order' => 6,
            ],
        ];

        foreach ($features as $feature) {
            LandingFeature::updateOrCreate(['title' => $feature['title']], $feature);
        }

        // -------------------------------------------------------------
        // 3. Landing Roles
        // -------------------------------------------------------------
        $roles = [
            [
                'role_name' => 'HR LEADERS',
                'title' => 'Complete Operational Control',
                'description' => 'Automate repetitive workflows, maintain compliance, manage organizational hierarchies, and gain instant reporting with minimal manual effort.',
                'features' => [
                    'Automated onboarding & offboarding',
                    'Comprehensive policy enforcement',
                    'Instant bulk actions & CSV exports',
                ],
                'order' => 1,
            ],
            [
                'role_name' => 'EMPLOYEES',
                'title' => 'Intuitive Self-Service Portal',
                'description' => 'Give your employees seamless access to request leaves, track attendance, review personal goals, and manage profile information on any device.',
                'features' => [
                    'One-click leave requests',
                    'Transparent balance & history tracking',
                    'Goal progress visibility',
                ],
                'order' => 2,
            ],
            [
                'role_name' => 'MANAGERS & C-LEVEL',
                'title' => 'Team Oversight & Agile Approvals',
                'description' => 'Equip managers to review team attendance, approve leave requests in seconds, conduct structured evaluations, and foster team growth.',
                'features' => [
                    'Single-inbox approval center',
                    'Team attendance overview',
                    'Structured performance feedback',
                ],
                'order' => 3,
            ],
        ];

        foreach ($roles as $role) {
            LandingRole::updateOrCreate(['role_name' => $role['role_name']], $role);
        }

        // -------------------------------------------------------------
        // 4. Landing Plans
        // -------------------------------------------------------------
        $plans = [
            [
                'name' => 'STARTER',
                'description' => 'Essential people operations for small teams and startups.',
                'price' => 'Custom',
                'billing_period' => 'per employee / month',
                'is_popular' => false,
                'features' => [
                    'Up to 50 employees',
                    'Core Employee Directory',
                    'Attendance & Shifts',
                    'Standard Leave Management',
                    'Email Support',
                ],
                'button_text' => 'Choose Plan',
                'order' => 1,
            ],
            [
                'name' => 'PROFESSIONAL',
                'description' => 'Complete workforce management with performance & AI insights.',
                'price' => 'Custom',
                'billing_period' => 'per employee / month',
                'is_popular' => true,
                'features' => [
                    'Up to 500 employees',
                    'Everything in Starter',
                    'Performance Reviews & KPIs',
                    'SkillMatch AI Recommendations',
                    'Custom Approval Workflows',
                    'Advanced Reports & Export',
                    'Priority Support',
                ],
                'button_text' => 'Choose Plan',
                'order' => 2,
            ],
            [
                'name' => 'ENTERPRISE',
                'description' => 'Maximum flexibility, dedicated infrastructure, and SLA guarantee.',
                'price' => 'Custom',
                'billing_period' => 'tailored for your organization',
                'is_popular' => false,
                'features' => [
                    'Unlimited employees',
                    'Everything in Professional',
                    'Custom HRIS Integrations',
                    'Dedicated Account Manager',
                    'Custom Roles & Permissions',
                    'Single Sign-On (SSO / SAML)',
                    '99.9% Uptime SLA',
                ],
                'button_text' => 'Contact Sales',
                'order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            LandingPlan::updateOrCreate(['name' => $plan['name']], $plan);
        }
    }
}
