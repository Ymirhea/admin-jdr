<?php

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('bootstrap site owner migration seeds roles from enums and creates superadmin owner', function () {
    config(['app.owner_email' => 'owner@example.com']);

    (require database_path('migrations/2026_09_20_135721_bootstrap_site_owner.php'))->up();

    foreach (PermissionEnum::cases() as $permission) {
        expect(Permission::findByName($permission->value, 'web'))->not->toBeNull();
    }

    foreach (RoleEnum::cases() as $role) {
        expect(Role::findByName($role->value, 'web'))->not->toBeNull();
    }

    $owner = User::query()->where('email', 'owner@example.com')->first();

    expect($owner)->not->toBeNull()
        ->and($owner->hasRole(RoleEnum::Superadmin->value))->toBeTrue();
});

test('bootstrap site owner migration skips owner when email is not configured', function () {
    config(['app.owner_email' => null]);

    $countBefore = User::query()->count();

    (require database_path('migrations/2026_09_20_135721_bootstrap_site_owner.php'))->up();

    expect(User::query()->count())->toBe($countBefore);
});

test('bootstrap site owner migration does not duplicate an existing owner', function () {
    config(['app.owner_email' => 'owner@example.com']);

    $migration = (require database_path('migrations/2026_09_20_135721_bootstrap_site_owner.php'));

    $migration->up();
    $migration->up();

    expect(User::query()->where('email', 'owner@example.com')->count())->toBe(1);
});
