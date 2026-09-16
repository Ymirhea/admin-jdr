<?php

use App\Models\User;
use Livewire\Livewire;

test('language settings page can be rendered', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('language.edit'))->assertOk();
});

test('user locale can be updated', function () {
    $user = User::factory()->create(['locale' => null]);

    $this->actingAs($user);

    Livewire::test('pages::settings.language')
        ->set('locale', 'fr')
        ->call('updateLanguage')
        ->assertHasNoErrors()
        ->assertRedirect(route('language.edit'));

    expect($user->refresh()->locale)->toBe('fr');
});

test('authenticated requests use the user locale', function () {
    $user = User::factory()->create(['locale' => 'fr']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();

    expect(app()->getLocale())->toBe('fr');
});
