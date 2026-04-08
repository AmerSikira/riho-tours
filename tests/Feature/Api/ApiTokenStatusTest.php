<?php

use App\Models\User;

test('api token status endpoint confirms valid and active key', function () {
    $plainToken = str_repeat('a', 64);
    $user = User::factory()->create([
        'api_token_hash' => hash('sha256', $plainToken),
        'api_allowed_domains' => ['plugin.example.com'],
        'is_active' => true,
    ]);

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer '.$plainToken,
            'X-Plugin-Domain' => 'plugin.example.com',
        ])
        ->getJson('/api/v1/auth/token/status');

    $response
        ->assertOk()
        ->assertJson([
            'valid' => true,
            'active' => true,
            'message' => 'API key is valid and active.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
});

test('api token status endpoint rejects invalid key', function () {
    $user = User::factory()->create([
        'api_allowed_domains' => ['plugin.example.com'],
        'is_active' => true,
    ]);

    $response = $this
        ->withHeaders([
            'Authorization' => 'Bearer invalid-token',
            'X-Plugin-Domain' => 'plugin.example.com',
        ])
        ->getJson('/api/v1/auth/token/status');

    $response
        ->assertUnauthorized()
        ->assertJson([
            'message' => 'Invalid API token.',
        ]);

    expect($user->fresh()->api_token_last_used_at)->toBeNull();
});
