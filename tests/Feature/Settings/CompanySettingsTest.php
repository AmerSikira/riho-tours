<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('company settings page can be rendered', function () {
    $response = $this->get('/postavke');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('settings/company')
        ->has('setting')
    );
});

test('company settings can be stored with logo, signature and stamp', function () {
    Storage::fake('public');

    $response = $this->post('/postavke', [
        'company_name' => 'Travel Co',
        'company_id' => '12345',
        'pdv' => 'PDV-123',
        'u_pdv_sistemu' => '0',
        'trn' => 'TRN-987',
        'email' => 'info@travelco.ba',
        'phone' => '+38761111222',
        'address' => 'Main street 1',
        'city' => 'Sarajevo',
        'zip' => '71000',
        'logo' => UploadedFile::fake()->image('logo.png'),
        'potpis' => UploadedFile::fake()->image('potpis.png'),
        'pecat' => UploadedFile::fake()->image('pecat.png'),
    ]);

    $response->assertRedirect('/postavke');

    $setting = Setting::query()->first();

    expect($setting)->not->toBeNull();
    expect($setting?->company_name)->toBe('Travel Co');
    expect($setting?->u_pdv_sistemu)->toBeFalse();
    expect($setting?->email)->toBe('info@travelco.ba');
    expect($setting?->logo_path)->not->toBeNull();
    expect($setting?->potpis_path)->not->toBeNull();
    expect($setting?->pecat_path)->not->toBeNull();

    Storage::disk('public')->assertExists($setting->logo_path);
    Storage::disk('public')->assertExists($setting->potpis_path);
    Storage::disk('public')->assertExists($setting->pecat_path);
});
