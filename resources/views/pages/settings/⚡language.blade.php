<?php

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Language settings')] class extends Component {
    public string $locale = '';

    public function mount(): void
    {
        $this->locale = Auth::user()->locale ?? config('app.locale');
    }

    public function updateLanguage(): void
    {
        $validated = $this->validate([
            'locale' => ['required', 'string', 'in:'.implode(',', array_keys(config('app.available_locales')))],
        ]);

        $user = Auth::user();
        $user->locale = $validated['locale'];
        $user->save();

        Flux::toast(variant: 'success', text: __('Language updated.'));

        $this->redirect(route('language.edit'), navigate: true);
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('Language settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Language')" :subheading="__('Update the language used across the site for your account')">
        <form wire:submit="updateLanguage" class="my-6 w-full space-y-6">
            <flux:select wire:model="locale" :label="__('Language')">
                @foreach (config('app.available_locales') as $code => $name)
                    <flux:select.option :value="$code">{{ $name }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="update-language-button">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </form>
    </x-pages::settings.layout>
</section>
