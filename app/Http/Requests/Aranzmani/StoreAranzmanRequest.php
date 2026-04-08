<?php

namespace App\Http\Requests\Aranzmani;

use Illuminate\Foundation\Http\FormRequest;

class StoreAranzmanRequest extends FormRequest
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
            'sifra' => ['required', 'string', 'max:100', 'unique:arrangements,sifra'],
            'destinacija' => ['required', 'string', 'max:255'],
            'naziv_putovanja' => ['required', 'string', 'max:255'],
            'opis_putovanja' => ['nullable', 'string'],
            'plan_putovanja' => ['nullable', 'string'],
            'datum_polaska' => ['required', 'date'],
            'datum_povratka' => ['required', 'date', 'after_or_equal:datum_polaska'],
            'tip_prevoza' => ['nullable', 'string', 'max:100'],
            'tip_smjestaja' => ['nullable', 'string', 'max:100'],
            'napomena' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'subagentski_aranzman' => ['nullable', 'boolean'],
            'supplier_id' => ['nullable', 'uuid', 'exists:suppliers,id'],
            'paketi' => ['required', 'array', 'min:1'],
            'paketi.*.id' => ['nullable', 'string'],
            'paketi.*.naziv' => ['required', 'string', 'max:255'],
            'paketi.*.opis' => ['nullable', 'string'],
            'paketi.*.cijena' => ['required', 'numeric', 'min:0'],
            'paketi.*.smjestaj_trosak' => ['nullable', 'numeric', 'min:0'],
            'paketi.*.transport_trosak' => ['nullable', 'numeric', 'min:0'],
            'paketi.*.fakultativne_stvari_trosak' => ['nullable', 'numeric', 'min:0'],
            'paketi.*.ostalo_trosak' => ['nullable', 'numeric', 'min:0'],
            'paketi.*.commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'paketi.*.is_active' => ['required', 'boolean'],
            'slike' => ['nullable', 'array'],
            'slike.*' => ['image', 'max:5120'],
            'main_image_selection' => ['nullable', 'regex:/^new:\d+$/'],
        ];
    }

    /**
     * Get custom validation messages for end users.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sifra.required' => 'Šifra aranžmana je obavezna.',
            'sifra.unique' => 'Aranžman sa ovom šifrom već postoji.',
            'destinacija.required' => 'Destinacija je obavezna.',
            'naziv_putovanja.required' => 'Naziv putovanja je obavezan.',
            'datum_polaska.required' => 'Datum polaska je obavezan.',
            'datum_povratka.required' => 'Datum povratka je obavezan.',
            'datum_povratka.after_or_equal' => 'Datum povratka mora biti nakon polaska.',
            'supplier_id.exists' => 'Odabrani dobavljač nije validan.',
            'paketi.required' => 'Dodajte najmanje jedan paket.',
            'paketi.min' => 'Dodajte najmanje jedan paket.',
            'paketi.*.naziv.required' => 'Naziv paketa je obavezan.',
            'paketi.*.cijena.required' => 'Cijena paketa je obavezna.',
            'paketi.*.commission_percent.numeric' => 'Procenat zarade mora biti broj.',
            'paketi.*.commission_percent.min' => 'Procenat zarade ne može biti negativan.',
            'paketi.*.commission_percent.max' => 'Procenat zarade ne može biti veći od 100.',
            'paketi.*.is_active.required' => 'Status paketa je obavezan.',
            'slike.*.image' => 'Svaka datoteka mora biti validna slika.',
            'slike.*.max' => 'Slika može imati maksimalno 5MB.',
        ];
    }
}
