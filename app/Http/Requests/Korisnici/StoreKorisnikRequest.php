<?php

namespace App\Http\Requests\Korisnici;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreKorisnikRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
            'role' => ['required', 'string', 'exists:roles,name'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * Get custom validation messages for a better user-facing experience.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Ime i prezime je obavezno.',
            'email.required' => 'Email adresa je obavezna.',
            'email.email' => 'Unesite ispravnu email adresu.',
            'email.unique' => 'Korisnik sa ovom email adresom već postoji.',
            'password.required' => 'Lozinka je obavezna.',
            'password.confirmed' => 'Potvrda lozinke se ne poklapa.',
            'role.required' => 'Uloga je obavezna.',
            'role.exists' => 'Odabrana uloga nije validna.',
            'is_active.required' => 'Status korisnika je obavezan.',
        ];
    }
}
