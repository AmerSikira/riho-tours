<?php

use App\Models\Arrangement;
use App\Models\ContractTemplate;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Contracts\ContractGenerationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

afterEach(function (): void {
    Carbon::setTestNow();
});

test('contract generation renders without generated contract database storage', function (): void {
    Carbon::setTestNow('2026-05-20 10:00:00');

    $user = User::factory()->create();
    $arrangement = Arrangement::query()->create([
        'sifra' => 'ARR-'.Str::uuid(),
        'destinacija' => 'Sarajevo',
        'naziv_putovanja' => 'Standard Trip',
        'opis_putovanja' => 'Travel arrangement used for contract generation tests.',
        'datum_polaska' => '2026-06-01',
        'datum_povratka' => '2026-06-07',
        'trajanje_dana' => 7,
        'tip_prevoza' => 'bus',
        'tip_smjestaja' => 'hotel',
        'is_active' => true,
        'subagentski_aranzman' => false,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
    $reservation = Reservation::query()->create([
        'aranzman_id' => $arrangement->id,
        'ime_prezime' => 'Test Traveler',
        'email' => 'traveler@example.com',
        'telefon' => '+38761111222',
        'broj_putnika' => 1,
        'status' => 'potvrdjena',
        'placanje' => 'placeno',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
    $template = ContractTemplate::query()->create([
        'template_key' => 'standard',
        'version' => 1,
        'name' => 'Standard Contract',
        'html_template' => '<p>{{ contract.number }}</p>',
        'is_active' => true,
        'subagentski_ugovor' => false,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $document = app(ContractGenerationService::class)->generate($reservation, $template, false);

    expect(Schema::hasTable('generated_contracts'))->toBeFalse()
        ->and($document->renderedHtml)->toContain('WEB-1/2026')
        ->and($document->hasPdfContent())->toBeFalse();
});
