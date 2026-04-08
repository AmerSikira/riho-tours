<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $user = User::factory()->create();

    $this->actingAs($user);
});

test('klijenti index page can be rendered', function () {
    Client::create([
        'ime' => 'Amar',
        'prezime' => 'Sikira',
        'broj_dokumenta' => '0101000500001',
        'datum_rodjenja' => '2000-01-01',
        'adresa' => 'Sarajevo 1',
        'broj_telefona' => '061000000',
        'email' => 'amar@example.com',
    ]);

    $response = $this->get('/klijenti');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('clients/index')
        ->has('klijenti.data', 1)
        ->where('klijenti.data.0.ime', 'Amar')
        ->where('filters.pretraga', '')
    );
});

test('klijenti can be filtered by first or last name', function () {
    Client::create([
        'ime' => 'Lejla',
        'prezime' => 'M',
        'broj_dokumenta' => '0202990500002',
        'datum_rodjenja' => '1999-02-02',
        'adresa' => 'Mostar 1',
        'broj_telefona' => '062000000',
        'email' => null,
    ]);

    Client::create([
        'ime' => 'Marko',
        'prezime' => 'Lejlic',
        'broj_dokumenta' => '0303990500003',
        'datum_rodjenja' => '1999-03-03',
        'adresa' => 'Tuzla 1',
        'broj_telefona' => '063000000',
        'email' => null,
    ]);

    $response = $this->get('/klijenti?pretraga=Lejl');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('filters.pretraga', 'Lejl')
        ->has('klijenti.data', 2)
    );
});

test('klijenti can be filtered by broj_dokumenta', function () {
    Client::create([
        'ime' => 'Ajla',
        'prezime' => 'H',
        'broj_dokumenta' => '1111100500001',
        'datum_rodjenja' => '2000-05-11',
        'adresa' => 'Adresa 1',
        'broj_telefona' => '061111111',
        'email' => null,
    ]);

    Client::create([
        'ime' => 'Nedim',
        'prezime' => 'K',
        'broj_dokumenta' => '2222200500002',
        'datum_rodjenja' => '2000-05-12',
        'adresa' => 'Adresa 2',
        'broj_telefona' => '062222222',
        'email' => null,
    ]);

    $response = $this->get('/klijenti?pretraga=11111');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->where('filters.pretraga', '11111')
        ->has('klijenti.data', 1)
        ->where('klijenti.data.0.broj_dokumenta', '1111100500001')
    );
});

test('klijenti search by partial broj_dokumenta returns autocomplete payload', function () {
    Client::create([
        'ime' => 'Adnan',
        'prezime' => 'A',
        'broj_dokumenta' => '1234500500001',
        'datum_rodjenja' => '2000-05-12',
        'adresa' => 'Adresa 1',
        'broj_telefona' => '061111111',
        'email' => 'adnan@example.com',
    ]);

    Client::create([
        'ime' => 'Belma',
        'prezime' => 'B',
        'broj_dokumenta' => '9999900500002',
        'datum_rodjenja' => '2000-05-12',
        'adresa' => 'Adresa 2',
        'broj_telefona' => '062222222',
        'email' => 'belma@example.com',
    ]);

    $response = $this->get('/klijenti/pretraga?broj_dokumenta=12345');

    $response->assertOk();
    $response->assertJsonCount(1);
    $response->assertJsonPath('0.broj_dokumenta', '1234500500001');
    $response->assertJsonPath('0.ime', 'Adnan');
});

test('klijent edit page can be rendered', function () {
    $klijent = Client::create([
        'ime' => 'Sara',
        'prezime' => 'Z',
        'broj_dokumenta' => '0101000500099',
        'datum_rodjenja' => '2000-01-01',
        'adresa' => 'Adresa',
        'broj_telefona' => '061999999',
        'email' => 'sara@example.com',
    ]);

    $response = $this->get("/klijenti/{$klijent->id}/uredi");

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('clients/edit')
        ->where('klijent.id', $klijent->id)
    );
});

test('klijent can be updated', function () {
    Storage::fake('public');

    $klijent = Client::create([
        'ime' => 'Old',
        'prezime' => 'Client',
        'broj_dokumenta' => '0101000500088',
        'datum_rodjenja' => '2000-01-01',
        'adresa' => 'Old adresa',
        'broj_telefona' => '061111000',
        'email' => 'old@example.com',
    ]);

    $response = $this->patch("/klijenti/{$klijent->id}", [
        'ime' => 'New',
        'prezime' => 'Client',
        'broj_dokumenta' => '0101000500088',
        'datum_rodjenja' => '2000-02-02',
        'adresa' => 'Nova adresa',
        'broj_telefona' => '061222000',
        'email' => 'new@example.com',
        'fotografija' => UploadedFile::fake()->image('nova.jpg'),
    ]);

    $response->assertRedirect('/klijenti');

    $klijent->refresh();

    expect($klijent->ime)->toBe('New');
    expect($klijent->datum_rodjenja?->toDateString())->toBe('2000-02-02');
    expect($klijent->fotografija_putanja)->not->toBeNull();
});

test('klijent can be deleted', function () {
    $klijent = Client::create([
        'ime' => 'Delete',
        'prezime' => 'Me',
        'broj_dokumenta' => '0101000500077',
        'datum_rodjenja' => '2000-01-01',
        'adresa' => 'Adresa',
        'broj_telefona' => '061777777',
        'email' => null,
    ]);

    $response = $this->delete("/klijenti/{$klijent->id}");

    $response->assertRedirect('/klijenti');
    $this->assertSoftDeleted('clients', [
        'id' => $klijent->id,
    ]);
});
