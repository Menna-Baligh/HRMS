<?php

namespace Database\Seeders;

use App\Enums\PermissionEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'api';

        $roleNames = ['Owner', 'HR', 'Manager', 'Employee'];
        $roles = [];

        foreach ($roleNames as $name) {
            $roles[$name] = Role::firstOrCreate([
                'name' => $name,
                'guard_name' => $guard,
            ]);
        }

        foreach (PermissionEnum::cases() as $permissionEnum) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionEnum->value,
                'guard_name' => $guard,
            ]);

            foreach ($permissionEnum->defaultRoles() as $roleName) {
                if (isset($roles[$roleName])) {
                    $roles[$roleName]->givePermissionTo($permission);
                }
            }
        }

        $roles['Owner']->givePermissionTo(Permission::all());

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
