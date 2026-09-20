<?php

use App\Enums\Permission;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Users')] class extends Component
{
    use WithPagination;

    public function deleteUser(int $userId): void
    {
        abort_unless(auth()->user()?->can(Permission::UsersManage->value), 403);

        $user = User::query()->with('roles')->findOrFail($userId);

        if ($user->is(auth()->user())) {
            Flux::toast(variant: 'danger', text: __('You cannot delete your own account.'));

            return;
        }

        abort_unless($user->canBeDeletedBy(auth()->user()), 403);

        $user->delete();

        Flux::toast(variant: 'success', text: __('User deleted.'));
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->with('roles')
            ->orderBy('name')
            ->paginate(15);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <flux:heading size="xl">{{ __('Users') }}</flux:heading>

            @can(Permission::UsersManage->value)
                <flux:button variant="primary" :href="route('admin.users.create')" icon="plus" wire:navigate>
                    {{ __('Create user') }}
                </flux:button>
            @endcan
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Email') }}</flux:table.column>
                <flux:table.column>{{ __('Role') }}</flux:table.column>
                <flux:table.column class="w-0">{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->users as $user)
                    <flux:table.row :key="$user->id">
                        <flux:table.cell>
                            <flux:link :href="route('admin.users.show', $user)" wire:navigate>
                                {{ $user->name }}
                            </flux:link>
                        </flux:table.cell>
                        <flux:table.cell>{{ $user->email }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($role = $user->roles->first())
                                <flux:badge size="sm">{{ __(Str::headline($role->name)) }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @php($actor = auth()->user())
                            <div class="flex items-center justify-end gap-2">
                                @can(Permission::UsersManage->value)
                                    @if ($user->canBeModifiedBy($actor))
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            icon="pencil-square"
                                            :href="route('admin.users.edit', $user)"
                                            wire:navigate
                                        />
                                    @else
                                        <flux:tooltip :content="__($user->modificationDenialKeyFor($actor))" position="top">
                                            <span class="inline-flex">
                                                <flux:button size="sm" variant="ghost" icon="pencil-square" disabled />
                                            </span>
                                        </flux:tooltip>
                                    @endif
                                    @if ($user->canBeDeletedBy($actor))
                                        <flux:button
                                            size="sm"
                                            variant="danger"
                                            icon="trash"
                                            wire:click="deleteUser({{ $user->id }})"
                                            wire:confirm="{{ __('Are you sure you want to delete this user?') }}"
                                        />
                                    @else
                                        <flux:tooltip :content="__($user->deletionDenialKeyFor($actor))" position="top">
                                            <span class="inline-flex">
                                                <flux:button size="sm" variant="danger" icon="trash" disabled />
                                            </span>
                                        </flux:tooltip>
                                    @endif
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

        {{ $this->users->links() }}
</div>
