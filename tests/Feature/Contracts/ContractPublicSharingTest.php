<?php

use App\Models\Arrangement;
use App\Models\ArrangementPackage;
use App\Models\Client;
use App\Models\ContractTemplate;
use App\Models\Reservation;
use App\Models\ReservationClient;
use App\Models\User;
use App\Services\Contracts\ContractDocument;
use App\Services\Contracts\ContractGenerationService;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

afterEach(function (): void {
    Carbon::setTestNow();
});

function contractSharingUser(): User
{
    test()->seed(RolesSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('agent');

    return $user;
}

function contractSharingArrangement(User $user): Arrangement
{
    return Arrangement::query()->create([
        'sifra' => 'ARR-'.Str::uuid(),
        'destinacija' => 'Sarajevo',
        'naziv_putovanja' => 'Shared Contract Trip',
        'opis_putovanja' => 'Travel arrangement used for public contract sharing tests.',
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
}

function contractSharingTemplate(User $user, bool $subagent = false): ContractTemplate
{
    return ContractTemplate::query()->create([
        'template_key' => $subagent ? 'subagent-share-test' : 'standard-share-test',
        'version' => 1,
        'name' => $subagent ? 'Subagent Share Contract' : 'Standard Share Contract',
        'html_template' => '<p>{{ contract.number }}</p>',
        'is_active' => true,
        'subagentski_ugovor' => $subagent,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
}

function contractSharingReservation(Arrangement $arrangement, User $user, ?ContractTemplate $template = null): Reservation
{
    return Reservation::query()->create([
        'aranzman_id' => $arrangement->id,
        'contract_template_id' => $template?->id,
        'ime_prezime' => 'Test Traveler',
        'email' => 'traveler@example.com',
        'telefon' => '+38761111222',
        'broj_putnika' => 1,
        'status' => 'potvrdjena',
        'placanje' => 'placeno',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
}

function contractSharingPackage(Arrangement $arrangement, User $user): ArrangementPackage
{
    return ArrangementPackage::query()->create([
        'aranzman_id' => $arrangement->id,
        'naziv' => 'Standard',
        'opis' => null,
        'cijena' => 1200,
        'smjestaj_trosak' => 400,
        'transport_trosak' => 150,
        'fakultativne_stvari_trosak' => 50,
        'ostalo_trosak' => 25,
        'is_active' => true,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
}

function contractSharingClient(User $user): Client
{
    return Client::query()->create([
        'ime' => 'Amina',
        'prezime' => 'Test',
        'broj_dokumenta' => '123456789',
        'datum_rodjenja' => '1995-05-05',
        'adresa' => 'Test address',
        'city' => 'Sarajevo',
        'broj_telefona' => '+38761111222',
        'email' => 'amina@example.com',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
}

function fakeContractPdfGeneration(int $times = 1, string $pdfContent = '%PDF-1.4 test contract'): void
{
    $mock = Mockery::mock(ContractGenerationService::class);
    $mock->shouldReceive('generate')
        ->times($times)
        ->andReturnUsing(fn (Reservation $reservation): ContractDocument => new ContractDocument(
            contractNumber: $reservation->documentNumber(),
            renderedHtml: '<p>contract</p>',
            pdfContent: $pdfContent,
            data: ['contract' => ['number' => $reservation->documentNumber()]],
            computedPlaceholders: [],
            generatedAt: Carbon::now(),
        ));

    app()->instance(ContractGenerationService::class, $mock);
}

test('contract share prepares one private pdf and public signature link', function (): void {
    Carbon::setTestNow('2026-05-20 10:00:00');
    Storage::fake('local');
    config(['contracts.public_access_days' => 30]);

    $user = contractSharingUser();
    $arrangement = contractSharingArrangement($user);
    $template = contractSharingTemplate($user);
    $reservation = contractSharingReservation($arrangement, $user, $template);
    fakeContractPdfGeneration();

    $response = $this
        ->actingAs($user)
        ->postJson(route('rezervacije.ugovor.podijeli', $reservation));

    $response
        ->assertOk()
        ->assertJsonPath('expires_at', '2026-06-19T10:00:00+00:00');

    $shareUrl = (string) $response->json('url');
    $shareSignature = (string) $response->json('signature');
    expect($shareUrl)->toContain('/javni/ugovor/'.$reservation->id.'/pdf?signature=');
    expect(parse_url($shareUrl, PHP_URL_QUERY))->toContain('signature='.$shareSignature);

    $reservation->refresh();
    expect($reservation->contract_pdf_path)->toBe("contracts/reservations/{$reservation->id}/contract.pdf")
        ->and($reservation->contract_expires_at?->toDateTimeString())->toBe('2026-06-19 10:00:00')
        ->and($shareSignature)->toBe($reservation->contractAccessSignature())
        ->and($reservation->contract_access_signature_hash)->toHaveLength(64)
        ->and(Reservation::hashContractAccessSignature($reservation->contractAccessSignature()))
        ->toBe($reservation->contract_access_signature_hash);

    Storage::disk('local')->assertExists($reservation->contract_pdf_path);
    Storage::disk('local')->assertExists($reservation->contract_pdf_path.'.meta.json');

    $publicPath = parse_url($shareUrl, PHP_URL_PATH).'?'.parse_url($shareUrl, PHP_URL_QUERY);

    $publicResponse = $this->get($publicPath);
    $publicResponse
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertSee('%PDF-1.4 test contract', false);
    expect((string) $publicResponse->headers->get('Cache-Control'))
        ->toContain('no-store')
        ->toContain('no-cache');

    $this->get(route('javni.ugovor.pdf', [
        'rezervacija' => $reservation->id,
        'signature' => 'bad-signature',
    ], false))->assertNotFound();
});

test('public contract link refreshes stale stored copy before streaming', function (): void {
    Carbon::setTestNow('2026-05-20 10:00:00');
    Storage::fake('local');

    $user = contractSharingUser();
    $arrangement = contractSharingArrangement($user);
    $template = contractSharingTemplate($user);
    $reservation = contractSharingReservation($arrangement, $user, $template);
    $signature = $reservation->contractAccessSignature();
    $path = "contracts/reservations/{$reservation->id}/contract.pdf";

    Storage::disk('local')->put($path, '%PDF-1.4 stale TRN contract');
    $reservation->forceFill([
        'contract_pdf_path' => $path,
        'contract_expires_at' => Carbon::now()->addDay(),
        'contract_access_signature_hash' => Reservation::hashContractAccessSignature($signature),
    ])->saveQuietly();

    fakeContractPdfGeneration(pdfContent: '%PDF-1.4 refreshed Broj računa contract');

    $publicResponse = $this->get(route('javni.ugovor.pdf', [
        'rezervacija' => $reservation->id,
        'signature' => $signature,
    ], false));
    $publicResponse
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertSee('%PDF-1.4 refreshed Broj računa contract', false);
    expect((string) $publicResponse->headers->get('Cache-Control'))
        ->toContain('no-store')
        ->toContain('no-cache');

    expect(Storage::disk('local')->get($path))->toBe('%PDF-1.4 refreshed Broj računa contract');
    Storage::disk('local')->assertExists($path.'.meta.json');
});

test('contract share reuses unexpired stored copy and extends expiration', function (): void {
    Carbon::setTestNow('2026-05-20 10:00:00');
    Storage::fake('local');
    config(['contracts.public_access_days' => 30]);

    $user = contractSharingUser();
    $arrangement = contractSharingArrangement($user);
    $template = contractSharingTemplate($user);
    $reservation = contractSharingReservation($arrangement, $user, $template);
    fakeContractPdfGeneration();

    $firstResponse = $this
        ->actingAs($user)
        ->postJson(route('rezervacije.ugovor.podijeli', $reservation))
        ->assertOk();

    Carbon::setTestNow('2026-05-21 10:00:00');

    $secondResponse = $this
        ->actingAs($user)
        ->postJson(route('rezervacije.ugovor.podijeli', $reservation))
        ->assertOk();

    $reservation->refresh();
    expect($secondResponse->json('url'))->toBe($firstResponse->json('url'))
        ->and($reservation->contract_pdf_path)->toBe("contracts/reservations/{$reservation->id}/contract.pdf")
        ->and($reservation->contract_expires_at?->toDateTimeString())->toBe('2026-06-20 10:00:00');
});

test('contract share opportunistically clears expired copies without deleting reservations', function (): void {
    Carbon::setTestNow('2026-05-20 10:00:00');
    Storage::fake('local');
    config(['contracts.public_access_days' => 30]);

    $user = contractSharingUser();
    $arrangement = contractSharingArrangement($user);
    $template = contractSharingTemplate($user);
    $expiredReservation = contractSharingReservation($arrangement, $user, $template);
    $targetReservation = contractSharingReservation($arrangement, $user, $template);
    $expiredPath = "contracts/reservations/{$expiredReservation->id}/contract.pdf";

    Storage::disk('local')->put($expiredPath, '%PDF expired');
    $expiredReservation->forceFill([
        'contract_pdf_path' => $expiredPath,
        'contract_expires_at' => Carbon::now()->subMinute(),
    ])->saveQuietly();

    fakeContractPdfGeneration();

    $this
        ->actingAs($user)
        ->postJson(route('rezervacije.ugovor.podijeli', $targetReservation))
        ->assertOk();

    $expiredReservation->refresh();
    expect($expiredReservation->deleted_at)->toBeNull()
        ->and($expiredReservation->contract_pdf_path)->toBeNull()
        ->and($expiredReservation->contract_expires_at)->toBeNull();

    Storage::disk('local')->assertMissing($expiredPath);
});

test('public financial document preview computes package cost from cost component fields', function (): void {
    Carbon::setTestNow('2026-06-02 10:00:00');

    $user = contractSharingUser();
    $arrangement = contractSharingArrangement($user);
    $reservation = contractSharingReservation($arrangement, $user);
    $package = contractSharingPackage($arrangement, $user);
    $client = contractSharingClient($user);

    ReservationClient::query()->create([
        'rezervacija_id' => $reservation->id,
        'klijent_id' => $client->id,
        'paket_id' => $package->id,
        'dodatno_na_cijenu' => 10,
        'popust' => 5,
        'boravisna_taksa' => 3,
        'osiguranje' => 2,
        'doplata_jednokrevetna_soba' => 0,
        'doplata_dodatno_sjediste' => 0,
        'doplata_sjediste_po_zelji' => 0,
    ]);

    $url = URL::temporarySignedRoute(
        'javni.finansijski-dokumenti.pregled',
        now()->addDay(),
        [
            'rezervacija' => $reservation->id,
            'tip' => 'predracun',
        ],
        absolute: false
    );

    $this->get($url)
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});
