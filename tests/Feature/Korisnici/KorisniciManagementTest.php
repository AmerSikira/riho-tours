<?php

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // Seed role data before assigning roles in tests.
    $this->seed(RolesSeeder::class);

    $adminUser = User::factory()->create();
    $adminUser->assignRole('admin');

    $this->actingAs($adminUser);
});

test('korisnici index page can be rendered', function () {
    $response = $this->get('/korisnici');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('users/index')
        ->has('users.data')
        ->has('users.current_page')
        ->where('filters.pretraga', '')
    );
});

test('korisnici can be searched by name', function () {
    User::factory()->create([
        'name' => 'Marko Markovic',
        'email' => 'marko@user.com',
    ]);

    User::factory()->create([
        'name' => 'Ivana Ilic',
        'email' => 'ivana@user.com',
    ]);

    $response = $this->get('/korisnici?pretraga=Marko');

    $response->assertOk();
    $response->assertSee('Marko Markovic');
    $response->assertDontSee('Ivana Ilic');
});

test('new user can be created from korisnici dodaj page', function () {
    $response = $this->post('/korisnici', [
        'name' => 'Novi Agent',
        'email' => 'novi.agent@user.com',
        'password' => 'Amer123#!',
        'password_confirmation' => 'Amer123#!',
        'role' => 'agent',
        'is_active' => '1',
    ]);

    $response->assertRedirect('/korisnici');

    $createdUser = User::where('email', 'novi.agent@user.com')->first();

    expect($createdUser)->not->toBeNull();
    expect($createdUser->hasRole('agent'))->toBeTrue();
    expect($createdUser->is_active)->toBeTrue();
});

test('user profile page can be rendered on /korisnici/{id}', function () {
    $user = User::factory()->create([
        'name' => 'Profil Korisnik',
        'email' => 'profil@user.com',
    ]);
    $user->assignRole('agent');

    $response = $this->get("/korisnici/{$user->id}");

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('users/edit')
        ->where('user.id', $user->id)
        ->where('user.name', 'Profil Korisnik')
    );
});

test('legacy /korisnici/{id}/uredi route still works', function () {
    $user = User::factory()->create([
        'name' => 'Legacy Edit',
        'email' => 'legacy@user.com',
    ]);
    $user->assignRole('agent');

    $response = $this->get("/korisnici/{$user->id}/uredi");

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('users/edit')
        ->where('user.id', $user->id)
    );
});

test('user can be updated from korisnici edit page', function () {
    $user = User::factory()->create([
        'name' => 'Staro Ime',
        'email' => 'staro@user.com',
        'is_active' => true,
    ]);
    $user->assignRole('agent');

    $response = $this->patch("/korisnici/{$user->id}", [
        'name' => 'Novo Ime',
        'email' => 'novo@user.com',
        'password' => '',
        'password_confirmation' => '',
        'role' => 'admin',
        'is_active' => '0',
    ]);

    $response->assertRedirect('/korisnici');

    $user->refresh();

    expect($user->name)->toBe('Novo Ime');
    expect($user->email)->toBe('novo@user.com');
    expect($user->is_active)->toBeFalse();
    expect($user->hasRole('admin'))->toBeTrue();
});

test('user status can be toggled from korisnici actions', function () {
    $user = User::factory()->create([
        'is_active' => true,
    ]);

    $response = $this->patch("/korisnici/{$user->id}/status");

    $response->assertRedirect('/korisnici');
    expect($user->refresh()->is_active)->toBeFalse();
});

test('user can be deleted from korisnici actions', function () {
    $user = User::factory()->create();

    $response = $this->delete("/korisnici/{$user->id}");

    $response->assertRedirect('/korisnici');
    $this->assertSoftDeleted('users', [
        'id' => $user->id,
    ]);
});
