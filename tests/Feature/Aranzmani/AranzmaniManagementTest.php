<?php

use App\Models\Arrangement;
use App\Models\ArrangementPackage;
use App\Models\ArrangementImage;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    Storage::fake('public');
});

function createArrangementForFilters(
    string $sifra,
    string $naziv,
    string $polazak,
    string $povratak
): Arrangement {
    return Arrangement::create([
        'sifra' => $sifra,
        'destinacija' => 'Test destinacija',
        'naziv_putovanja' => $naziv,
        'opis_putovanja' => 'Opis',
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

test('aranzmani index page can be rendered', function () {
    $response = $this->get('/aranzmani');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('arrangements/index')
        ->has('aranzmani.data')
        ->has('aranzmani.current_page')
        ->where('filters.pretraga', '')
        ->where('filters.datum_od', '')
        ->where('filters.datum_do', '')
    );
});

test('aranzmani can be filtered by name', function () {
    createArrangementForFilters('FLT-01', 'Ljeto u Antaliji', '2026-07-01', '2026-07-08');
    createArrangementForFilters('FLT-02', 'Zima u Beču', '2026-12-01', '2026-12-06');

    $response = $this->get('/aranzmani?pretraga=Antaliji');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('filters.pretraga', 'Antaliji')
        ->has('aranzmani.data', 1)
        ->where('aranzmani.data.0.naziv_putovanja', 'Ljeto u Antaliji')
    );
});

test('aranzmani can be filtered only by date from', function () {
    createArrangementForFilters('FLT-03', 'Krf ljeto', '2026-05-01', '2026-05-08');
    createArrangementForFilters('FLT-04', 'Rim jesen', '2026-09-10', '2026-09-15');

    $response = $this->get('/aranzmani?datum_od=2026-08-01');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('filters.datum_od', '2026-08-01')
        ->has('aranzmani.data', 1)
        ->where('aranzmani.data.0.naziv_putovanja', 'Rim jesen')
    );
});

test('aranzmani can be filtered only by date to', function () {
    createArrangementForFilters('FLT-05', 'Kipar proljece', '2026-04-01', '2026-04-07');
    createArrangementForFilters('FLT-06', 'Dubai zima', '2026-11-01', '2026-11-08');

    $response = $this->get('/aranzmani?datum_do=2026-06-01');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('filters.datum_do', '2026-06-01')
        ->has('aranzmani.data', 1)
        ->where('aranzmani.data.0.naziv_putovanja', 'Kipar proljece')
    );
});

test('aranzmani can be filtered by date range together', function () {
    createArrangementForFilters('FLT-07', 'Pariz proljece', '2026-03-10', '2026-03-15');
    createArrangementForFilters('FLT-08', 'Barselona jesen', '2026-09-10', '2026-09-15');

    $response = $this->get('/aranzmani?datum_od=2026-09-01&datum_do=2026-09-30');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('filters.datum_od', '2026-09-01')
        ->where('filters.datum_do', '2026-09-30')
        ->has('aranzmani.data', 1)
        ->where('aranzmani.data.0.naziv_putovanja', 'Barselona jesen')
    );
});

test('new aranzman can be created', function () {
    $response = $this->post('/aranzmani', [
        'sifra' => 'LTO-2026-01',
        'destinacija' => 'Turska',
        'naziv_putovanja' => 'Ljeto u Antaliji',
        'opis_putovanja' => 'Sedam noćenja u hotelu sa prevozom.',
        'datum_polaska' => '2026-07-01',
        'datum_povratka' => '2026-07-08',
        'tip_prevoza' => 'Avion',
        'tip_smjestaja' => 'Hotel',
        'napomena' => 'Rani booking',
        'is_active' => '1',
        'paketi' => [
            [
                'naziv' => 'Sa doručkom',
                'opis' => 'Doručak uključen',
                'cijena' => '129.50',
                'is_active' => '1',
            ],
        ],
        'slike' => [
            UploadedFile::fake()->image('cover.jpg'),
            UploadedFile::fake()->image('galerija.jpg'),
        ],
        'main_image_selection' => 'new:0',
    ]);

    $response->assertRedirect('/aranzmani');

    $aranzman = Arrangement::where('sifra', 'LTO-2026-01')->first();

    expect($aranzman)->not->toBeNull();
    expect($aranzman->destinacija)->toBe('Turska');
    expect($aranzman->is_active)->toBeTrue();
    expect($aranzman->datum_polaska->toDateString())->toBe('2026-07-01');
    expect($aranzman->datum_povratka->toDateString())->toBe('2026-07-08');

    $paket = ArrangementPackage::where('aranzman_id', $aranzman->id)->first();
    expect($paket)->not->toBeNull();
    expect($paket->naziv)->toBe('Sa doručkom');
    expect((float) $paket->cijena)->toBe(129.5);

    $slike = ArrangementImage::where('aranzman_id', $aranzman->id)->get();
    expect($slike)->toHaveCount(2);
    expect($slike->where('is_primary', true))->toHaveCount(1);
});

test('new aranzman can be created with only mandatory fields', function () {
    $response = $this->post('/aranzmani', [
        'sifra' => 'MIN-2026-01',
        'destinacija' => 'Grcka',
        'naziv_putovanja' => 'Minimalni unos',
        'datum_polaska' => '2026-06-01',
        'datum_povratka' => '2026-06-05',
        'paketi' => [
            [
                'naziv' => 'Osnovni paket',
                'cijena' => '250.00',
                'is_active' => '1',
            ],
        ],
    ]);

    $response->assertRedirect('/aranzmani');

    $aranzman = Arrangement::where('sifra', 'MIN-2026-01')->first();

    expect($aranzman)->not->toBeNull();
    expect($aranzman->opis_putovanja)->toBe('');
    expect($aranzman->tip_prevoza)->toBe('');
    expect($aranzman->tip_smjestaja)->toBe('');
    expect($aranzman->is_active)->toBeTrue();

    $paket = ArrangementPackage::where('aranzman_id', $aranzman->id)->first();
    expect($paket)->not->toBeNull();
    expect($paket->naziv)->toBe('Osnovni paket');
    expect((float) $paket->cijena)->toBe(250.0);
    expect($paket->is_active)->toBeTrue();

    $slike = ArrangementImage::where('aranzman_id', $aranzman->id)->get();
    expect($slike)->toHaveCount(0);
});

test('aranzman images can be updated and primary image reassigned', function () {
    $aranzman = Arrangement::create([
        'sifra' => 'LTO-2026-09',
        'destinacija' => 'Španija',
        'naziv_putovanja' => 'Costa Brava',
        'opis_putovanja' => 'Opis',
        'datum_polaska' => '2026-07-10',
        'datum_povratka' => '2026-07-17',
        'trajanje_dana' => 8,
        'tip_prevoza' => 'Avion',
        'tip_smjestaja' => 'Hotel',
        'napomena' => null,
        'is_active' => true,
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
    ]);

    $slika = ArrangementImage::create([
        'aranzman_id' => $aranzman->id,
        'putanja' => "aranzmani/{$aranzman->id}/old.jpg",
        'is_primary' => true,
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
    ]);

    ArrangementPackage::create([
        'aranzman_id' => $aranzman->id,
        'naziv' => 'Sa doručkom',
        'opis' => null,
        'is_active' => true,
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
    ]);

    $response = $this->patch("/aranzmani/{$aranzman->id}", [
        'sifra' => $aranzman->sifra,
        'destinacija' => $aranzman->destinacija,
        'naziv_putovanja' => $aranzman->naziv_putovanja,
        'opis_putovanja' => $aranzman->opis_putovanja,
        'datum_polaska' => $aranzman->datum_polaska->toDateString(),
        'datum_povratka' => $aranzman->datum_povratka->toDateString(),
        'tip_prevoza' => $aranzman->tip_prevoza,
        'tip_smjestaja' => $aranzman->tip_smjestaja,
        'napomena' => $aranzman->napomena,
        'is_active' => '1',
        'paketi' => [
            [
                'id' => $aranzman->packages()->first()->id,
                'naziv' => 'Sa doručkom',
                'opis' => null,
                'cijena' => '199.00',
                'is_active' => '1',
            ],
        ],
        'zadrzane_slike' => [$slika->id],
        'nove_slike' => [
            UploadedFile::fake()->image('nova.jpg'),
        ],
        'main_image_selection' => 'new:0',
    ]);

    $response->assertRedirect('/aranzmani');

    $aranzman->refresh();
    expect($aranzman->datum_polaska->toDateString())->toBe('2026-07-10');
    expect($aranzman->datum_povratka->toDateString())->toBe('2026-07-17');
    expect((float) $aranzman->packages()->first()->cijena)->toBe(199.0);

    $slike = ArrangementImage::where('aranzman_id', $aranzman->id)->get();
    expect($slike)->toHaveCount(2);
    expect($slike->where('is_primary', true))->toHaveCount(1);
});
