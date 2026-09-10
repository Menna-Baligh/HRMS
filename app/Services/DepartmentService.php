<?php
namespace App\Services;

use App\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DepartmentService
{
    public function getAllDepartments(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Department::with(['manager.user'])
            ->withCount('employees')
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(!empty($filters['status']), function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->latest()
            ->paginate($perPage);
    }

    public function createDepartment(array $data): Department
    {
        $department = Department::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'manager_id' => $data['manager_id'] ?? null,
            'status' => 'active',
        ]);
        return $department->load('manager.user');
    }

    public function updateDepartment(int $id, array $data): Department
    {
        $department = Department::findOrFail($id);
        $department->update($data);

        return $department->load('manager.user');
    }

    public function changeDepartmentStatus(int $id): Department
    {
        $department = Department::findOrFail($id);
        $newStatus = $department->status === 'active' ? 'inactive' : 'active';
        $department->update(['status' => $newStatus]);
        return $department->load('manager.user');
    }
}
