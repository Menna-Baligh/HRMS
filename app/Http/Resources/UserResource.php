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

        $isOwner = $this->hasRole('Owner') || $roleValue === 'Owner';

        $avatarFile = $this->relationLoaded('files')
            ? $this->files->sortByDesc('created_at')->first()
            : $this->files()->latest()->first();

        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'email'               => $this->email,
            'phone'               => $this->phone,

            $this->mergeWhen(! $isOwner, [
                'employee_code'   => $this->employee_id,
                'job_title'       => $this->job_title,
                'employment_type' => $this->employment_type
                    ? __('employment_types.' . strtolower(str_replace(' ', '-', $this->employment_type)))
                    : null,
                'start_date'      => $this->start_date?->format('Y-m-d'),
            ]),

            'status'              => $this->status ? __('statuses.' . $this->status) : null,
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
