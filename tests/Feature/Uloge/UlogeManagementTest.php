<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\RolesSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // Ensure role and permission catalog exists for role management flows.
    $this->seed(RolesSeeder::class);
    $this->seed(PermissionsSeeder::class);

    $adminUser = User::factory()->create();
    $adminUser->assignRole('admin');

    $this->actingAs($adminUser);
});

test('uloge index page can be rendered', function () {
    $response = $this->get('/uloge');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('roles/index')
        ->has('roles')
        ->where('filters.pretraga', '')
    );
});

test('new role can be created with permissions', function () {
    $response = $this->post('/uloge', [
        'name' => 'supervizor',
        'permissions' => [
            'dodavanje korisnika',
            'uređivanje korisnika',
        ],
    ]);

    $response->assertRedirect('/uloge');

    $role = Role::findByName('supervizor', 'web');

    expect($role->hasPermissionTo('dodavanje korisnika'))->toBeTrue();
    expect($role->hasPermissionTo('uređivanje korisnika'))->toBeTrue();
});

test('role permissions can be updated', function () {
    $role = Role::create([
        'name' => 'operater',
        'guard_name' => 'web',
    ]);

    $role->syncPermissions(['uređivanje ličnog profila']);

    $response = $this->patch("/uloge/{$role->id}", [
        'name' => 'operater',
        'permissions' => ['brisanje korisnika'],
    ]);

    $response->assertRedirect('/uloge');

    $role->refresh();

    expect($role->hasPermissionTo('brisanje korisnika'))->toBeTrue();
    expect($role->hasPermissionTo('uređivanje ličnog profila'))->toBeFalse();
});

test('role without users can be deleted', function () {
    $role = Role::create([
        'name' => 'privremena-uloga',
        'guard_name' => 'web',
    ]);

    $response = $this->delete("/uloge/{$role->id}");

    $response->assertRedirect('/uloge');
    $this->assertSoftDeleted('roles', [
        'id' => $role->id,
    ]);
});

test('role with users can not be deleted', function () {
    $agentUser = User::factory()->create();
    $agentUser->assignRole('agent');

    $agentRole = Role::findByName('agent', 'web');

    $response = $this->delete("/uloge/{$agentRole->id}");

    $response->assertRedirect('/uloge');
    $response->assertSessionHas('error', 'Rola se ne može obrisati dok ima korisnike.');
    expect(Role::where('name', 'agent')->exists())->toBeTrue();
});
