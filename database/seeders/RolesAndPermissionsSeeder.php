<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionEnum::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (RoleEnum::cases() as $role) {
            Role::findOrCreate($role->value, 'web');
        }

        Role::findByName(RoleEnum::Admin->value, 'web')
            ->syncPermissions([
                PermissionEnum::AccessAdmin->value,
                PermissionEnum::UsersView->value,
                PermissionEnum::UsersManage->value,
            ]);

        Role::findByName(RoleEnum::GameMaster->value, 'web')
            ->syncPermissions([]);

        Role::findByName(RoleEnum::Player->value, 'web')
            ->syncPermissions([]);
    }
}
