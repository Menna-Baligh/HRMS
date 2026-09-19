<?php

namespace App\Services;

use App\Enums\PermissionEnum;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

class PermissionService
{
    public function getAllPermissions(): Collection
    {
        return collect(PermissionEnum::cases())->map(function (PermissionEnum $permission) {
            return $permission->label(); 
        });
    }
}
