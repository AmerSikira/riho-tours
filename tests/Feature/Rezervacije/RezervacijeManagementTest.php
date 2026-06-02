<?php

use App\Exports\ReservationsClientsExport;
use App\Models\Arrangement;
use App\Models\ArrangementPackage;
use App\Models\Client;
use App\Models\ReservationClient;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function () {
    $this->seed(RolesSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user);
});

function createArrangementForReservations(string $sifra, string $naziv, string $polazak, string $povratak): Arrangement
{
    return Arrangement::create([
        'sifra' => $sifra,
        'destinacija' => 'Grčka',
        'naziv_putovanja' => $naziv,
        'opis_putovanja' => 'Opis putovanja',
        'datum_polaska' => $polazak,
        'datum_povratka' => $povratak,
        'trajanje_dana' => 7,
        'tip_prevoza' => 'Avion',
        'tip_smjestaja' => 'Hotel',
        'napomena' => null,
        'is_active' => true,
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
    ]);
}

function createPackageForArrangement(Arrangement $aranzman, string $naziv = 'Standard'): ArrangementPackage
{
    return ArrangementPackage::create([
        'aranzman_id' => $aranzman->id,
        'naziv' => $naziv,
        'opis' => null,
        'cijena' => 199.99,
        'is_active' => true,
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
    ]);
}

test('rezervacije index page can be rendered', function () {
    $response = $this->get('/rezervacije');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('reservations/index')
        ->has('rezervacije.data')
        ->has('rezervacije.current_page')
        ->where('filters.pretraga', '')
    );
});

test('rezervacije can be filtered by arrangement name', function () {
    $aranzmanA = createArrangementForReservations(
        'RSV-01',
        'Ljeto u Antaliji',
        '2026-07-01',
        '2026-07-08'
    );
    $aranzmanB = createArrangementForReservations(
        'RSV-02',
        'Zima na Kopaoniku',
        '2026-12-20',
        '2026-12-27'
    );

    Reservation::create([
        'aranzman_id' => $aranzmanA->id,
        'ime_prezime' => 'Putnik A',
        'broj_putnika' => 2,
        'status' => 'na_cekanju',
    ]);

    Reservation::create([
        'aranzman_id' => $aranzmanB->id,
        'ime_prezime' => 'Putnik B',
        'broj_putnika' => 1,
        'status' => 'na_cekanju',
    ]);

    $response = $this->get('/rezervacije?pretraga=Antaliji');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('filters.pretraga', 'Antaliji')
        ->has('rezervacije.data', 1)
        ->where('rezervacije.data.0.aranzman.naziv_putovanja', 'Ljeto u Antaliji')
    );
});

test('rezervacije can be filtered only by date from', function () {
    $aranzmanA = createArrangementForReservations(
        'RSV-03',
        'Majorka',
        '2026-05-01',
        '2026-05-08'
    );
    $aranzmanB = createArrangementForReservations(
        'RSV-04',
        'Ibiza',
        '2026-08-01',
        '2026-08-08'
    );

    Reservation::create([
        'aranzman_id' => $aranzmanA->id,
        'ime_prezime' => 'Putnik A',
        'broj_putnika' => 2,
        'status' => 'na_cekanju',
    ]);

    Reservation::create([
        'aranzman_id' => $aranzmanB->id,
        'ime_prezime' => 'Putnik B',
        'broj_putnika' => 1,
        'status' => 'na_cekanju',
    ]);

    $response = $this->get('/rezervacije?datum_od=2026-07-01');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('filters.datum_od', '2026-07-01')
        ->has('rezervacije.data', 1)
        ->where('rezervacije.data.0.aranzman.naziv_putovanja', 'Ibiza')
    );
});

test('rezervacije can be filtered only by date to', function () {
    $aranzmanA = createArrangementForReservations(
        'RSV-05',
        'Tunis',
        '2026-06-01',
        '2026-06-08'
    );
    $aranzmanB = createArrangementForReservations(
        'RSV-06',
        'Dubai',
        '2026-11-01',
        '2026-11-08'
    );

    Reservation::create([
        'aranzman_id' => $aranzmanA->id,
        'ime_prezime' => 'Putnik A',
        'broj_putnika' => 2,
        'status' => 'na_cekanju',
    ]);

    Reservation::create([
        'aranzman_id' => $aranzmanB->id,
        'ime_prezime' => 'Putnik B',
        'broj_putnika' => 1,
        'status' => 'na_cekanju',
    ]);

    $response = $this->get('/rezervacije?datum_do=2026-07-01');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('filters.datum_do', '2026-07-01')
        ->has('rezervacije.data', 1)
        ->where('rezervacije.data.0.aranzman.naziv_putovanja', 'Tunis')
    );
});

test('rezervacije can be filtered by date range together', function () {
    $aranzmanA = createArrangementForReservations(
        'RSV-07',
        'Pariz',
        '2026-04-10',
        '2026-04-15'
    );
    $aranzmanB = createArrangementForReservations(
        'RSV-08',
        'Barcelona',
        '2026-09-10',
        '2026-09-15'
    );

    Reservation::create([
        'aranzman_id' => $aranzmanA->id,
        'ime_prezime' => 'Putnik A',
        'broj_putnika' => 2,
        'status' => 'na_cekanju',
    ]);

    Reservation::create([
        'aranzman_id' => $aranzmanB->id,
        'ime_prezime' => 'Putnik B',
        'broj_putnika' => 1,
        'status' => 'na_cekanju',
    ]);

    $response = $this->get('/rezervacije?datum_od=2026-09-01&datum_do=2026-09-30');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('filters.datum_od', '2026-09-01')
        ->where('filters.datum_do', '2026-09-30')
        ->has('rezervacije.data', 1)
        ->where('rezervacije.data.0.aranzman.naziv_putovanja', 'Barcelona')
    );
});

test('new rezervacija can be created', function () {
    Storage::fake('public');

    $aranzman = createArrangementForReservations(
        'RSV-09',
        'Kapadokija',
        '2026-10-01',
        '2026-10-08'
    );
    $paket = createPackageForArrangement($aranzman, 'All inclusive');

    $response = $this->post('/rezervacije', [
        'aranzman_id' => $aranzman->id,
        'klijenti' => [
            [
                'ime' => 'Amar',
                'prezime' => 'Test',
                'broj_dokumenta' => '0101000500006',
                'datum_rodjenja' => '2000-01-01',
                'adresa' => 'Ulica 1',
                'broj_telefona' => '061111222',
                'email' => 'amar@test.com',
                'fotografija' => UploadedFile::fake()->image('klijent.jpg'),
                'paket_id' => $paket->id,
            ],
        ],
        'status' => 'potvrdjena',
        'placanje' => 'placeno',
        'nacin_uplate' => 'cash',
        'napomena' => 'VIP putnik',
    ]);

    $response->assertRedirect('/rezervacije');

    $klijent = Client::where('broj_dokumenta', '0101000500006')->first();

    expect($klijent)->not->toBeNull();
    expect($klijent?->ime)->toBe('Amar');
    expect($klijent?->prezime)->toBe('Test');
    expect($klijent?->fotografija_putanja)->not->toBeNull();

    Storage::disk('public')->assertExists($klijent->fotografija_putanja);

    $rezervacija = Reservation::where('ime_prezime', 'Amar Test')->first();
    expect($rezervacija)->not->toBeNull();
    expect($rezervacija->aranzman_id)->toBe($aranzman->id);
    expect($rezervacija->klijent_id)->toBe($klijent->id);
    expect($rezervacija->broj_putnika)->toBe(1);
    expect($rezervacija->status)->toBe('potvrdjena');

    $stavka = ReservationClient::where('rezervacija_id', $rezervacija->id)->first();
    expect($stavka)->not->toBeNull();
    expect($stavka?->paket_id)->toBe($paket->id);
});

test('rezervacija edit page can be rendered', function () {
    $aranzman = createArrangementForReservations(
        'RSV-10',
        'Rim',
        '2026-06-10',
        '2026-06-15'
    );

    $rezervacija = Reservation::create([
        'aranzman_id' => $aranzman->id,
        'ime_prezime' => 'Edit Test',
        'broj_putnika' => 2,
        'status' => 'na_cekanju',
    ]);

    $response = $this->get("/rezervacije/{$rezervacija->id}/uredi");

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('reservations/edit')
        ->where('rezervacija.id', $rezervacija->id)
        ->has('aranzmani')
    );
});

test('rezervacija can be deleted', function () {
    $aranzman = createArrangementForReservations(
        'RSV-11',
        'Prag',
        '2026-11-10',
        '2026-11-15'
    );

    $rezervacija = Reservation::create([
        'aranzman_id' => $aranzman->id,
        'ime_prezime' => 'Delete Test',
        'broj_putnika' => 1,
        'status' => 'na_cekanju',
    ]);

    $response = $this->delete("/rezervacije/{$rezervacija->id}");

    $response->assertRedirect('/rezervacije');
    $this->assertSoftDeleted('reservations', [
        'id' => $rezervacija->id,
    ]);
});

test('rezervacija can be updated', function () {
    $aranzman = createArrangementForReservations(
        'RSV-12',
        'Amsterdam',
        '2026-12-01',
        '2026-12-05'
    );
    $paket = createPackageForArrangement($aranzman, 'Bez doručka');

    $rezervacija = Reservation::create([
        'aranzman_id' => $aranzman->id,
        'ime_prezime' => 'Old Name',
        'broj_putnika' => 1,
        'status' => 'na_cekanju',
    ]);

    $response = $this->patch("/rezervacije/{$rezervacija->id}", [
        'aranzman_id' => $aranzman->id,
        'klijenti' => [
            [
                'ime' => 'Novi',
                'prezime' => 'Putnik',
                'broj_dokumenta' => '0101000500014',
                'datum_rodjenja' => '2000-01-01',
                'adresa' => 'Nova adresa',
                'broj_telefona' => '060123456',
                'email' => 'novi@putnik.com',
                'paket_id' => $paket->id,
            ],
            [
                'ime' => 'Drugi',
                'prezime' => 'Putnik',
                'broj_dokumenta' => '0202000500015',
                'datum_rodjenja' => '2000-02-02',
                'adresa' => 'Druga adresa',
                'broj_telefona' => '060654321',
                'email' => 'drugi@putnik.com',
                'paket_id' => $paket->id,
            ],
        ],
        'status' => 'potvrdjena',
        'placanje' => 'placeno',
        'nacin_uplate' => 'cash',
        'napomena' => 'Ažurirano',
    ]);

    $response->assertRedirect('/rezervacije');

    $rezervacija->refresh();

    expect($rezervacija->ime_prezime)->toBe('Novi Putnik, Drugi Putnik');
    expect($rezervacija->broj_putnika)->toBe(2);
    expect($rezervacija->status)->toBe('potvrdjena');

    expect(ReservationClient::where('rezervacija_id', $rezervacija->id)->count())->toBe(2);
});

test('selected reservations export includes napomena in the Excel file', function () {
    Carbon::setTestNow('2026-06-02 09:30:00');
    Excel::fake();

    $aranzman = createArrangementForReservations(
        'RSV-13',
        'Istanbul',
        '2026-09-01',
        '2026-09-06'
    );
    $paket = createPackageForArrangement($aranzman, 'Premium');

    $rezervacija = Reservation::create([
        'order_num' => 713,
        'aranzman_id' => $aranzman->id,
        'ime_prezime' => 'Export Test',
        'broj_putnika' => 1,
        'status' => 'potvrdjena',
        'placanje' => 'placeno',
        'napomena' => 'Kasni dolazak putnika',
    ]);

    $klijent = Client::create([
        'ime' => 'Amina',
        'prezime' => 'Hodžić',
        'broj_dokumenta' => '0303000500016',
        'datum_rodjenja' => '1995-03-03',
        'adresa' => 'Test adresa 13',
        'city' => 'Tuzla',
        'broj_telefona' => '061333444',
        'email' => 'amina@example.com',
    ]);

    ReservationClient::create([
        'rezervacija_id' => $rezervacija->id,
        'klijent_id' => $klijent->id,
        'paket_id' => $paket->id,
    ]);

    $this->get('/rezervacije/izvoz/putnici?reservation_ids[]='.$rezervacija->id);

    Excel::assertDownloaded(
        'selected-reservations-clients-20260602_093000.xlsx',
        function (ReservationsClientsExport $export): bool {
            expect($export->headings())->toBe([
                'Redni broj',
                'Ime i prezime',
                'Grad',
                'Telefon',
                'Datum rođenja',
                'Broj dokumenta',
                'Package',
                'Broj rezervacije',
                'Napomena',
            ]);

            expect($export->collection()->all())->toBe([[
                'redni_broj' => 1,
                'ime_i_prezime' => 'Amina Hodžić',
                'grad' => 'Tuzla',
                'telefon' => '061333444',
                'datum_rodjenja' => '03.03.1995',
                'broj_dokumenta' => '0303000500016',
                'package' => 'Premium',
                'broj_rezervacije' => '713',
                'napomena' => 'Kasni dolazak putnika',
            ]]);

            return true;
        }
    );
});
