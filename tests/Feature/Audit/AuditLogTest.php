<?php

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\User;

test('audit log stores actor and change diff for update and delete events', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $klijent = Client::create([
        'ime' => 'Ime',
        'prezime' => 'Prezime',
        'broj_dokumenta' => '0101000501234',
        'datum_rodjenja' => '2000-01-01',
        'adresa' => 'Adresa 1',
        'broj_telefona' => '061111111',
        'email' => 'stari@example.com',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $klijent->update([
        'email' => 'novi@example.com',
        'adresa' => 'Adresa 2',
        'updated_by' => $user->id,
    ]);

    $klijent->delete();

    $updateLog = AuditLog::query()
        ->where('auditable_type', Client::class)
        ->where('auditable_id', $klijent->id)
        ->where('event', 'updated')
        ->latest('created_at')
        ->first();

    expect($updateLog)->not->toBeNull();
    expect($updateLog?->causer_id)->toBe($user->id);
    expect($updateLog?->old_values)->toHaveKey('email');
    expect($updateLog?->new_values)->toHaveKey('email');
    expect($updateLog?->old_values['email'])->toBe('stari@example.com');
    expect($updateLog?->new_values['email'])->toBe('novi@example.com');

    $deleteLog = AuditLog::query()
        ->where('auditable_type', Client::class)
        ->where('auditable_id', $klijent->id)
        ->where('event', 'deleted')
        ->latest('created_at')
        ->first();

    expect($deleteLog)->not->toBeNull();
    expect($deleteLog?->causer_id)->toBe($user->id);
    expect($deleteLog?->old_values)->toHaveKey('ime');
    expect($deleteLog?->new_values)->toBeNull();
});
