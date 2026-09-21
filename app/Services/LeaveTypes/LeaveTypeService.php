<?php

namespace App\Services\LeaveTypes;

use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class LeaveTypeService
{
    /**
     * Get all leave types.
     */
    public function index(): Collection
    {
        return LeaveType::query()
            ->latest()
            ->get();
    }

    /**
     * Create a new leave type.
     */
    public function create(array $data): LeaveType
    {
        return DB::transaction(function () use ($data) {
            return LeaveType::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'default_days' => $data['default_days'],
                'is_active' => $data['is_active'] ?? true,
                'requires_balance' => $data['requires_balance'] ?? true,
                'requires_attachment' => $data['requires_attachment'] ?? false,
            ]);
        });
    }

    /**
     * Update an existing leave type.
     */
    public function update(LeaveType $leaveType, array $data): LeaveType
    {
        $leaveType->update($data);

        return $leaveType->fresh();
    }

    /**
     * Activate a leave type.
     */
    public function activate(LeaveType $leaveType): LeaveType
    {
        $leaveType->update([
            'is_active' => true,
        ]);

        return $leaveType->fresh();
    }

    /**
     * Deactivate a leave type.
     */
    public function deactivate(LeaveType $leaveType): LeaveType
    {
        $leaveType->update([
            'is_active' => false,
        ]);

        return $leaveType->fresh();
    }
}
