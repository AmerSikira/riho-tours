<?php

namespace App\Http\Controllers;

use App\Models\Arrangement;
use App\Models\ArrangementPackage;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\ReservationClient;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display dashboard statistics and latest records.
     */
    public function __invoke(): Response
    {
        $latestRezervacije = Reservation::query()
            ->with([
                'arrangement:id,naziv_putovanja,sifra,datum_polaska,datum_povratka',
                'reservationClients.client:id,ime,prezime',
            ])
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(function (Reservation $rezervacija): array {
                $klijenti = $rezervacija->reservationClients
                    ->map(fn (ReservationClient $stavka) => trim("{$stavka->client?->ime} {$stavka->client?->prezime}"))
                    ->filter()
                    ->values();

                return [
                    'id' => $rezervacija->id,
                    'putnici' => $klijenti->isNotEmpty()
                        ? $klijenti->implode(', ')
                        : $rezervacija->ime_prezime,
                    'broj_putnika' => $rezervacija->broj_putnika,
                    'status' => $rezervacija->status,
                    'aranzman' => [
                        'id' => $rezervacija->arrangement?->id,
                        'sifra' => $rezervacija->arrangement?->sifra,
                        'naziv_putovanja' => $rezervacija->arrangement?->naziv_putovanja,
                    ],
                    'created_at' => $rezervacija->created_at?->toDateString(),
                ];
            });

        $latestAranzmani = Arrangement::query()
            ->whereDate('datum_polaska', '>=', now()->toDateString())
            ->orderBy('datum_polaska')
            ->limit(8)
            ->get(['id', 'sifra', 'naziv_putovanja', 'destinacija', 'datum_polaska', 'datum_povratka'])
            ->map(fn (Arrangement $aranzman) => [
                'id' => $aranzman->id,
                'sifra' => $aranzman->sifra,
                'naziv_putovanja' => $aranzman->naziv_putovanja,
                'destinacija' => $aranzman->destinacija,
                'datum_polaska' => $aranzman->datum_polaska?->toDateString(),
                'datum_povratka' => $aranzman->datum_povratka?->toDateString(),
            ]);

        return Inertia::render('dashboard', [
            'stats' => [
                'broj_putnika' => Client::query()->count(),
                'broj_aranzmana' => Arrangement::query()->count(),
                'broj_programa' => ArrangementPackage::query()->count(),
                'broj_rezervacija' => Reservation::query()->count(),
            ],
            'latest_rezervacije' => $latestRezervacije,
            'latest_aranzmani' => $latestAranzmani,
        ]);
    }
}
