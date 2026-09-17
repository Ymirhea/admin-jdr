<?php

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

test('roles and permissions seeder configures admin access', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = Role::findByName(RoleEnum::Admin->value, 'web');

    expect($admin->hasPermissionTo(PermissionEnum::AccessAdmin->value))->toBeTrue();
});
