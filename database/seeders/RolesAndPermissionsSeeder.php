<?php

namespace Database\Seeders;

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

        $permissions = [
            'view employees',
            'create employee',
            'edit hr fields',
            'edit self profile',
            'deactivate employee',
            'reactivate employee',
            'manage departments',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => $guard]);
        }

        $ownerRole = Role::firstOrCreate(['name' => 'Owner', 'guard_name' => $guard]);
        $hrRole = Role::firstOrCreate(['name' => 'HR', 'guard_name' => $guard]);
        $managerRole = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => $guard]);
        $employeeRole = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => $guard]);

        $hrRole->givePermissionTo(Permission::all());
        $ownerRole->givePermissionTo(Permission::all());

        $managerRole->givePermissionTo(['view employees', 'edit self profile']);

        $employeeRole->givePermissionTo(['edit self profile']);
    }
}
