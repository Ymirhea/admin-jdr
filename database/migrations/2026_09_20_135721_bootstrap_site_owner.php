<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        (new RolesAndPermissionsSeeder)->run();

        $email = config('app.owner_email');

        if (! is_string($email) || $email === '') {
            return;
        }

        if (User::query()->where('email', $email)->exists()) {
            return;
        }

        $user = User::query()->create([
            'name' => Str::headline(Str::before($email, '@')),
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Str::password(32),
        ]);

        $user->assignRole(Role::Superadmin->value);
    }

    public function down(): void
    {
        $email = config('app.owner_email');

        if (! is_string($email) || $email === '') {
            return;
        }

        User::query()->where('email', $email)->delete();
    }
};
