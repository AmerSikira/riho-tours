<?php

use App\Models\Role;
use Database\Seeders\RolesSeeder;

it('creates admin and agent roles', function (): void {
    // Seed required base roles and verify both are present.
    $this->seed(RolesSeeder::class);

    expect(Role::where('name', 'admin')->where('guard_name', 'web')->exists())->toBeTrue();
    expect(Role::where('name', 'agent')->where('guard_name', 'web')->exists())->toBeTrue();
});
