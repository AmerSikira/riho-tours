<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionRegistry;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class PermissionsSeeder extends Seeder
{
    /**
     * Seed base permissions and assign them to roles.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionNames = PermissionRegistry::all();

        foreach ($permissionNames as $permissionName) {
            $permission = Permission::withTrashed()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);

            if ($permission->trashed()) {
                $permission->restore();
            }
        }

        $adminRole = Role::query()
            ->where('name', 'admin')
            ->where('guard_name', 'web')
            ->first();

        $agentRole = Role::query()
            ->where('name', 'agent')
            ->where('guard_name', 'web')
            ->first();

        if ($adminRole) {
            // Admin has access to complete permission catalog.
            $adminRole->syncPermissions(PermissionRegistry::adminDefault());
        }

        if ($agentRole) {
            // Agent receives limited operational permissions.
            $agentRole->syncPermissions(PermissionRegistry::agentDefault());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
