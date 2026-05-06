<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReservationsApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 20), 1), 100);
        $search = trim((string) $request->string('search'));

        $reservations = Reservation::query()
            ->with([
                'arrangement:id,sifra,naziv_putovanja,destinacija,datum_polaska,datum_povratka',
                'reservationClients.client:id,ime,prezime,email,broj_telefona',
                'reservationClients.package:id,naziv,cijena',
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('ime_prezime', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('telefon', 'like', "%{$search}%");
                });
            })
            ->latest('created_at')
            ->paginate($perPage);

        return response()->json($reservations);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateReservation($request);

        $reservation = DB::transaction(function () use ($request, $validated): Reservation {
            $clientsPayload = $validated['klijenti'];

            $reservation = Reservation::query()->create([
                'aranzman_id' => $validated['aranzman_id'],
                'contract_template_id' => $validated['contract_template_id'] ?? null,
                'broj_putnika' => count($clientsPayload),
                'status' => $validated['status'],
                'broj_fiskalnog_racuna' => $validated['broj_fiskalnog_racuna'] ?? null,
                'placanje' => $validated['placanje'],
                'broj_rata' => $validated['placanje'] === 'na_rate' ? (int) $validated['broj_rata'] : null,
                'rate' => $this->normalizeRateDatesForStorage($validated),
                'napomena' => $validated['napomena'] ?? null,
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]);

            $this->syncReservationClients($reservation, $clientsPayload, $request->user()?->id);

            return $reservation;
        });

        return response()->json($reservation->load([
            'arrangement',
            'reservationClients.client',
            'reservationClients.package',
        ]), 201);
    }

    public function show(Reservation $reservation): JsonResponse
    {
        $reservation->load([
            'arrangement',
            'reservationClients.client',
            'reservationClients.package',
            'contractTemplate:id,name,template_key,version',
        ]);

        return response()->json($reservation);
    }

    public function update(Request $request, Reservation $reservation): JsonResponse
    {
        $validated = $this->validateReservation($request);

        DB::transaction(function () use ($request, $validated, $reservation): void {
            $clientsPayload = $validated['klijenti'];

            $reservation->update([
                'aranzman_id' => $validated['aranzman_id'],
                'contract_template_id' => $validated['contract_template_id'] ?? null,
                'broj_putnika' => count($clientsPayload),
                'status' => $validated['status'],
                'broj_fiskalnog_racuna' => $validated['broj_fiskalnog_racuna'] ?? null,
                'placanje' => $validated['placanje'],
                'broj_rata' => $validated['placanje'] === 'na_rate' ? (int) $validated['broj_rata'] : null,
                'rate' => $this->normalizeRateDatesForStorage($validated),
                'napomena' => $validated['napomena'] ?? null,
                'updated_by' => $request->user()?->id,
            ]);

            $this->syncReservationClients($reservation, $clientsPayload, $request->user()?->id);
        });

        return response()->json($reservation->fresh()->load([
            'arrangement',
            'reservationClients.client',
            'reservationClients.package',
        ]));
    }

    public function destroy(Reservation $reservation): JsonResponse
    {
        $reservation->delete();

        return response()->json(status: 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateReservation(Request $request): array
    {
        return $request->validate([
            'aranzman_id' => ['required', 'exists:arrangements,id'],
            'contract_template_id' => ['nullable', 'exists:contract_templates,id'],
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

            'klijenti' => ['required', 'array', 'min:1'],
            'klijenti.*.id' => ['nullable', 'exists:clients,id'],
            'klijenti.*.ime' => ['required', 'string', 'max:255'],
            'klijenti.*.prezime' => ['required', 'string', 'max:255'],
            'klijenti.*.broj_dokumenta' => ['nullable', 'string', 'max:255'],
            'klijenti.*.datum_rodjenja' => ['nullable', 'date'],
            'klijenti.*.adresa' => ['required', 'string', 'max:255'],
            'klijenti.*.broj_telefona' => ['required', 'string', 'max:50'],
            'klijenti.*.email' => ['nullable', 'email', 'max:255'],
            'klijenti.*.dodatno_na_cijenu' => ['nullable', 'numeric', 'min:0'],
            'klijenti.*.popust' => ['nullable', 'numeric', 'min:0'],
            'klijenti.*.boravisna_taksa' => ['nullable', 'numeric', 'min:0'],
            'klijenti.*.osiguranje' => ['nullable', 'numeric', 'min:0'],
            'klijenti.*.doplata_jednokrevetna_soba' => ['nullable', 'numeric', 'min:0'],
            'klijenti.*.doplata_dodatno_sjediste' => ['nullable', 'numeric', 'min:0'],
            'klijenti.*.doplata_sjediste_po_zelji' => ['nullable', 'numeric', 'min:0'],
            'klijenti.*.paket_id' => [
                'required',
                Rule::exists('arrangement_packages', 'id')->where(function ($query) use ($request) {
                    $query
                        ->where('aranzman_id', (string) $request->input('aranzman_id'))
                        ->whereNull('deleted_at');
                }),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function normalizeRateDatesForStorage(array $validated): ?array
    {
        if (($validated['placanje'] ?? '') !== 'na_rate') {
            return null;
        }

        $rows = $validated['rate'] ?? null;
        if (! is_array($rows)) {
            return null;
        }

        return array_map(function ($row): array {
            return [
                'datum_predracuna' => $this->normalizeDateValue($row['datum_predracuna'] ?? null),
                'iznos_predracuna' => $this->normalizeMoneyValue($row['iznos_predracuna'] ?? null),
                'datum_uplate' => $this->normalizeDateValue($row['datum_uplate'] ?? null),
                'iznos_uplate' => $this->normalizeMoneyValue($row['iznos_uplate'] ?? null),
                'datum_avansne_fakture' => $this->normalizeDateValue($row['datum_avansne_fakture'] ?? null),
                'iznos_avansne_fakture' => $this->normalizeMoneyValue($row['iznos_avansne_fakture'] ?? null),
            ];
        }, $rows);
    }

    private function normalizeDateValue(mixed $value): ?string
    {
        $raw = trim((string) ($value ?? ''));

        return $raw === '' ? null : $raw;
    }

    private function normalizeMoneyValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $numeric = (float) $value;

        return number_format($numeric, 2, '.', '');
    }

    /**
     * @param  array<int, array<string, mixed>>  $clientsPayload
     */
    private function syncReservationClients(Reservation $reservation, array $clientsPayload, ?string $userId): void
    {
        $reservation->reservationClients()->delete();

        $nameParts = [];
        $firstClient = null;

        foreach ($clientsPayload as $index => $clientData) {
            $client = $this->upsertClient($clientData, $userId);

            $reservation->reservationClients()->create([
                'klijent_id' => $client->id,
                'paket_id' => (string) $clientData['paket_id'],
                'dodatno_na_cijenu' => isset($clientData['dodatno_na_cijenu']) && $clientData['dodatno_na_cijenu'] !== ''
                    ? (float) $clientData['dodatno_na_cijenu']
                    : 0,
                'popust' => isset($clientData['popust']) && $clientData['popust'] !== ''
                    ? (float) $clientData['popust']
                    : 0,
                'boravisna_taksa' => isset($clientData['boravisna_taksa']) && $clientData['boravisna_taksa'] !== ''
                    ? (float) $clientData['boravisna_taksa']
                    : 0,
                'osiguranje' => isset($clientData['osiguranje']) && $clientData['osiguranje'] !== ''
                    ? (float) $clientData['osiguranje']
                    : 0,
                'doplata_jednokrevetna_soba' => isset($clientData['doplata_jednokrevetna_soba']) && $clientData['doplata_jednokrevetna_soba'] !== ''
                    ? (float) $clientData['doplata_jednokrevetna_soba']
                    : 0,
                'doplata_dodatno_sjediste' => isset($clientData['doplata_dodatno_sjediste']) && $clientData['doplata_dodatno_sjediste'] !== ''
                    ? (float) $clientData['doplata_dodatno_sjediste']
                    : 0,
                'doplata_sjediste_po_zelji' => isset($clientData['doplata_sjediste_po_zelji']) && $clientData['doplata_sjediste_po_zelji'] !== ''
                    ? (float) $clientData['doplata_sjediste_po_zelji']
                    : 0,
            ]);

            if ($index === 0) {
                $firstClient = $client;
            }

            $nameParts[] = trim(($client->ime ?? '').' '.($client->prezime ?? ''));
        }

        $reservation->update([
            'klijent_id' => $firstClient?->id,
            'ime_prezime' => trim(implode(', ', array_filter($nameParts))),
            'email' => $firstClient?->email,
            'telefon' => $firstClient?->broj_telefona,
            'broj_putnika' => count($clientsPayload),
            'updated_by' => $userId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $clientData
     */
    private function upsertClient(array $clientData, ?string $userId): Client
    {
        $client = null;
        if (! empty($clientData['id'])) {
            $client = Client::query()->find($clientData['id']);
        }

        if (! $client) {
            $client = new Client();
            $client->created_by = $userId;
        }

        $client->ime = (string) $clientData['ime'];
        $client->prezime = (string) $clientData['prezime'];
        $client->broj_dokumenta = $clientData['broj_dokumenta'] ?: null;
        $client->datum_rodjenja = $clientData['datum_rodjenja'] ?: null;
        $client->adresa = (string) $clientData['adresa'];
        $client->broj_telefona = (string) $clientData['broj_telefona'];
        $client->email = $clientData['email'] ?: null;
        $client->updated_by = $userId;
        $client->save();

        return $client;
    }
}
