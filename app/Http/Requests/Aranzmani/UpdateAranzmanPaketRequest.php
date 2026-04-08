<?php

namespace App\Http\Requests\Aranzmani;

use App\Models\Arrangement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAranzmanPaketRequest extends FormRequest
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
        $isSubagentArrangement = $this->isSubagentArrangement();

        return [
            'naziv' => ['required', 'string', 'max:255'],
            'opis' => ['nullable', 'string'],
            'cijena' => ['required', 'numeric', 'min:0'],
            'smjestaj_trosak' => [Rule::requiredIf(! $isSubagentArrangement), 'nullable', 'numeric', 'min:0'],
            'transport_trosak' => [Rule::requiredIf(! $isSubagentArrangement), 'nullable', 'numeric', 'min:0'],
            'fakultativne_stvari_trosak' => [Rule::requiredIf(! $isSubagentArrangement), 'nullable', 'numeric', 'min:0'],
            'ostalo_trosak' => [Rule::requiredIf(! $isSubagentArrangement), 'nullable', 'numeric', 'min:0'],
            'commission_percent' => [Rule::requiredIf($isSubagentArrangement), 'nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['required', 'boolean'],
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
            'naziv.required' => 'Naziv paketa je obavezan.',
            'cijena.required' => 'Cijena paketa je obavezna.',
            'cijena.min' => 'Cijena ne može biti manja od 0.',
            'smjestaj_trosak.required' => 'Trošak smještaja je obavezan.',
            'transport_trosak.required' => 'Trošak transporta je obavezan.',
            'fakultativne_stvari_trosak.required' => 'Trošak fakultativnih stvari je obavezan.',
            'ostalo_trosak.required' => 'Trošak ostalog je obavezan.',
            'commission_percent.required' => 'Procenat zarade je obavezan za subagentski aranžman.',
            'commission_percent.numeric' => 'Procenat zarade mora biti broj.',
            'commission_percent.min' => 'Procenat zarade ne može biti negativan.',
            'commission_percent.max' => 'Procenat zarade ne može biti veći od 100.',
            'is_active.required' => 'Status paketa je obavezan.',
        ];
    }

    /**
     * Resolve whether current arrangement is in subagent mode.
     */
    private function isSubagentArrangement(): bool
    {
        $arrangement = $this->route('aranzman');

        if ($arrangement instanceof Arrangement) {
            return (bool) $arrangement->subagentski_aranzman;
        }

        return false;
    }
}
