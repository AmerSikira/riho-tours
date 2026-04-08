<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultUsersSeeder extends Seeder
{
    /**
     * Seed the application's default users with base roles.
     */
    public function run(): void
    {
        // Create or update the default admin account.
        $adminUser = User::updateOrCreate(
            ['email' => 'user1@user.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('Amer123#!'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // Create or update the default agent account.
        $agentUser = User::updateOrCreate(
            ['email' => 'user2@user.com'],
            [
                'name' => 'Agent User',
                'password' => Hash::make('Amer123#!'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        // Assign base roles used across the system.
        $adminUser->syncRoles(['admin']);
        $agentUser->syncRoles(['agent']);
    }
}
