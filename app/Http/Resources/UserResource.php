<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $roleValue = $this->role instanceof \BackedEnum ? $this->role->value : $this->role;
        $avatarFile = $this->files()->latest()->first();

        return [
            'id'                  => $this->id,
            'employee_id'         => $this->employee_id,
            'name'                => $this->name,
            'email'               => $this->email,
            'phone'               => $this->phone,
            'job_title'           => $this->job_title,
            'employment_type'     => $this->employment_type,
            'start_date'          => $this->start_date?->format('Y-m-d'),
            'status'              => $this->status,
            'address'             => $this->address,
            'avatar_url'          => $avatarFile ? route('files.download', $avatarFile->id) : null,
            'role'                => $roleValue,
            'role_label'          => $roleValue ? __('roles.' . strtolower($roleValue)) : null,
            'locale'              => $this->locale,
            'department'          => $this->whenLoaded('department', function () {
                return [
                    'id'   => $this->department->id,
                    'name' => $this->department->name,
                ];
            }),
            'company_location'    => $this->whenLoaded('companyLocation', function () {
                return [
                    'id'   => $this->companyLocation->id,
                    'name' => $this->companyLocation->name,
                ];
            }),
            'manager'             => $this->whenLoaded('manager', function () {
                return [
                    'id'   => $this->manager->id,
                    'name' => $this->manager->name,
                ];
            }),
            'permissions'         => $this->getAllPermissions()->map(function ($permission) {
                return __('permissions.' . $permission->name);
            }),
            'created_at'          => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}