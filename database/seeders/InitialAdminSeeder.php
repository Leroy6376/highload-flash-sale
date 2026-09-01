<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Seeder;

class InitialAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('initial_admin.email');
        $password = config('initial_admin.password');
        if (! is_string($email) || ! is_string($password) || $email === '' || $password === '') {
            return;
        }

        $user = User::query()->updateOrCreate(['email' => $email], [
            'name' => config('initial_admin.name'),
            'password' => $password,
        ]);
        $user->syncRoles([UserRole::SuperAdmin->value]);
    }
}
