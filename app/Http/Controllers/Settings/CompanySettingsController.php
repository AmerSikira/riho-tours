<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\CompanySettingsUpdateRequest;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CompanySettingsController extends Controller
{
    /**
     * Show company settings page.
     */
    public function edit(Request $request): Response
    {
        $setting = Setting::query()->first() ?? Setting::query()->create([]);
        $user = $request->user();
        $domains = is_array($user?->api_allowed_domains) ? array_values($user->api_allowed_domains) : [];

        return Inertia::render('settings/company', [
            'setting' => [
                'company_name' => $setting->company_name ?? '',
                'invoice_prefix' => $setting->invoice_prefix ?? '',
                'company_id' => $setting->company_id ?? '',
                'maticni_broj_subjekta_upisa' => $setting->maticni_broj_subjekta_upisa ?? '',
                'pdv' => $setting->pdv ?? '',
                'u_pdv_sistemu' => (bool) ($setting->u_pdv_sistemu ?? true),
                'trn' => $setting->trn ?? '',
                'broj_kase' => $setting->broj_kase ?? '',
                'banka' => $setting->banka ?? '',
                'iban' => $setting->iban ?? '',
                'swift' => $setting->swift ?? '',
                'osiguravajuce_drustvo' => $setting->osiguravajuce_drustvo ?? '',
                'polisa_osiguranja' => $setting->polisa_osiguranja ?? '',
                'email' => $setting->email ?? '',
                'phone' => $setting->phone ?? '',
                'address' => $setting->address ?? '',
                'city' => $setting->city ?? '',
                'zip' => $setting->zip ?? '',
                'logo_url' => $setting->logo_path
                    ? Storage::disk('public')->url($setting->logo_path)
                    : null,
                'potpis_url' => $setting->potpis_path
                    ? Storage::disk('public')->url($setting->potpis_path)
                    : null,
                'pecat_url' => $setting->pecat_path
                    ? Storage::disk('public')->url($setting->pecat_path)
                    : null,
                'api_key_active' => ! empty($user?->api_token_hash),
                'api_key_last_used_at' => $user?->api_token_last_used_at?->toIso8601String(),
                'api_domain_1' => $domains[0] ?? '',
                'api_domain_2' => $domains[1] ?? '',
            ],
            'status' => session('status'),
            'generated_api_token' => session('generated_api_token'),
        ]);
    }

    /**
     * Update company settings.
     */
    public function update(CompanySettingsUpdateRequest $request): RedirectResponse
    {
        $setting = Setting::query()->first() ?? Setting::query()->create([]);
        $validated = $request->validated();
        $user = $request->user();

        $logoPath = $setting->logo_path;
        if ($request->hasFile('logo')) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }

            $logoPath = $request->file('logo')?->store('settings', 'public');
        }

        $potpisPath = $setting->potpis_path;
        if ($request->hasFile('potpis')) {
            if ($potpisPath) {
                Storage::disk('public')->delete($potpisPath);
            }

            $potpisPath = $request->file('potpis')?->store('settings', 'public');
        }

        $pecatPath = $setting->pecat_path;
        if ($request->hasFile('pecat')) {
            if ($pecatPath) {
                Storage::disk('public')->delete($pecatPath);
            }

            $pecatPath = $request->file('pecat')?->store('settings', 'public');
        }

        $setting->update([
            'company_name' => $validated['company_name'] ?? null,
            'invoice_prefix' => array_key_exists('invoice_prefix', $validated)
                ? trim((string) ($validated['invoice_prefix'] ?? ''))
                : 'WEB',
            'company_id' => $validated['company_id'] ?? null,
            'maticni_broj_subjekta_upisa' => $validated['maticni_broj_subjekta_upisa'] ?? null,
            'pdv' => $validated['pdv'] ?? null,
            'u_pdv_sistemu' => array_key_exists('u_pdv_sistemu', $validated)
                ? (bool) $validated['u_pdv_sistemu']
                : true,
            'trn' => $validated['trn'] ?? null,
            'broj_kase' => $validated['broj_kase'] ?? null,
            'banka' => $validated['banka'] ?? null,
            'iban' => $validated['iban'] ?? null,
            'swift' => $validated['swift'] ?? null,
            'osiguravajuce_drustvo' => $validated['osiguravajuce_drustvo'] ?? null,
            'polisa_osiguranja' => $validated['polisa_osiguranja'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'zip' => $validated['zip'] ?? null,
            'logo_path' => $logoPath,
            'potpis_path' => $potpisPath,
            'pecat_path' => $pecatPath,
        ]);

        $domains = collect([
            $validated['api_domain_1'] ?? '',
            $validated['api_domain_2'] ?? '',
        ])
            ->map(static fn ($value) => trim((string) $value))
            ->filter(static fn ($value) => $value !== '')
            ->map(function (string $domain): string {
                $withoutScheme = preg_replace('#^https?://#i', '', $domain) ?? $domain;

                return strtolower(explode('/', $withoutScheme)[0] ?? '');
            })
            ->values()
            ->all();

        $generatedToken = null;
        if ($user) {
            $updates = [
                'api_allowed_domains' => $domains,
            ];

            if ((bool) ($validated['regenerate_api_token'] ?? false)) {
                $generatedToken = Str::random(64);
                $updates['api_token_hash'] = hash('sha256', $generatedToken);
                $updates['api_token_last_used_at'] = null;
            }

            $user->forceFill($updates)->save();
        }

        $redirect = to_route('company-settings.edit')->with('status', 'Postavke su sačuvane.');
        if ($generatedToken !== null) {
            $redirect->with('generated_api_token', $generatedToken);
        }

        return $redirect;
    }
}
