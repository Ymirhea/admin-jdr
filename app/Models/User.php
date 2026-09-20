<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $locale
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'locale'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    public function canBeModifiedBy(User $actor): bool
    {
        if ($actor->is($this)) {
            return true;
        }

        $subjectRole = $this->roles->first()?->name;

        if ($subjectRole === Role::Superadmin->value) {
            return false;
        }

        if ($subjectRole === Role::Admin->value) {
            return $actor->hasRole(Role::Superadmin->value);
        }

        return $actor->hasRole(Role::Admin->value) || $actor->hasRole(Role::Superadmin->value);
    }

    public function canBeDeletedBy(User $actor): bool
    {
        if ($actor->is($this)) {
            return false;
        }

        return $this->canBeModifiedBy($actor);
    }

    public function modificationDenialKeyFor(User $actor): ?string
    {
        if ($this->canBeModifiedBy($actor)) {
            return null;
        }

        return $this->roleManagementDenialKey('modify');
    }

    public function deletionDenialKeyFor(User $actor): ?string
    {
        if ($actor->is($this)) {
            return 'You cannot delete your own account.';
        }

        if ($this->canBeDeletedBy($actor)) {
            return null;
        }

        return $this->roleManagementDenialKey('delete');
    }

    private function roleManagementDenialKey(string $action): string
    {
        $subjectRole = $this->roles->first()?->name;

        if ($subjectRole === Role::Superadmin->value) {
            return $action === 'delete'
                ? 'A superadmin can only be deleted by themselves.'
                : 'A superadmin can only be modified by themselves.';
        }

        if ($subjectRole === Role::Admin->value) {
            return $action === 'delete'
                ? 'An admin can only be deleted by themselves or a superadmin.'
                : 'An admin can only be modified by themselves or a superadmin.';
        }

        return $action === 'delete'
            ? 'You cannot delete this user with your role.'
            : 'You cannot modify this user with your role.';
    }
}
