<?php

use App\Models\AuditLog;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $user = User::factory()->create();
    $this->actingAs($user);
});

test('izmjene index page can be rendered', function () {
    $response = $this->get('/izmjene');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('changes/index')
        ->has('logs.data')
        ->has('logs.current_page')
        ->has('logs.last_page')
        ->where('filters.pretraga', '')
        ->where('filters.datum_od', '')
        ->where('filters.datum_do', '')
    );
});

test('izmjene can be filtered by user or location and date range', function () {
    $firstUser = User::factory()->create([
        'name' => 'Test User A',
        'email' => 'test-a@example.com',
    ]);

    $secondUser = User::factory()->create([
        'name' => 'Test User B',
        'email' => 'test-b@example.com',
    ]);

    $firstLog = AuditLog::create([
        'event' => 'updated',
        'auditable_type' => User::class,
        'auditable_id' => (string) $firstUser->id,
        'causer_type' => User::class,
        'causer_id' => (string) $firstUser->id,
        'old_values' => ['name' => 'Old'],
        'new_values' => ['name' => 'New'],
        'request_context' => [
            'method' => 'PATCH',
            'url' => 'http://localhost/rezervacije/1',
        ],
        'created_by' => $firstUser->id,
        'updated_by' => $firstUser->id,
    ]);

    $secondLog = AuditLog::create([
        'event' => 'updated',
        'auditable_type' => User::class,
        'auditable_id' => (string) $secondUser->id,
        'causer_type' => User::class,
        'causer_id' => (string) $secondUser->id,
        'old_values' => ['name' => 'Old B'],
        'new_values' => ['name' => 'New B'],
        'request_context' => [
            'method' => 'PATCH',
            'url' => 'http://localhost/aranzmani/1',
        ],
        'created_by' => $secondUser->id,
        'updated_by' => $secondUser->id,
    ]);

    AuditLog::query()->whereKey($firstLog->id)->update([
        'created_at' => '2026-03-10 09:00:00',
    ]);
    AuditLog::query()->whereKey($secondLog->id)->update([
        'created_at' => '2031-03-12 09:00:00',
    ]);

    $responseByUser = $this->get('/izmjene?pretraga=User%20A');
    $responseByUser->assertOk();
    $responseByUser->assertInertia(fn (Assert $page) => $page
        ->where('filters.pretraga', 'User A')
        ->has('logs.data', 1)
        ->where('logs.data.0.user_name', 'Test User A')
    );

    $responseByLocation = $this->get('/izmjene?pretraga=rezervacije');
    $responseByLocation->assertOk();
    $responseByLocation->assertInertia(fn (Assert $page) => $page
        ->where('filters.pretraga', 'rezervacije')
        ->has('logs.data', 1)
        ->where('logs.data.0.location', '/rezervacije/1')
    );

    $responseByDate = $this->get('/izmjene?datum_od=2031-03-12&datum_do=2031-03-12');
    $responseByDate->assertOk();
    $responseByDate->assertInertia(fn (Assert $page) => $page
        ->where('filters.datum_od', '2031-03-12')
        ->where('filters.datum_do', '2031-03-12')
        ->has('logs.data', 1)
        ->where('logs.data.0.user_name', 'Test User B')
    );
});
