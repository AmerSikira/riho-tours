<?php

use App\Exports\CashDeskPaymentLedgerExport;
use App\Models\Arrangement;
use App\Models\ArrangementPackage;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\ReservationClient;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function (): void {
    $this->seed(RolesSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function createCashDeskArrangement(string $code, string $name): Arrangement
{
    return Arrangement::create([
        'sifra' => $code,
        'destinacija' => 'Bosna i Hercegovina',
        'naziv_putovanja' => $name,
        'opis_putovanja' => 'Opis putovanja',
        'datum_polaska' => '2026-07-01',
        'datum_povratka' => '2026-07-08',
        'trajanje_dana' => 7,
        'tip_prevoza' => 'Autobus',
        'tip_smjestaja' => 'Hotel',
        'napomena' => null,
        'is_active' => true,
    ]);
}

function createCashDeskPackage(Arrangement $arrangement, float $price): ArrangementPackage
{
    return ArrangementPackage::create([
        'aranzman_id' => $arrangement->id,
        'naziv' => 'Standard',
        'opis' => null,
        'cijena' => $price,
        'is_active' => true,
    ]);
}

function createCashDeskClient(string $firstName, string $lastName): Client
{
    return Client::create([
        'ime' => $firstName,
        'prezime' => $lastName,
        'broj_dokumenta' => fake()->unique()->numerify('#########'),
        'datum_rodjenja' => '1990-01-01',
        'adresa' => 'Test adresa',
        'city' => 'Sarajevo',
        'broj_telefona' => '+38761000000',
        'email' => null,
    ]);
}

function createCashDeskReservation(
    Arrangement $arrangement,
    int $number,
    string $createdAt,
    string $paymentPlan,
    string $paymentMethod = 'cash',
    ?array $rate = null
): Reservation {
    $reservation = Reservation::create([
        'order_num' => $number,
        'aranzman_id' => $arrangement->id,
        'ime_prezime' => 'Fallback Payer',
        'broj_putnika' => 1,
        'status' => 'na_cekanju',
        'placanje' => $paymentPlan,
        'nacin_uplate' => $paymentMethod,
        'broj_rata' => is_array($rate) ? count($rate) : null,
        'rate' => $rate,
    ]);

    $reservation->forceFill([
        'created_at' => Carbon::parse($createdAt),
    ])->save();

    return $reservation;
}

function attachCashDeskClient(Reservation $reservation, Client $client, ArrangementPackage $package): void
{
    ReservationClient::create([
        'rezervacija_id' => $reservation->id,
        'klijent_id' => $client->id,
        'paket_id' => $package->id,
    ]);
}

test('cash desk page shows one row per received payment entry', function (): void {
    $arrangement = createCashDeskArrangement('BLG-01', 'Ljeto u Živinicama');
    $package = createCashDeskPackage($arrangement, 275.00);

    $fullReservation = createCashDeskReservation(
        arrangement: $arrangement,
        number: 100,
        createdAt: '2026-05-10 10:00:00',
        paymentPlan: 'placeno',
    );
    attachCashDeskClient($fullReservation, createCashDeskClient('Ćamil', 'Šarić'), $package);
    attachCashDeskClient($fullReservation, createCashDeskClient('Žana', 'Đurić'), $package);

    $installmentReservation = createCashDeskReservation(
        arrangement: $arrangement,
        number: 101,
        createdAt: '2026-05-11 10:00:00',
        paymentPlan: 'na_rate',
        paymentMethod: 'bank',
        rate: [
            ['datum_uplate' => '2026-05-14', 'iznos_uplate' => '200.00'],
            ['datum_uplate' => '2026-05-20', 'iznos_uplate' => ''],
            ['datum_uplate' => null, 'iznos_uplate' => '150.50'],
        ],
    );
    attachCashDeskClient($installmentReservation, createCashDeskClient('Amar', 'Hadžić'), $package);

    $deferredReservation = createCashDeskReservation(
        arrangement: $arrangement,
        number: 102,
        createdAt: '2026-05-12 10:00:00',
        paymentPlan: 'na_odgodeno',
    );
    attachCashDeskClient($deferredReservation, createCashDeskClient('Lejla', 'Begić'), $package);

    $response = $this->get('/blagajna?datum_od=2026-05-01&datum_do=2026-05-31');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('blagajna/index')
        ->has('payment_entries.data', 3)
        ->where('payment_entries.data.0.reservation_number', '101')
        ->where('payment_entries.data.0.payment_date', '2026-05-14')
        ->where('payment_entries.data.0.payer', 'Amar Hadžić')
        ->where('payment_entries.data.0.arrangement', 'BLG-01 - Ljeto u Živinicama')
        ->where('payment_entries.data.0.payment_kind', 'Rata 1')
        ->where('payment_entries.data.0.payment_method', 'Banka')
        ->where('payment_entries.data.0.amount', 200)
        ->where('payment_entries.data.1.reservation_number', '101')
        ->where('payment_entries.data.1.payment_date', '2026-05-11')
        ->where('payment_entries.data.1.payment_kind', 'Rata 3')
        ->where('payment_entries.data.1.amount', 150.50)
        ->where('payment_entries.data.2.reservation_number', '100')
        ->where('payment_entries.data.2.payer', 'Ćamil Šarić, Žana Đurić')
        ->where('payment_entries.data.2.payment_kind', 'Kompletna uplata')
        ->where('payment_entries.data.2.payment_method', 'Gotovina')
        ->where('payment_entries.data.2.amount', 550)
    );
});

test('cash desk export downloads an Excel payment ledger with Bosnian columns', function (): void {
    Carbon::setTestNow('2026-05-25 12:00:00');
    Excel::fake();

    $arrangement = createCashDeskArrangement('EXP-01', 'Aranžman Šćepan Polje');
    $package = createCashDeskPackage($arrangement, 400.00);
    $reservation = createCashDeskReservation(
        arrangement: $arrangement,
        number: 301,
        createdAt: '2026-05-15 10:00:00',
        paymentPlan: 'placeno',
        paymentMethod: 'cash',
    );
    attachCashDeskClient($reservation, createCashDeskClient('Đenana', 'Čengić'), $package);

    $this->get('/blagajna/izvoz?datum_od=2026-05-01&datum_do=2026-05-31');

    Excel::assertDownloaded(
        'blagajna-uplate-20260525_120000.xlsx',
        function (CashDeskPaymentLedgerExport $export): bool {
            expect($export->headings())->toBe([
                'Broj rezervacije',
                'Datum uplate',
                'Ko je uplatio',
                'Aranžman',
                'Vrsta uplate',
                'Način uplate',
                'Iznos (KM)',
            ]);

            expect($export->array())->toBe([[
                '301',
                '15.05.2026',
                'Đenana Čengić',
                'EXP-01 - Aranžman Šćepan Polje',
                'Kompletna uplata',
                'Gotovina',
                400.00,
            ]]);

            return true;
        }
    );
});
