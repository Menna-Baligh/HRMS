<?php
namespace App\Services;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

class PermissionService
{
    public function getAllPermissions(): Collection
    {
        return Permission::where('guard_name', 'api')->pluck('name');
    }
}
