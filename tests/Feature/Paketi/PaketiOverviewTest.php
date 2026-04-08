<?php

use App\Models\Arrangement;
use App\Models\ArrangementPackage;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $user = User::factory()->create();

    $this->actingAs($user);
});

test('paketi index page can be rendered', function () {
    $aranzman = Arrangement::create([
        'sifra' => 'LTO-2026-10',
        'destinacija' => 'Bali',
        'naziv_putovanja' => 'Bali ljeto',
        'opis_putovanja' => 'Opis putovanja',
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

    ArrangementPackage::create([
        'aranzman_id' => $aranzman->id,
        'naziv' => 'Sa doručkom',
        'opis' => 'Opis paketa',
        'is_active' => true,
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
    ]);

    $response = $this->get('/paketi');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('packages/index')
        ->has('paketi.data')
        ->has('paketi.current_page')
    );
});

test('paket details page shows linked aranzmani', function () {
    $aranzman1 = Arrangement::create([
        'sifra' => 'LTO-2026-11',
        'destinacija' => 'Tajland',
        'naziv_putovanja' => 'Phuket odmor',
        'opis_putovanja' => 'Opis putovanja',
        'datum_polaska' => '2026-08-01',
        'datum_povratka' => '2026-08-08',
        'trajanje_dana' => 8,
        'tip_prevoza' => 'Avion',
        'tip_smjestaja' => 'Hotel',
        'napomena' => null,
        'is_active' => true,
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
    ]);

    $aranzman2 = Arrangement::create([
        'sifra' => 'LTO-2026-12',
        'destinacija' => 'Maldivi',
        'naziv_putovanja' => 'Maldivi premium',
        'opis_putovanja' => 'Opis putovanja',
        'datum_polaska' => '2026-09-01',
        'datum_povratka' => '2026-09-10',
        'trajanje_dana' => 10,
        'tip_prevoza' => 'Avion',
        'tip_smjestaja' => 'Resort',
        'napomena' => null,
        'is_active' => true,
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
    ]);

    $paket = ArrangementPackage::create([
        'aranzman_id' => $aranzman1->id,
        'naziv' => 'All inclusive',
        'opis' => 'Opis paketa',
        'is_active' => true,
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
    ]);

    ArrangementPackage::create([
        'aranzman_id' => $aranzman2->id,
        'naziv' => 'All inclusive',
        'opis' => 'Opis paketa',
        'is_active' => true,
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
    ]);

    $response = $this->get("/paketi/{$paket->id}");

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('packages/show')
        ->where('paket.naziv', 'All inclusive')
        ->has('aranzmani', 2)
    );
});
