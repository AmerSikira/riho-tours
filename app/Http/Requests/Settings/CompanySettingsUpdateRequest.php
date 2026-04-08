<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class CompanySettingsUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['nullable', 'string', 'max:255'],
            'invoice_prefix' => ['nullable', 'string', 'max:50'],
            'company_id' => ['nullable', 'string', 'max:255'],
            'maticni_broj_subjekta_upisa' => ['nullable', 'string', 'max:255'],
            'pdv' => ['nullable', 'string', 'max:255'],
            'u_pdv_sistemu' => ['nullable', 'boolean'],
            'trn' => ['nullable', 'string', 'max:255'],
            'broj_kase' => ['nullable', 'string', 'max:255'],
            'banka' => ['nullable', 'string', 'max:255'],
            'iban' => ['nullable', 'string', 'max:255'],
            'swift' => ['nullable', 'string', 'max:255'],
            'osiguravajuce_drustvo' => ['nullable', 'string', 'max:255'],
            'polisa_osiguranja' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'image', 'max:5120'],
            'potpis' => ['nullable', 'image', 'max:5120'],
            'pecat' => ['nullable', 'image', 'max:5120'],
            'regenerate_api_token' => ['nullable', 'boolean'],
            'api_domain_1' => ['nullable', 'string', 'max:255'],
            'api_domain_2' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Additional validation checks.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $domains = collect([
                $this->input('api_domain_1'),
                $this->input('api_domain_2'),
            ])
                ->map(static fn ($value) => trim((string) $value))
                ->filter(static fn ($value) => $value !== '')
                ->values();

            if ($domains->count() > 2) {
                $validator->errors()->add('api_domain_1', 'Moguće je unijeti maksimalno 2 domene.');

                return;
            }

            if ($domains->unique()->count() !== $domains->count()) {
                $validator->errors()->add('api_domain_1', 'Domene moraju biti različite.');

                return;
            }

            $domainPattern = '/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i';
            foreach ($domains as $index => $domain) {
                $normalized = preg_replace('#^https?://#i', '', $domain) ?? $domain;
                $normalized = explode('/', $normalized)[0] ?? '';

                if (! preg_match($domainPattern, $normalized)) {
                    $field = $index === 0 ? 'api_domain_1' : 'api_domain_2';
                    $validator->errors()->add($field, 'Domena mora biti u formatu primjer.com.');
                }
            }
        });
    }
}
