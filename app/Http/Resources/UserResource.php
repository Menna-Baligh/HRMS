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

        return [
            'id' => (method_exists($this, 'hasRole') && $this->hasRole('Owner'))
                ? $this->id
                : ($this->employee?->id ?? $this->id),
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'role' => $roleValue,
            'permissions' => method_exists($this, 'getAllPermissions') ? $this->getAllPermissions()->pluck('name') : [],
        ];
    }
}
