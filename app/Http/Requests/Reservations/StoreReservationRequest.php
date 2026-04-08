<?php

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservationRequest extends FormRequest
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
            'aranzman_id' => ['required', 'exists:arrangements,id'],
            'contract_template_id' => ['nullable', 'exists:contract_templates,id'],
            'klijenti' => ['required', 'array', 'min:1'],
            'klijenti.*.ime' => ['required', 'string', 'max:255'],
            'klijenti.*.prezime' => ['required', 'string', 'max:255'],
            'klijenti.*.broj_dokumenta' => ['nullable', 'string'],
            'klijenti.*.datum_rodjenja' => ['nullable', 'date'],
            'klijenti.*.adresa' => ['required', 'string', 'max:255'],
            'klijenti.*.city' => ['nullable', 'string', 'max:255'],
            'klijenti.*.broj_telefona' => ['required', 'string', 'max:50'],
            'klijenti.*.email' => ['nullable', 'email', 'max:255'],
            'klijenti.*.fotografija' => ['nullable', 'image', 'max:5120'],
            'klijenti.*.dodatno_na_cijenu' => ['nullable', 'numeric', 'min:0'],
            'klijenti.*.popust' => ['nullable', 'numeric', 'min:0'],
            'klijenti.*.paket_id' => [
                'required',
                Rule::exists('arrangement_packages', 'id')->where(function ($query) {
                    $query
                        ->where('aranzman_id', (string) $this->input('aranzman_id'))
                        ->whereNull('deleted_at');
                }),
            ],
            'status' => ['required', 'in:na_cekanju,potvrdjena,otkazana'],
            'broj_fiskalnog_racuna' => ['nullable', 'string', 'max:100'],
            'placanje' => ['required', Rule::in(['placeno', 'na_rate', 'na_odgodeno'])],
            'broj_rata' => ['nullable', 'integer', 'min:2', 'max:36', 'required_if:placanje,na_rate'],
            'rate' => ['nullable', 'array', 'required_if:placanje,na_rate'],
            'rate.*.datum_predracuna' => ['nullable', 'date'],
            'rate.*.iznos_predracuna' => ['nullable', 'numeric', 'min:0'],
            'rate.*.datum_uplate' => ['nullable', 'date'],
            'rate.*.iznos_uplate' => ['nullable', 'numeric', 'min:0'],
            'rate.*.datum_avansne_fakture' => ['nullable', 'date'],
            'rate.*.iznos_avansne_fakture' => ['nullable', 'numeric', 'min:0'],
            'napomena' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $placanje = (string) $this->input('placanje');

            if ($placanje !== 'na_rate') {
                return;
            }

            if (! $this->canManageInstallments()) {
                $validator->errors()->add(
                    'placanje',
                    'Nemate dozvolu za upravljanje ratama rezervacije.'
                );

                return;
            }

            $brojRata = (int) $this->input('broj_rata');
            $rate = $this->input('rate');

            if (! is_array($rate)) {
                return;
            }

            if (count($rate) !== $brojRata) {
                $validator->errors()->add(
                    'rate',
                    'Broj unešenih rata mora odgovarati odabranom broju rata.'
                );
            }
        });
    }

    /**
     * Check if the current user can manage reservation installments.
     */
    private function canManageInstallments(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        if ($this->routeIs('rezervacije.store')) {
            return $user->can('dodavanje rata rezervacija');
        }

        if ($this->routeIs('rezervacije.update')) {
            return $user->can('uređivanje rata rezervacija');
        }

        return $user->can('dodavanje rata rezervacija')
            || $user->can('uređivanje rata rezervacija');
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'aranzman_id.required' => 'Aranžman je obavezan.',
            'aranzman_id.exists' => 'Odabrani aranžman nije validan.',
            'contract_template_id.exists' => 'Odabrani predložak ugovora nije validan.',
            'klijenti.required' => 'Potrebno je dodati najmanje jednog klijenta.',
            'klijenti.min' => 'Potrebno je dodati najmanje jednog klijenta.',
            'klijenti.*.ime.required' => 'Ime klijenta je obavezno.',
            'klijenti.*.prezime.required' => 'Prezime klijenta je obavezno.',
            'klijenti.*.adresa.required' => 'Adresa je obavezna.',
            'klijenti.*.broj_telefona.required' => 'Broj telefona je obavezan.',
            'klijenti.*.fotografija.image' => 'Fotografija mora biti validna slika.',
            'klijenti.*.fotografija.max' => 'Fotografija može imati maksimalno 5MB.',
            'klijenti.*.dodatno_na_cijenu.numeric' => 'Dodatno na cijenu mora biti broj.',
            'klijenti.*.dodatno_na_cijenu.min' => 'Dodatno na cijenu ne može biti negativno.',
            'klijenti.*.popust.numeric' => 'Popust mora biti broj.',
            'klijenti.*.popust.min' => 'Popust ne može biti negativan.',
            'klijenti.*.paket_id.required' => 'Paket je obavezan za svakog klijenta.',
            'klijenti.*.paket_id.exists' => 'Odabrani paket ne pripada izabranom aranžmanu.',
            'status.required' => 'Status rezervacije je obavezan.',
            'broj_fiskalnog_racuna.max' => 'Broj fiskalnog računa može imati maksimalno 100 karaktera.',
            'placanje.required' => 'Način plaćanja je obavezan.',
            'placanje.in' => 'Odabrani način plaćanja nije validan.',
            'broj_rata.required_if' => 'Broj rata je obavezan kada je odabrano plaćanje na rate.',
            'broj_rata.integer' => 'Broj rata mora biti cijeli broj.',
            'broj_rata.min' => 'Minimalan broj rata je 2.',
            'broj_rata.max' => 'Maksimalan broj rata je 36.',
            'rate.required_if' => 'Potrebno je unijeti rate za odabrano plaćanje na rate.',
            'rate.array' => 'Podaci o ratama nisu u ispravnom formatu.',
            'rate.*.datum_predracuna.date' => 'Datum predračuna mora biti validan datum.',
            'rate.*.iznos_predracuna.numeric' => 'Iznos predračuna mora biti broj.',
            'rate.*.iznos_predracuna.min' => 'Iznos predračuna ne može biti negativan.',
            'rate.*.datum_uplate.date' => 'Datum uplate rate mora biti validan datum.',
            'rate.*.iznos_uplate.numeric' => 'Iznos uplate mora biti broj.',
            'rate.*.iznos_uplate.min' => 'Iznos uplate ne može biti negativan.',
            'rate.*.datum_avansne_fakture.date' => 'Datum avansne fakture mora biti validan datum.',
            'rate.*.iznos_avansne_fakture.numeric' => 'Iznos avansne fakture mora biti broj.',
            'rate.*.iznos_avansne_fakture.min' => 'Iznos avansne fakture ne može biti negativan.',
        ];
    }
}
