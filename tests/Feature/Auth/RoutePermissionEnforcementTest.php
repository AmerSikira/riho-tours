<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesSeeder;

test('user without required permission receives forbidden response', function () {
    $this->seed(RolesSeeder::class);

    $restrictedRole = Role::query()->firstOrCreate([
        'name' => 'bez-dozvola',
        'guard_name' => 'web',
    ]);

    $user = User::factory()->create();
    $user->assignRole($restrictedRole);

    $response = $this->actingAs($user)->get('/korisnici');

    $response->assertForbidden();
});

test('admin can access protected route with seeded permissions', function () {
    $this->seed(RolesSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get('/korisnici');

    $response->assertOk();
});
