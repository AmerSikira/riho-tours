<?php

use App\Models\Arrangement;
use App\Models\ArrangementPackage;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\ReservationClient;
use App\Models\Setting;
use Database\Seeders\DatabaseSeeder;

it('seeds snapshot data for settings, arrangements, packages, clients, and reservations', function (): void {
    $this->seed(DatabaseSeeder::class);

    $setting = Setting::query()->first();

    expect($setting)->not->toBeNull();
    expect($setting?->company_name)->toBe('Riho Turs');
    expect($setting?->company_id)->toBe('12345678912345');
    expect($setting?->pdv)->toBe('1234567891234');
    expect($setting?->trn)->toBe('12345678912345');
    expect($setting?->city)->toBe('Kakanj');

    $aranzman = Arrangement::query()->where('sifra', 'LTO-123')->first();
    expect($aranzman)->not->toBeNull();

    $paket = ArrangementPackage::query()
        ->where('aranzman_id', $aranzman?->id)
        ->where('naziv', 'Lux 123')
        ->first();
    expect($paket)->not->toBeNull();
    expect((float) $paket?->cijena)->toBe(250.0);

    $amer = Client::query()->where('broj_dokumenta', '1111993190004')->first();
    $merjmea = Client::query()->where('broj_dokumenta', '1910994195644')->first();
    expect($amer)->not->toBeNull();
    expect($merjmea)->not->toBeNull();

    $rezervacija = Reservation::query()
        ->where('aranzman_id', $aranzman?->id)
        ->where('status', 'potvrdjena')
        ->first();
    expect($rezervacija)->not->toBeNull();
    expect($rezervacija?->broj_putnika)->toBe(2);

    $stavkeCount = ReservationClient::query()
        ->where('rezervacija_id', $rezervacija?->id)
        ->count();
    expect($stavkeCount)->toBe(2);

    expect(Arrangement::query()->count())->toBeGreaterThanOrEqual(12);
    expect(ArrangementPackage::query()->count())->toBeGreaterThanOrEqual(35);
    expect(Client::query()->count())->toBeGreaterThanOrEqual(60);
    expect(Reservation::query()->count())->toBeGreaterThanOrEqual(40);
    expect(ReservationClient::query()->count())->toBeGreaterThanOrEqual(100);
});
