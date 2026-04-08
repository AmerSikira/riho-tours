<?php

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Database\Seeders\DefaultUsersSeeder;
use Illuminate\Support\Facades\Hash;

it('creates default admin and agent users with expected roles and password', function (): void {
    // Ensure roles exist before user-role assignment.
    $this->seed(RolesSeeder::class);
    $this->seed(DefaultUsersSeeder::class);

    $adminUser = User::where('email', 'user1@user.com')->first();
    $agentUser = User::where('email', 'user2@user.com')->first();

    expect($adminUser)->not->toBeNull();
    expect($agentUser)->not->toBeNull();

    expect($adminUser->hasRole('admin'))->toBeTrue();
    expect($agentUser->hasRole('agent'))->toBeTrue();
    expect($adminUser->is_active)->toBeTrue();
    expect($agentUser->is_active)->toBeTrue();

    expect(Hash::check('Amer123#!', $adminUser->password))->toBeTrue();
    expect(Hash::check('Amer123#!', $agentUser->password))->toBeTrue();
});
