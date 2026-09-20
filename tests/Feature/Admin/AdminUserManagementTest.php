<?php

use App\Enums\Role;
use App\Models\User;
use Livewire\Livewire;

test('admin can create a user', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.users.form')
        ->set('name', 'New Player')
        ->set('email', 'new-player@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('role', Role::Player->value)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.users.show', User::query()->where('email', 'new-player@example.com')->first()));

    $user = User::query()->where('email', 'new-player@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->hasRole(Role::Player->value))->toBeTrue();
});

test('admin can update a user', function () {
    $admin = User::factory()->admin()->create();
    $subject = User::factory()->create(['name' => 'Before']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.users.form', ['user' => $subject])
        ->set('name', 'After')
        ->set('email', $subject->email)
        ->set('role', Role::GameMaster->value)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.users.show', $subject));

    $subject->refresh();

    expect($subject->name)->toBe('After');
    expect($subject->hasRole(Role::GameMaster->value))->toBeTrue();
});

test('admin cannot update another admin', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.users.form', ['user' => $otherAdmin])
        ->assertForbidden();
});

test('admin cannot update a superadmin', function () {
    $admin = User::factory()->admin()->create();
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.users.form', ['user' => $superadmin])
        ->assertForbidden();
});

test('superadmin can update an admin', function () {
    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create(['name' => 'Before']);

    $this->actingAs($superadmin);

    Livewire::test('pages::admin.users.form', ['user' => $admin])
        ->set('name', 'After')
        ->set('email', $admin->email)
        ->set('role', Role::Admin->value)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.users.show', $admin));

    expect($admin->refresh()->name)->toBe('After');
});

test('superadmin cannot update another superadmin', function () {
    $superadmin = User::factory()->superadmin()->create();
    $otherSuperadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin);

    Livewire::test('pages::admin.users.form', ['user' => $otherSuperadmin])
        ->assertForbidden();
});

test('admin can delete another user from the index', function () {
    $admin = User::factory()->admin()->create();
    $subject = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.users.index')
        ->call('deleteUser', $subject->id)
        ->assertHasNoErrors();

    expect(User::query()->find($subject->id))->toBeNull();
});

test('admin cannot delete another admin from the index', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.users.index')
        ->call('deleteUser', $otherAdmin->id)
        ->assertForbidden();

    expect(User::query()->find($otherAdmin->id))->not->toBeNull();
});

test('superadmin can delete an admin from the index', function () {
    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($superadmin);

    Livewire::test('pages::admin.users.index')
        ->call('deleteUser', $admin->id)
        ->assertHasNoErrors();

    expect(User::query()->find($admin->id))->toBeNull();
});

test('index shows translated denial tooltips for french users deleting themselves', function () {
    $admin = User::factory()->admin()->create(['locale' => 'fr']);

    $this->actingAs($admin);

    Livewire::test('pages::admin.users.index')
        ->assertSee('Vous ne pouvez pas supprimer votre propre compte')
        ->assertDontSee('You cannot delete your own account');
});

test('index shows disabled actions with denial tooltips for users the actor cannot manage', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();
    $player = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::admin.users.index')
        ->assertSeeHtml(route('admin.users.edit', $player))
        ->assertDontSeeHtml(route('admin.users.edit', $otherAdmin))
        ->assertSee(__('An admin can only be modified by themselves or a superadmin.'));
});
