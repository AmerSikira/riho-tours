<?php

use App\Models\Arrangement;
use App\Models\ArrangementPackage;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $user = User::factory()->create();

    $this->actingAs($user);
});

test('paketi index page can be rendered for aranzman', function () {
    $aranzman = Arrangement::create([
        'sifra' => 'LTO-2026-02',
        'destinacija' => 'Grčka',
        'naziv_putovanja' => 'Ljeto na Krfu',
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

    $response = $this->get("/aranzmani/{$aranzman->id}/paketi");

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('arrangements/packages/index')
        ->has('paketi')
    );
});

test('new paket can be created for aranzman', function () {
    $aranzman = Arrangement::create([
        'sifra' => 'LTO-2026-03',
        'destinacija' => 'Egipat',
        'naziv_putovanja' => 'Hurgada odmor',
        'opis_putovanja' => 'Opis putovanja',
        'datum_polaska' => '2026-09-01',
        'datum_povratka' => '2026-09-08',
        'trajanje_dana' => 8,
        'tip_prevoza' => 'Avion',
        'tip_smjestaja' => 'Hotel',
        'napomena' => null,
        'is_active' => true,
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
    ]);

    $response = $this->post("/aranzmani/{$aranzman->id}/paketi", [
        'naziv' => 'Sa doručkom',
        'opis' => 'Uključuje doručak svaki dan',
        'cijena' => '299.90',
        'is_active' => '1',
    ]);

    $response->assertRedirect("/aranzmani/{$aranzman->id}/paketi");

    $paket = ArrangementPackage::where('aranzman_id', $aranzman->id)
        ->where('naziv', 'Sa doručkom')
        ->first();

    expect($paket)->not->toBeNull();
    expect($paket->is_active)->toBeTrue();
    expect((float) $paket->cijena)->toBe(299.9);
});

test('paket can be updated for aranzman', function () {
    $aranzman = Arrangement::create([
        'sifra' => 'LTO-2026-04',
        'destinacija' => 'Španija',
        'naziv_putovanja' => 'Barcelona city break',
        'opis_putovanja' => 'Opis putovanja',
        'datum_polaska' => '2026-10-01',
        'datum_povratka' => '2026-10-05',
        'trajanje_dana' => 5,
        'tip_prevoza' => 'Avion',
        'tip_smjestaja' => 'Hotel',
        'napomena' => null,
        'is_active' => true,
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
    ]);

    $paket = ArrangementPackage::create([
        'aranzman_id' => $aranzman->id,
        'naziv' => 'Bez doručka',
        'opis' => 'Osnovni paket',
        'is_active' => true,
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
    ]);

    $response = $this->patch("/aranzmani/{$aranzman->id}/paketi/{$paket->id}", [
        'naziv' => 'Sa doručkom',
        'opis' => 'Ažuriran paket',
        'cijena' => '349.00',
        'is_active' => '0',
    ]);

    $response->assertRedirect("/aranzmani/{$aranzman->id}/paketi");

    $paket->refresh();

    expect($paket->naziv)->toBe('Sa doručkom');
    expect($paket->is_active)->toBeFalse();
    expect((float) $paket->cijena)->toBe(349.0);
});

test('paket can be deleted for aranzman', function () {
    $aranzman = Arrangement::create([
        'sifra' => 'LTO-2026-05',
        'destinacija' => 'Italija',
        'naziv_putovanja' => 'Rim i Firenca',
        'opis_putovanja' => 'Opis putovanja',
        'datum_polaska' => '2026-11-01',
        'datum_povratka' => '2026-11-06',
        'trajanje_dana' => 6,
        'tip_prevoza' => 'Bus',
        'tip_smjestaja' => 'Apartman',
        'napomena' => null,
        'is_active' => true,
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
    ]);

    $paket = ArrangementPackage::create([
        'aranzman_id' => $aranzman->id,
        'naziv' => 'Polupansion',
        'opis' => null,
        'is_active' => true,
        'created_by' => auth()->id(),
        'updated_by' => auth()->id(),
    ]);

    $response = $this->delete("/aranzmani/{$aranzman->id}/paketi/{$paket->id}");

    $response->assertRedirect("/aranzmani/{$aranzman->id}/paketi");
    $this->assertSoftDeleted('arrangement_packages', [
        'id' => $paket->id,
    ]);
});
