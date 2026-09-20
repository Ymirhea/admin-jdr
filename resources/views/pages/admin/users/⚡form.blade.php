<?php

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('User')] class extends Component
{
    use PasswordValidationRules, ProfileValidationRules;

    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $role = '';

    public function mount(?User $user = null): void
    {
        $this->user = $user;

        if ($user !== null) {
            $user->load('roles');
            abort_unless($user->canBeModifiedBy(auth()->user()), 403);
            $this->name = $user->name;
            $this->email = $user->email;
            $this->role = $user->roles->first()?->name ?? Role::Player->value;
        } else {
            $this->role = Role::Player->value;
        }
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->user !== null;
    }

    /**
     * @return array<int, Password|Rule|array<mixed>|string>
     */
    protected function formPasswordRules(): array
    {
        if (! $this->isEditing) {
            return $this->passwordRules();
        }

        return ['nullable', 'string', Password::default(), 'confirmed'];
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can(Permission::UsersManage->value), 403);

        $userId = $this->user?->id;

        $validated = $this->validate([
            ...$this->profileRules($userId),
            'password' => $this->formPasswordRules(),
            'role' => ['required', Rule::in(Role::values())],
        ]);

        $role = $validated['role'];

        if ($userId === null) {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            $user->assignRole($role);

            Flux::toast(variant: 'success', text: __('User created.'));

            $this->redirect(route('admin.users.show', $user), navigate: true);

            return;
        }

        $user = $this->user;
        abort_unless($user->canBeModifiedBy(auth()->user()), 403);
        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        $user->save();
        $user->syncRoles([$role]);

        Flux::toast(variant: 'success', text: __('User updated.'));

        $this->redirect(route('admin.users.show', $user), navigate: true);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex flex-col gap-1">
            <flux:heading size="xl">
                {{ $this->isEditing ? __('Edit user') : __('Create user') }}
            </flux:heading>
            @if ($this->isEditing)
                <flux:text>{{ $user->email }}</flux:text>
            @endif
        </div>

        <form wire:submit="save" class="w-full max-w-lg space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

            <flux:select wire:model="role" :label="__('Role')">
                @foreach (Role::cases() as $roleOption)
                    <flux:select.option :value="$roleOption->value">{{ __(Str::headline($roleOption->name)) }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input
                wire:model="password"
                :label="$this->isEditing ? __('New password') : __('Password')"
                type="password"
                :required="! $this->isEditing"
                autocomplete="new-password"
                viewable
            />

            <flux:input
                wire:model="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                :required="! $this->isEditing"
                autocomplete="new-password"
                viewable
            />

            <div class="flex items-center gap-3">
                <flux:button variant="primary" type="submit">
                    {{ $this->isEditing ? __('Save changes') : __('Create user') }}
                </flux:button>

                <flux:button :href="route('admin.users.index')" variant="ghost" wire:navigate>
                    {{ __('Cancel') }}
                </flux:button>
            </div>
        </form>
</div>
