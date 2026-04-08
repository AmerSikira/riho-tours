<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Seed the application's roles.
     */
    public function run(): void
    {
        // Ensure required base roles exist for the default web guard.
        Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        Role::firstOrCreate([
            'name' => 'agent',
            'guard_name' => 'web',
        ]);

        // Keep role-permission bindings consistent whenever roles are seeded.
        $this->call(PermissionsSeeder::class);
    }
}
