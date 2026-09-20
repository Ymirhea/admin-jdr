<?php

use App\Enums\Permission;
use App\Models\User;

test('guests are redirected from admin users index', function () {
    $response = $this->get(route('admin.users.index'));

    $response->assertRedirect(route('login'));
});

test('users without users view permission cannot list users', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::AccessAdmin->value);

    $response = $this->actingAs($user)->get(route('admin.users.index'));

    $response->assertForbidden();
});

test('users with users view permission can list users', function () {
    $user = User::factory()->create();
    $user->givePermissionTo([
        Permission::AccessAdmin->value,
        Permission::UsersView->value,
    ]);

    $response = $this->actingAs($user)->get(route('admin.users.index'));

    $response->assertOk();
});

test('users without users manage permission cannot access create user form', function () {
    $user = User::factory()->create();
    $user->givePermissionTo([
        Permission::AccessAdmin->value,
        Permission::UsersView->value,
    ]);

    $response = $this->actingAs($user)->get(route('admin.users.create'));

    $response->assertForbidden();
});

test('admins can access user management routes', function () {
    $admin = User::factory()->admin()->create();
    $subject = User::factory()->create();

    $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.users.create'))->assertOk();
    $this->actingAs($admin)->get(route('admin.users.show', $subject))->assertOk();
    $this->actingAs($admin)->get(route('admin.users.edit', $subject))->assertOk();
});

test('superadmins can access user management routes', function () {
    $superadmin = User::factory()->superadmin()->create();
    $subject = User::factory()->create();

    $this->actingAs($superadmin)->get(route('admin.users.index'))->assertOk();
    $this->actingAs($superadmin)->get(route('admin.users.create'))->assertOk();
});
