<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\PermissionRegistry;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Validation\ValidationException;

test('permissions seeder creates base permissions and assigns them to roles', function () {
    $this->seed(RolesSeeder::class);
    $this->seed(PermissionsSeeder::class);

    $permissionNames = PermissionRegistry::all();

    foreach ($permissionNames as $permissionName) {
        expect(Permission::where('name', $permissionName)->where('guard_name', 'web')->exists())->toBeTrue();
    }

    $adminRole = Role::findByName('admin', 'web');
    $agentRole = Role::findByName('agent', 'web');

    foreach ($permissionNames as $permissionName) {
        expect($adminRole->hasPermissionTo($permissionName))->toBeTrue();
    }

    expect($agentRole->hasPermissionTo('pregled ličnog profila'))->toBeTrue();
    expect($agentRole->hasPermissionTo('uređivanje ličnog profila'))->toBeTrue();
    expect($agentRole->hasPermissionTo('dodavanje korisnika'))->toBeFalse();
});

test('role cannot be deleted while at least one user is assigned to it', function () {
    $this->seed(RolesSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $adminRole = Role::findByName('admin', 'web');

    expect(fn () => $adminRole->delete())->toThrow(ValidationException::class);
    expect(Role::where('name', 'admin')->exists())->toBeTrue();
});
