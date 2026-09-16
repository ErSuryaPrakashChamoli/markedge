<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates the initial Super Admin only when credentials are provided through the
 * environment. No default password is ever shipped.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('markedge.admin.email');
        $password = config('markedge.admin.password');

        if (blank($email) || blank($password)) {
            $this->command?->warn('Skipping admin user: set MARKEDGE_ADMIN_EMAIL and MARKEDGE_ADMIN_PASSWORD to seed one.');

            return;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => config('markedge.admin.name'),
                'password' => $password,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles([User::SUPER_ADMIN_ROLE]);
    }
}
