<?php

use App\Jobs\DeleteYesterdayGeneratedContractPdfs;
use App\Models\Arrangement;
use App\Models\ContractTemplate;
use App\Models\GeneratedContract;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

afterEach(function (): void {
    Carbon::setTestNow();
});

test('successful login queues generated contract pdf cleanup', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
    Queue::assertPushed(DeleteYesterdayGeneratedContractPdfs::class);
});

test('cleanup job deletes only generated contract pdfs from yesterday', function (): void {
    Carbon::setTestNow('2026-05-18 09:00:00');
    Storage::fake('public');

    $fixture = createGeneratedContractPdfCleanupFixture();
    $yesterdayPath = 'contracts/'.$fixture['reservation']->id.'/contract-standard-20260517090000.pdf';
    $todayPath = 'contracts/'.$fixture['reservation']->id.'/contract-standard-20260518090000.pdf';
    $olderPath = 'contracts/'.$fixture['reservation']->id.'/contract-standard-20260516090000.pdf';

    Storage::disk('public')->put($yesterdayPath, 'yesterday pdf');
    Storage::disk('public')->put($todayPath, 'today pdf');
    Storage::disk('public')->put($olderPath, 'older pdf');

    $yesterdayContract = createGeneratedContractForPdfCleanup(
        $fixture,
        $yesterdayPath,
        Carbon::parse('2026-05-17 12:00:00')
    );
    $todayContract = createGeneratedContractForPdfCleanup(
        $fixture,
        $todayPath,
        Carbon::parse('2026-05-18 12:00:00')
    );
    $olderContract = createGeneratedContractForPdfCleanup(
        $fixture,
        $olderPath,
        Carbon::parse('2026-05-16 12:00:00')
    );

    (new DeleteYesterdayGeneratedContractPdfs)->handle();

    Storage::disk('public')->assertMissing($yesterdayPath);
    Storage::disk('public')->assertExists($todayPath);
    Storage::disk('public')->assertExists($olderPath);

    expect($yesterdayContract->refresh()->rendered_pdf_path)->toBeNull()
        ->and($todayContract->refresh()->rendered_pdf_path)->toBe($todayPath)
        ->and($olderContract->refresh()->rendered_pdf_path)->toBe($olderPath);
});

/**
 * @return array{user: User, reservation: Reservation, template: ContractTemplate}
 */
function createGeneratedContractPdfCleanupFixture(): array
{
    $user = User::factory()->create();
    $arrangement = Arrangement::query()->create([
        'sifra' => 'ARR-'.Str::uuid(),
        'destinacija' => 'Sarajevo',
        'naziv_putovanja' => 'Standard Trip',
        'opis_putovanja' => 'Travel arrangement used for cleanup tests.',
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

    return [
        'user' => $user,
        'reservation' => $reservation,
        'template' => $template,
    ];
}

/**
 * @param  array{user: User, reservation: Reservation, template: ContractTemplate}  $fixture
 */
function createGeneratedContractForPdfCleanup(array $fixture, string $path, Carbon $generatedAt): GeneratedContract
{
    return GeneratedContract::query()->create([
        'reservation_id' => $fixture['reservation']->id,
        'contract_template_id' => $fixture['template']->id,
        'template_version' => 1,
        'contract_number' => 'WEB-1/2026',
        'rendered_html' => '<p>Contract</p>',
        'rendered_pdf_path' => $path,
        'snapshot_data_json' => [
            'data' => [
                'company' => [],
                'contract' => [
                    'number' => 'WEB-1/2026',
                ],
            ],
            'computed' => [],
            'template' => [
                'template_key' => $fixture['template']->template_key,
            ],
        ],
        'generated_at' => $generatedAt,
        'created_by' => $fixture['user']->id,
        'updated_by' => $fixture['user']->id,
    ]);
}
