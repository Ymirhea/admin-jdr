<?php

use App\Enums\Permission;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('User')] class extends Component
{
    public User $user;

    public function mount(): void
    {
        $this->user->load('roles');
    }

    public function deleteUser(): void
    {
        abort_unless(auth()->user()?->can(Permission::UsersManage->value), 403);

        if ($this->user->is(auth()->user())) {
            Flux::toast(variant: 'danger', text: __('You cannot delete your own account.'));

            return;
        }

        abort_unless($this->user->canBeDeletedBy(auth()->user()), 403);

        $this->user->delete();

        Flux::toast(variant: 'success', text: __('User deleted.'));

        $this->redirect(route('admin.users.index'), navigate: true);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex flex-col gap-2">
                <flux:heading size="xl">{{ $user->name }}</flux:heading>
                <flux:text>{{ $user->email }}</flux:text>
                @if ($role = $user->roles->first())
                    <flux:badge size="sm">{{ __(Str::headline($role->name)) }}</flux:badge>
                @endif
            </div>

            @php($actor = auth()->user())
            <div class="flex items-center gap-2">
                @can(Permission::UsersManage->value)
                    @if ($user->canBeModifiedBy($actor))
                        <flux:button
                            variant="primary"
                            icon="pencil-square"
                            :href="route('admin.users.edit', $user)"
                            wire:navigate
                        >
                            {{ __('Edit') }}
                        </flux:button>
                    @else
                        <flux:tooltip :content="__($user->modificationDenialKeyFor($actor))" position="top">
                            <span class="inline-flex">
                                <flux:button variant="primary" icon="pencil-square" disabled>
                                    {{ __('Edit') }}
                                </flux:button>
                            </span>
                        </flux:tooltip>
                    @endif
                    @if ($user->canBeDeletedBy($actor))
                        <flux:button
                            variant="danger"
                            icon="trash"
                            wire:click="deleteUser"
                            wire:confirm="{{ __('Are you sure you want to delete this user?') }}"
                        >
                            {{ __('Delete') }}
                        </flux:button>
                    @else
                        <flux:tooltip :content="__($user->deletionDenialKeyFor($actor))" position="top">
                            <span class="inline-flex">
                                <flux:button variant="danger" icon="trash" disabled>
                                    {{ __('Delete') }}
                                </flux:button>
                            </span>
                        </flux:tooltip>
                    @endif
                @endcan
            </div>
        </div>

        <flux:button :href="route('admin.users.index')" variant="ghost" icon="arrow-left" wire:navigate>
            {{ __('Back to users') }}
        </flux:button>
</div>
