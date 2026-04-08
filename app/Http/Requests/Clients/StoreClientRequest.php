<?php

namespace App\Http\Requests\Clients;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
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
            'ime' => ['required', 'string', 'max:255'],
            'prezime' => ['required', 'string', 'max:255'],
            'broj_dokumenta' => ['nullable', 'string'],
            'datum_rodjenja' => ['nullable', 'date'],
            'adresa' => ['required', 'string', 'max:255'],
            'broj_telefona' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'fotografija' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
