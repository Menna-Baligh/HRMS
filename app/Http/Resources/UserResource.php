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
            'id' => $this->id,
            'employee_id' => $this->when($this->employee?->id !== null, $this->employee?->id),
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => $avatarFile ? route('files.download', $avatarFile->id) : null,
            'role' => $roleValue,
            'locale' => $this->locale,
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
