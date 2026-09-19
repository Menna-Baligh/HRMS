<?php

namespace App\Services;

use App\Mail\EmployeeInvitationMail;
use App\Models\CompanyLocation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class EmployeeService
{
    public function __construct(protected FileService $fileService) {}

    public function createEmployee(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $managerId = $data['manager_id'] ?? null;
            if (empty($managerId) && ! empty($data['department_id']) && $data['role'] === 'Employee') {
                $department = Department::find($data['department_id']);
                $managerId = $department?->manager_id;
            }

            $locationId = $data['company_location_id'] ?? CompanyLocation::where('is_active', true)->value('id');

            $user = User::create([
                'name'                => $data['name'],
                'email'               => $data['email'],
                'phone'               => $data['phone'] ?? null,
                'password'            => Hash::make($data['password']),
                'role'                => $data['role'],
                'employee_id'         => $this->generateUniqueEmployeeId(),
                'job_title'           => $data['job_title'],
                'employment_type'     => $data['employment_type'],
                'start_date'          => $data['start_date'],
                'status'              => 'inactive',
                'department_id'       => $data['department_id'] ?? null,
                'company_location_id' => $locationId,
                'manager_id'          => $managerId,
                'address'             => $data['address'] ?? null,
            ]);

            $user->assignRole($data['role']);
            if (! empty($data['permissions'])) {
                $user->givePermissionTo($data['permissions']);
            }

            Mail::to($user->email)->send(new EmployeeInvitationMail($user));

            return $user->load(['department', 'companyLocation', 'manager']);
        });
    }

    private function generateUniqueEmployeeId(): string
    {
        $nextId = (Employee::withTrashed()->max('id') ?? 0) + 1;

        return 'EMP-'.date('Y').'-'.str_pad($nextId, 5, '0', STR_PAD_LEFT);
    }

    public function getEmployeeById(int $id): User
    {
        return User::with(['department', 'companyLocation', 'manager', 'files', 'roles'])->findOrFail($id);
    }

    public function updateHrFields(User $user, array $data): User
    {
        $filteredData = array_filter($data, fn ($value) => $value !== null);
    
        if (! empty($filteredData)) {
            $user->update($filteredData);
        }

        return $user->load(['department', 'companyLocation', 'manager', 'files']);
    }

    public function updateProfile(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
        if (isset($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
            $this->fileService->updateAvatar($data['avatar'], $user);
            unset($data['avatar']);
        }

        $updateData = array_intersect_key($data, array_flip(['name', 'phone', 'locale', 'address']));

        if (! empty($updateData)) {
            $user->update(array_filter($updateData, fn ($val) => $val !== null));
        }

        return $user->fresh()->load(['department', 'companyLocation', 'manager', 'files']);
        });
    }

    public function changeAccountStatus(int $userId): User
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth('api')->id()) {
            throw ValidationException::withMessages([
                'employee' => [__('employees.cannot_change_own_status')],
            ]);
        }

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        return $user->fresh()->load(['department', 'companyLocation', 'manager']);
    }

    public function getAllEmployees(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return User::query()
            ->with(['department', 'companyLocation', 'manager', 'roles', 'files'])

            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%")
                        ->orWhere('job_title', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })

            ->when(! empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['department_id']), fn ($q) => $q->where('department_id', $filters['department_id']))
            ->when(! empty($filters['manager_id']), fn ($q) => $q->where('manager_id', $filters['manager_id']))
            ->when(! empty($filters['employment_type']), fn ($q) => $q->where('employment_type', $filters['employment_type']))

            ->when(! empty($filters['role']), function ($query) use ($filters) {
                $query->where('role', $filters['role'])
                    ->orWhereHas('roles', function ($q) use ($filters) {
                        $q->where('name', 'like', "%{$filters['role']}%");
                    });
            })
            ->latest()
            ->paginate($perPage);
    }
}
