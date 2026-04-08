<?php

namespace App\Http\Controllers\WebReservations;

use App\Http\Controllers\Controller;
use App\Models\ArrangementPackage;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\WebReservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WebReservationsController extends Controller
{
    /**
     * Display all web reservations.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('pretraga'));

        $webReservations = WebReservation::query()
            ->with([
                'arrangement:id,sifra,naziv_putovanja,destinacija,datum_polaska,datum_povratka',
                'package:id,naziv,cijena',
                'convertedReservation:id,order_num',
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('ime', 'like', "%{$search}%")
                        ->orWhere('prezime', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('broj_telefona', 'like', "%{$search}%")
                        ->orWhere('source_domain', 'like', "%{$search}%");
                });
            })
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $webReservations->setCollection(
            $webReservations->getCollection()->map(function (WebReservation $webReservation): array {
                return [
                    'id' => $webReservation->id,
                    'ime' => $webReservation->ime,
                    'prezime' => $webReservation->prezime,
                    'email' => $webReservation->email,
                    'broj_telefona' => $webReservation->broj_telefona,
                    'broj_putnika' => $webReservation->broj_putnika,
                    'status' => $webReservation->status,
                    'source_domain' => $webReservation->source_domain,
                    'created_at' => $webReservation->created_at?->toIso8601String(),
                    'arrangement' => $webReservation->arrangement ? [
                        'id' => $webReservation->arrangement->id,
                        'sifra' => $webReservation->arrangement->sifra,
                        'naziv_putovanja' => $webReservation->arrangement->naziv_putovanja,
                        'destinacija' => $webReservation->arrangement->destinacija,
                    ] : null,
                    'package' => $webReservation->package ? [
                        'id' => $webReservation->package->id,
                        'naziv' => $webReservation->package->naziv,
                    ] : null,
                    'converted_reservation' => $webReservation->convertedReservation ? [
                        'id' => $webReservation->convertedReservation->id,
                        'order_num' => $webReservation->convertedReservation->order_num,
                    ] : null,
                ];
            })
        );

        $now = now();
        $trendStart = $now->copy()->subDays(29)->startOfDay();

        $totalLeads = WebReservation::query()->count();
        $convertedLeads = WebReservation::query()
            ->whereNotNull('converted_at')
            ->count();
        $newLeads = WebReservation::query()
            ->where('status', 'novo')
            ->count();
        $contactedLeads = WebReservation::query()
            ->where('status', 'kontaktiran')
            ->count();

        $conversionRate = $totalLeads > 0
            ? round(($convertedLeads / $totalLeads) * 100, 2)
            : 0.0;

        $averageConversionHours = (float) round(
            WebReservation::query()
                ->whereNotNull('converted_at')
                ->get(['created_at', 'converted_at'])
                ->avg(static function (WebReservation $row): float {
                    if (! $row->created_at || ! $row->converted_at) {
                        return 0.0;
                    }

                    return (float) $row->created_at->diffInHours($row->converted_at);
                }) ?? 0.0,
            2
        );

        $sourceDomains = WebReservation::query()
            ->select('source_domain', DB::raw('count(*) as ukupno'))
            ->whereNotNull('source_domain')
            ->where('source_domain', '!=', '')
            ->groupBy('source_domain')
            ->orderByDesc('ukupno')
            ->limit(5)
            ->get()
            ->map(static fn ($row): array => [
                'label' => (string) $row->source_domain,
                'total' => (int) $row->ukupno,
            ])
            ->values();

        $utmSources = WebReservation::query()
            ->select('utm_source', DB::raw('count(*) as ukupno'))
            ->whereNotNull('utm_source')
            ->where('utm_source', '!=', '')
            ->groupBy('utm_source')
            ->orderByDesc('ukupno')
            ->limit(5)
            ->get()
            ->map(static fn ($row): array => [
                'label' => (string) $row->utm_source,
                'total' => (int) $row->ukupno,
            ])
            ->values();

        $utmCampaigns = WebReservation::query()
            ->select('utm_campaign', DB::raw('count(*) as ukupno'))
            ->whereNotNull('utm_campaign')
            ->where('utm_campaign', '!=', '')
            ->groupBy('utm_campaign')
            ->orderByDesc('ukupno')
            ->limit(5)
            ->get()
            ->map(static fn ($row): array => [
                'label' => (string) $row->utm_campaign,
                'total' => (int) $row->ukupno,
            ])
            ->values();

        $rowsByDate = WebReservation::query()
            ->selectRaw('DATE(created_at) as dan, count(*) as ukupno')
            ->where('created_at', '>=', $trendStart)
            ->groupBy('dan')
            ->orderBy('dan')
            ->get()
            ->keyBy('dan');

        $leadTrend = [];
        for ($dayOffset = 0; $dayOffset < 30; $dayOffset++) {
            $day = $trendStart->copy()->addDays($dayOffset);
            $key = $day->toDateString();
            $leadTrend[] = [
                'date' => $key,
                'total' => (int) ($rowsByDate->get($key)->ukupno ?? 0),
            ];
        }

        return Inertia::render('web-reservations/index', [
            'web_reservations' => $webReservations,
            'filters' => [
                'pretraga' => $search,
            ],
            'analytics' => [
                'totals' => [
                    'all' => $totalLeads,
                    'new' => $newLeads,
                    'contacted' => $contactedLeads,
                    'converted' => $convertedLeads,
                    'conversion_rate' => $conversionRate,
                    'average_conversion_hours' => $averageConversionHours,
                ],
                'top_source_domains' => $sourceDomains,
                'top_utm_sources' => $utmSources,
                'top_utm_campaigns' => $utmCampaigns,
                'lead_trend_30d' => $leadTrend,
            ],
            'status' => session('status'),
            'error' => session('error'),
        ]);
    }

    /**
     * Show single web reservation details.
     */
    public function show(WebReservation $webRezervacija): Response
    {
        $webRezervacija->load([
            'arrangement:id,sifra,naziv_putovanja,destinacija,datum_polaska,datum_povratka',
            'package:id,naziv,cijena',
            'convertedReservation:id,order_num',
        ]);

        return Inertia::render('web-reservations/show', [
            'web_reservation' => [
                'id' => $webRezervacija->id,
                'ime' => $webRezervacija->ime,
                'prezime' => $webRezervacija->prezime,
                'email' => $webRezervacija->email,
                'broj_telefona' => $webRezervacija->broj_telefona,
                'adresa' => $webRezervacija->adresa,
                'broj_putnika' => $webRezervacija->broj_putnika,
                'napomena' => $webRezervacija->napomena,
                'status' => $webRezervacija->status,
                'source_domain' => $webRezervacija->source_domain,
                'source_url' => $webRezervacija->source_url,
                'landing_page_url' => $webRezervacija->landing_page_url,
                'referrer_url' => $webRezervacija->referrer_url,
                'utm_source' => $webRezervacija->utm_source,
                'utm_medium' => $webRezervacija->utm_medium,
                'utm_campaign' => $webRezervacija->utm_campaign,
                'utm_term' => $webRezervacija->utm_term,
                'utm_content' => $webRezervacija->utm_content,
                'created_at' => $webRezervacija->created_at?->toIso8601String(),
                'payload' => $webRezervacija->payload,
                'arrangement' => $webRezervacija->arrangement ? [
                    'id' => $webRezervacija->arrangement->id,
                    'sifra' => $webRezervacija->arrangement->sifra,
                    'naziv_putovanja' => $webRezervacija->arrangement->naziv_putovanja,
                    'destinacija' => $webRezervacija->arrangement->destinacija,
                ] : null,
                'package' => $webRezervacija->package ? [
                    'id' => $webRezervacija->package->id,
                    'naziv' => $webRezervacija->package->naziv,
                ] : null,
                'converted_reservation' => $webRezervacija->convertedReservation ? [
                    'id' => $webRezervacija->convertedReservation->id,
                    'order_num' => $webRezervacija->convertedReservation->order_num,
                ] : null,
            ],
            'status' => session('status'),
            'error' => session('error'),
        ]);
    }

    /**
     * Convert web reservation into standard reservation.
     */
    public function convert(Request $request, WebReservation $webRezervacija): RedirectResponse
    {
        if ($webRezervacija->converted_reservation_id) {
            return to_route('rezervacije.edit', ['rezervacija' => $webRezervacija->converted_reservation_id])
                ->with('status', 'Web rezervacija je već prebačena u rezervacije.');
        }

        if (! $webRezervacija->aranzman_id) {
            return back()->with('error', 'Web rezervacija nema odabran aranžman i ne može se prebaciti.');
        }

        $reservation = DB::transaction(function () use ($request, $webRezervacija): Reservation {
            $packageId = $webRezervacija->paket_id;
            if ($packageId) {
                $packageId = ArrangementPackage::query()
                    ->where('id', $packageId)
                    ->where('aranzman_id', $webRezervacija->aranzman_id)
                    ->value('id');
            }
            if (! $packageId) {
                $packageId = ArrangementPackage::query()
                    ->where('aranzman_id', $webRezervacija->aranzman_id)
                    ->where('is_active', true)
                    ->oldest('created_at')
                    ->value('id');
            }

            $client = Client::query()->create([
                'ime' => trim((string) $webRezervacija->ime) !== '' ? (string) $webRezervacija->ime : 'Web',
                'prezime' => trim((string) $webRezervacija->prezime) !== '' ? (string) $webRezervacija->prezime : 'Kontakt',
                'broj_dokumenta' => null,
                'datum_rodjenja' => null,
                'adresa' => $webRezervacija->adresa ?: 'Nije unesena adresa',
                'broj_telefona' => $webRezervacija->broj_telefona ?: '-',
                'email' => $webRezervacija->email,
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]);

            $noteParts = array_filter([
                $webRezervacija->napomena,
                $webRezervacija->source_domain ? 'Izvor domena: '.$webRezervacija->source_domain : null,
                $webRezervacija->source_url ? 'Izvor URL: '.$webRezervacija->source_url : null,
            ]);

            $reservation = Reservation::query()->create([
                'aranzman_id' => $webRezervacija->aranzman_id,
                'contract_template_id' => null,
                'klijent_id' => $client->id,
                'ime_prezime' => trim($client->ime.' '.$client->prezime),
                'email' => $client->email,
                'telefon' => $client->broj_telefona,
                'broj_putnika' => max(1, (int) ($webRezervacija->broj_putnika ?? 1)),
                'status' => 'na_cekanju',
                'broj_fiskalnog_racuna' => null,
                'placanje' => 'placeno',
                'broj_rata' => null,
                'rate' => null,
                'napomena' => trim(implode("\n", $noteParts)) ?: null,
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]);

            if ($packageId) {
                $reservation->reservationClients()->create([
                    'klijent_id' => $client->id,
                    'paket_id' => $packageId,
                    'dodatno_na_cijenu' => 0,
                    'popust' => 0,
                ]);
            }

            $webRezervacija->update([
                'status' => 'konvertovano',
                'converted_reservation_id' => $reservation->id,
                'converted_at' => now(),
                'updated_by' => $request->user()?->id,
            ]);

            return $reservation;
        });

        return to_route('rezervacije.edit', ['rezervacija' => $reservation->id])
            ->with('status', 'Web rezervacija je uspješno prebačena u rezervacije.');
    }
}
