<?php

namespace App\Services;

use Spatie\Permission\Models\Permission;

class PermissionService
{
    public function getAllPermissions(): array
    {
        return Permission::where('guard_name', 'api')
            ->pluck('name')
            ->toArray();
    }
}
