<?php

use App\Enums\Role;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('admin.dashboard'));

    $response->assertRedirect(route('login'));
});

test('users without admin access receive forbidden', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Player->value);

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertForbidden();
});

test('admins can visit the admin dashboard', function () {
    $user = User::factory()->admin()->create();

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertOk();
});

test('superadmins can visit the admin dashboard', function () {
    $user = User::factory()->superadmin()->create();

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertOk();
});
