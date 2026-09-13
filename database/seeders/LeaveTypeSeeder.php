<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Annual Leave',
                'description' => 'Standard paid annual vacation leave.',
                'is_active' => true,
                'requires_balance' => true,
                'requires_attachment' => false,
            ],
            [
                'name' => 'Sick Leave',
                'description' => 'Leave for medical reasons or illness. Requires medical certificate.',
                'is_active' => true,
                'requires_balance' => true,
                'requires_attachment' => true,
            ],
            [
                'name' => 'Emergency Leave',
                'description' => 'Urgent personal or family emergencies.',
                'is_active' => true,
                'requires_balance' => true,
                'requires_attachment' => false,
            ],
            [
                'name' => 'Unpaid Leave',
                'description' => 'Leave without pay. Does not deduct from leave balance.',
                'is_active' => true,
                'requires_balance' => false,
                'requires_attachment' => false,
            ],
        ];

        foreach ($types as $type) {
            LeaveType::updateOrCreate(['name' => $type['name']], $type);
        }
    }
}
