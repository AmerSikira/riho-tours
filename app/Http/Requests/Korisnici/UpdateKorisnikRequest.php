<?php

namespace App\Http\Requests\Korisnici;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateKorisnikRequest extends FormRequest
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
        $userId = $this->route('korisnik')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => [
                'nullable',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
            'role' => ['required', 'string', 'exists:roles,name'],
            'is_active' => ['required', 'boolean'],
            'potpis' => ['nullable', 'image', 'max:5120'],
            'pecat' => ['nullable', 'image', 'max:5120'],
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
            'password.confirmed' => 'Potvrda lozinke se ne poklapa.',
            'role.required' => 'Uloga je obavezna.',
            'role.exists' => 'Odabrana uloga nije validna.',
            'is_active.required' => 'Status korisnika je obavezan.',
            'potpis.image' => 'Potpis mora biti slika.',
            'potpis.max' => 'Potpis ne smije biti veći od 5 MB.',
            'pecat.image' => 'Pečat mora biti slika.',
            'pecat.max' => 'Pečat ne smije biti veći od 5 MB.',
        ];
    }
}
