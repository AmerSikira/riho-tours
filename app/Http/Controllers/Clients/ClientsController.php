<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Http\Requests\Clients\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ClientsController extends Controller
{
    private const RESERVATION_STATUS_LABELS = [
        'potvrdjena' => 'Potvrđena',
        'otkazana' => 'Otkazana',
        'na_cekanju' => 'Na čekanju',
    ];

    /**
     * Display clients with optional search by name, document number, or city.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('pretraga'));

        $klijenti = Client::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery
                        ->where('ime', 'like', "%{$search}%")
                        ->orWhere('prezime', 'like', "%{$search}%")
                        ->orWhere('broj_dokumenta', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->orderBy('prezime')
            ->orderBy('ime')
            ->paginate(15)
            ->withQueryString();

        $klijenti->setCollection(
            $klijenti->getCollection()->map(fn (Client $klijent) => [
                'id' => $klijent->id,
                'ime' => $klijent->ime,
                'prezime' => $klijent->prezime,
                'broj_dokumenta' => $klijent->broj_dokumenta ?? '',
                'datum_rodjenja' => $klijent->datum_rodjenja?->toDateString(),
                'adresa' => $klijent->adresa,
                'city' => $klijent->city,
                'broj_telefona' => $klijent->broj_telefona,
                'email' => $klijent->email,
                'fotografija_url' => $klijent->fotografija_putanja
                    ? Storage::disk('public')->url($klijent->fotografija_putanja)
                    : null,
            ])
        );

        return Inertia::render('clients/index', [
            'klijenti' => $klijenti,
            'filters' => [
                'pretraga' => $search,
            ],
            'status' => session('status'),
        ]);
    }

    /**
     * Search clients by partial name, surname, document number, or city for reservation autocomplete.
     */
    public function search(Request $request): JsonResponse
    {
        $search = trim((string) $request->string('pretraga'));

        if ($search === '') {
            // Backward compatibility with older callers that only send broj_dokumenta.
            $search = trim((string) $request->string('broj_dokumenta'));
        }

        if ($search === '') {
            return response()->json([]);
        }

        $klijenti = Client::query()
            ->where(function ($query) use ($search) {
                $query
                    ->where('ime', 'like', "%{$search}%")
                    ->orWhere('prezime', 'like', "%{$search}%")
                    ->orWhere('broj_dokumenta', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            })
            ->orderBy('prezime')
            ->orderBy('ime')
            ->limit(8)
            ->get();

        return response()->json(
            $klijenti->map(fn (Client $klijent) => [
                'id' => $klijent->id,
                'ime' => $klijent->ime,
                'prezime' => $klijent->prezime,
                'broj_dokumenta' => $klijent->broj_dokumenta ?? '',
                'datum_rodjenja' => $klijent->datum_rodjenja?->toDateString(),
                'adresa' => $klijent->adresa,
                'city' => $klijent->city,
                'broj_telefona' => $klijent->broj_telefona,
                'email' => $klijent->email,
                'fotografija_url' => $klijent->fotografija_putanja
                    ? Storage::disk('public')->url($klijent->fotografija_putanja)
                    : null,
            ])->values()
        );
    }

    /**
     * Display social profile page for selected client.
     */
    public function show(Client $klijent): Response
    {
        $reservationItems = $klijent->reservationItems()
            ->with([
                'reservation:id,aranzman_id,status,broj_putnika,created_at',
                'reservation.arrangement:id,sifra,naziv_putovanja,destinacija,datum_polaska,datum_povratka',
                'package:id,naziv,cijena',
            ])
            ->whereHas('reservation')
            ->latest()
            ->get();

        $totalRezervacije = $reservationItems
            ->pluck('rezervacija_id')
            ->filter()
            ->unique()
            ->count();

        $statusCount = $reservationItems
            ->map(fn ($stavka) => $stavka->reservation?->status)
            ->filter()
            ->countBy();

        $totalSpent = $reservationItems->sum(function ($stavka): float {
            $paketCijena = (float) ($stavka->package?->cijena ?? 0);
            $dodatno = (float) ($stavka->dodatno_na_cijenu ?? 0);
            $popust = (float) ($stavka->popust ?? 0);

            return $paketCijena + $dodatno - $popust;
        });

        $aktivnosti = $reservationItems
            ->sortByDesc(fn ($stavka) => $stavka->reservation?->created_at?->timestamp ?? 0)
            ->take(8)
            ->map(function ($stavka): array {
                $status = $stavka->reservation?->status ?? 'na_cekanju';

                return [
                    'id' => $stavka->id,
                    'status' => $status,
                    'status_label' => self::RESERVATION_STATUS_LABELS[$status] ?? ucfirst($status),
                    'rezervacija_id' => $stavka->reservation?->id,
                    'created_at' => $stavka->reservation?->created_at?->toISOString(),
                    'broj_putnika' => $stavka->reservation?->broj_putnika,
                    'paket' => [
                        'naziv' => $stavka->package?->naziv,
                        'cijena' => $stavka->package?->cijena,
                    ],
                    'dodatno_na_cijenu' => $stavka->dodatno_na_cijenu,
                    'popust' => $stavka->popust,
                    'aranzman' => [
                        'sifra' => $stavka->reservation?->arrangement?->sifra,
                        'naziv_putovanja' => $stavka->reservation?->arrangement?->naziv_putovanja,
                        'destinacija' => $stavka->reservation?->arrangement?->destinacija,
                        'datum_polaska' => $stavka->reservation?->arrangement?->datum_polaska?->toDateString(),
                        'datum_povratka' => $stavka->reservation?->arrangement?->datum_povratka?->toDateString(),
                    ],
                ];
            })
            ->values();

        return Inertia::render('clients/show', [
            'klijent' => [
                'id' => $klijent->id,
                'ime' => $klijent->ime,
                'prezime' => $klijent->prezime,
                'broj_dokumenta' => $klijent->broj_dokumenta ?? '',
                'datum_rodjenja' => $klijent->datum_rodjenja?->toDateString(),
                'adresa' => $klijent->adresa,
                'city' => $klijent->city,
                'broj_telefona' => $klijent->broj_telefona,
                'email' => $klijent->email,
                'fotografija_url' => $klijent->fotografija_putanja
                    ? Storage::disk('public')->url($klijent->fotografija_putanja)
                    : null,
                'created_at' => $klijent->created_at?->toISOString(),
            ],
            'statistika' => [
                'ukupno_rezervacija' => $totalRezervacije,
                'potvrdjene' => (int) ($statusCount['potvrdjena'] ?? 0),
                'na_cekanju' => (int) ($statusCount['na_cekanju'] ?? 0),
                'otkazane' => (int) ($statusCount['otkazana'] ?? 0),
                'ukupna_potrosnja' => round($totalSpent, 2),
            ],
            'aktivnosti' => $aktivnosti,
        ]);
    }

    /**
     * Show form for creating a new client.
     */
    public function create(): Response
    {
        return Inertia::render('clients/create');
    }

    /**
     * Show form for editing selected client.
     */
    public function edit(Client $klijent): Response
    {
        return Inertia::render('clients/edit', [
            'klijent' => [
                'id' => $klijent->id,
                'ime' => $klijent->ime,
                'prezime' => $klijent->prezime,
                'broj_dokumenta' => $klijent->broj_dokumenta ?? '',
                'datum_rodjenja' => $klijent->datum_rodjenja?->toDateString() ?? '',
                'adresa' => $klijent->adresa,
                'city' => $klijent->city ?? '',
                'broj_telefona' => $klijent->broj_telefona,
                'email' => $klijent->email,
                'fotografija_url' => $klijent->fotografija_putanja
                    ? Storage::disk('public')->url($klijent->fotografija_putanja)
                    : null,
            ],
        ]);
    }

    /**
     * Store a newly created client.
     */
    public function store(StoreClientRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $photoPath = $request->file('fotografija')?->store('klijenti', 'public');

        Client::query()->create([
            'ime' => $validated['ime'],
            'prezime' => $validated['prezime'],
            'broj_dokumenta' => $validated['broj_dokumenta'] ?? null,
            'datum_rodjenja' => $validated['datum_rodjenja'] ?? null,
            'adresa' => $validated['adresa'],
            'city' => $validated['city'] ?? null,
            'broj_telefona' => $validated['broj_telefona'],
            'email' => $validated['email'] ?? null,
            'fotografija_putanja' => $photoPath,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return to_route('klijenti.index')->with('status', 'Klijent je uspješno dodan.');
    }

    /**
     * Update selected client.
     */
    public function update(UpdateClientRequest $request, Client $klijent): RedirectResponse
    {
        $validated = $request->validated();

        $photoPath = $klijent->fotografija_putanja;
        if ($request->hasFile('fotografija')) {
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }

            $photoPath = $request->file('fotografija')?->store('klijenti', 'public');
        }

        $klijent->update([
            'ime' => $validated['ime'],
            'prezime' => $validated['prezime'],
            'broj_dokumenta' => $validated['broj_dokumenta'] ?? null,
            'datum_rodjenja' => $validated['datum_rodjenja'] ?? null,
            'adresa' => $validated['adresa'],
            'city' => $validated['city'] ?? null,
            'broj_telefona' => $validated['broj_telefona'],
            'email' => $validated['email'] ?? null,
            'fotografija_putanja' => $photoPath,
            'updated_by' => $request->user()?->id,
        ]);

        return to_route('klijenti.index')->with('status', 'Klijent je uspješno ažuriran.');
    }

    /**
     * Delete selected client.
     */
    public function destroy(Client $klijent): RedirectResponse
    {
        if ($klijent->fotografija_putanja) {
            Storage::disk('public')->delete($klijent->fotografija_putanja);
        }

        $klijent->delete();

        return to_route('klijenti.index')->with('status', 'Klijent je obrisan.');
    }
}
