<?php

namespace App\Http\Controllers\Reservations;

use App\Exports\ReservationsClientsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reservations\StoreReservationRequest;
use App\Models\Arrangement;
use App\Models\Client;
use App\Models\ContractTemplate;
use App\Models\Reservation;
use App\Models\ReservationClient;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReservationsController extends Controller
{
    /**
     * Display reservations with search filters.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('pretraga'));
        $aranzmanId = trim((string) $request->string('aranzman_id'));
        $datumOd = trim((string) $request->string('datum_od'));
        $datumDo = trim((string) $request->string('datum_do'));
        $sortBy = trim((string) $request->string('sort_by'));
        $sortDirection = strtolower(trim((string) $request->string('sort_direction'))) === 'desc'
            ? 'desc'
            : 'asc';

        $rezervacijeQuery = Reservation::query()
            ->with([
                'arrangement:id,naziv_putovanja,sifra,destinacija,datum_polaska,datum_povratka',
                'reservationClients.client:id,ime,prezime,email,broj_telefona',
                'reservationClients.package:id,naziv,cijena',
                'client:id,ime,prezime',
            ])
            ->whereHas('arrangement', function ($query) use ($search, $aranzmanId, $datumOd, $datumDo) {
                $query
                    ->when($aranzmanId !== '', function ($nestedQuery) use ($aranzmanId) {
                        $nestedQuery->whereKey($aranzmanId);
                    })
                    ->when($search !== '', function ($nestedQuery) use ($search) {
                        $nestedQuery->where(function ($searchQuery) use ($search) {
                            $searchQuery
                                ->where('naziv_putovanja', 'like', "%{$search}%")
                                ->orWhere('sifra', 'like', "%{$search}%")
                                ->orWhere('destinacija', 'like', "%{$search}%");
                        });
                    })
                    ->when($datumOd !== '', function ($nestedQuery) use ($datumOd) {
                        $nestedQuery->whereDate('datum_polaska', '>=', $datumOd);
                    })
                    ->when($datumDo !== '', function ($nestedQuery) use ($datumDo) {
                        $nestedQuery->whereDate('datum_povratka', '<=', $datumDo);
                    });
            });

        if ($sortBy === 'putnik') {
            $rezervacijeQuery
                ->orderBy(
                    ReservationClient::query()
                        ->selectRaw("MIN(CONCAT(COALESCE(clients.prezime, ''), ' ', COALESCE(clients.ime, '')))")
                        ->join('clients', 'clients.id', '=', 'reservation_clients.klijent_id')
                        ->whereColumn('reservation_clients.rezervacija_id', 'reservations.id'),
                    $sortDirection
                )
                ->orderBy('order_num', 'desc');
        } elseif ($sortBy === 'aranzman') {
            $rezervacijeQuery
                ->orderBy(
                    Arrangement::query()
                        ->select('sifra')
                        ->whereColumn('arrangements.id', 'reservations.aranzman_id'),
                    $sortDirection
                )
                ->orderBy(
                    Arrangement::query()
                        ->select('naziv_putovanja')
                        ->whereColumn('arrangements.id', 'reservations.aranzman_id'),
                    $sortDirection
                )
                ->orderBy('order_num', 'desc');
        } elseif ($sortBy === 'datumi') {
            $rezervacijeQuery
                ->orderBy(
                    Arrangement::query()
                        ->select('datum_polaska')
                        ->whereColumn('arrangements.id', 'reservations.aranzman_id'),
                    $sortDirection
                )
                ->orderBy(
                    Arrangement::query()
                        ->select('datum_povratka')
                        ->whereColumn('arrangements.id', 'reservations.aranzman_id'),
                    $sortDirection
                )
                ->orderBy('order_num', 'desc');
        } elseif ($sortBy === 'broj_putnika') {
            $rezervacijeQuery
                ->orderBy('broj_putnika', $sortDirection)
                ->orderBy('order_num', 'desc');
        } else {
            $sortBy = '';
            $sortDirection = 'asc';
            $rezervacijeQuery->latest('order_num');
        }

        $rezervacije = $rezervacijeQuery
            ->paginate(15)
            ->withQueryString();

        $rezervacije->setCollection(
            $rezervacije->getCollection()->map(function (Reservation $rezervacija): array {
                $stavke = $rezervacija->reservationClients;
                $firstStavka = $stavke->first();
                $paymentStatus = $this->buildPaymentStatusMeta($rezervacija);

                return [
                    'id' => $rezervacija->id,
                    'order_num' => $rezervacija->order_num,
                    'ime_prezime' => $stavke->count() > 0
                        ? $stavke
                            ->map(fn (ReservationClient $stavka) => trim("{$stavka->client?->ime} {$stavka->client?->prezime}"))
                            ->filter()
                            ->implode(', ')
                        : ($rezervacija->client
                            ? "{$rezervacija->client->ime} {$rezervacija->client->prezime}"
                            : $rezervacija->ime_prezime),
                    'email' => $firstStavka?->client?->email ?? $rezervacija->email,
                    'telefon' => $firstStavka?->client?->broj_telefona ?? $rezervacija->telefon,
                    'broj_putnika' => $rezervacija->broj_putnika,
                    'status' => $rezervacija->status,
                    'payment_status' => $paymentStatus,
                    'klijenti' => $stavke->map(fn (ReservationClient $stavka) => [
                        'id' => $stavka->klijent_id,
                        'ime_prezime' => trim("{$stavka->client?->ime} {$stavka->client?->prezime}"),
                        'dodatno_na_cijenu' => $stavka->dodatno_na_cijenu,
                        'popust' => $stavka->popust,
                        'boravisna_taksa' => $stavka->boravisna_taksa,
                        'osiguranje' => $stavka->osiguranje,
                        'doplata_jednokrevetna_soba' => $stavka->doplata_jednokrevetna_soba,
                        'doplata_dodatno_sjediste' => $stavka->doplata_dodatno_sjediste,
                        'doplata_sjediste_po_zelji' => $stavka->doplata_sjediste_po_zelji,
                        'paket' => [
                            'id' => $stavka->paket_id,
                            'naziv' => $stavka->package?->naziv,
                            'cijena' => $stavka->package?->cijena,
                        ],
                    ])->values(),
                    'aranzman' => [
                        'id' => $rezervacija->arrangement->id,
                        'sifra' => $rezervacija->arrangement->sifra,
                        'naziv_putovanja' => $rezervacija->arrangement->naziv_putovanja,
                        'destinacija' => $rezervacija->arrangement->destinacija,
                        'datum_polaska' => $rezervacija->arrangement->datum_polaska?->toDateString(),
                        'datum_povratka' => $rezervacija->arrangement->datum_povratka?->toDateString(),
                    ],
                ];
            })
        );

        $selectedAranzman = null;
        if ($aranzmanId !== '') {
            $selectedAranzmanModel = Arrangement::query()
                ->find($aranzmanId, ['id', 'sifra', 'naziv_putovanja', 'destinacija', 'datum_polaska', 'datum_povratka']);

            if ($selectedAranzmanModel) {
                $selectedAranzman = [
                    'id' => $selectedAranzmanModel->id,
                    'sifra' => $selectedAranzmanModel->sifra,
                    'naziv_putovanja' => $selectedAranzmanModel->naziv_putovanja,
                    'destinacija' => $selectedAranzmanModel->destinacija,
                    'datum_polaska' => $selectedAranzmanModel->datum_polaska?->toDateString(),
                    'datum_povratka' => $selectedAranzmanModel->datum_povratka?->toDateString(),
                ];
            }
        }

        return Inertia::render('reservations/index', [
            'rezervacije' => $rezervacije,
            'filters' => [
                'pretraga' => $search,
                'aranzman_id' => $aranzmanId,
                'datum_od' => $datumOd,
                'datum_do' => $datumDo,
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ],
            'selected_aranzman' => $selectedAranzman,
            'status' => session('status'),
        ]);
    }

    /**
     * Search arrangements for reservations index autocomplete.
     */
    public function searchArrangements(Request $request): JsonResponse
    {
        $query = trim((string) $request->string('q'));

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $aranzmani = Arrangement::query()
            ->where(function ($searchQuery) use ($query) {
                $searchQuery
                    ->where('sifra', 'like', "%{$query}%")
                    ->orWhere('naziv_putovanja', 'like', "%{$query}%")
                    ->orWhere('destinacija', 'like', "%{$query}%");
            })
            ->orderBy('datum_polaska')
            ->limit(8)
            ->get(['id', 'sifra', 'naziv_putovanja', 'destinacija', 'datum_polaska', 'datum_povratka']);

        return response()->json(
            $aranzmani->map(fn (Arrangement $aranzman) => [
                'id' => $aranzman->id,
                'sifra' => $aranzman->sifra,
                'naziv_putovanja' => $aranzman->naziv_putovanja,
                'destinacija' => $aranzman->destinacija,
                'datum_polaska' => $aranzman->datum_polaska?->toDateString(),
                'datum_povratka' => $aranzman->datum_povratka?->toDateString(),
            ])->values()
        );
    }

    /**
     * Export selected reservations into XLSX, one traveler per row.
     */
    public function exportSelectedClients(Request $request): BinaryFileResponse
    {
        $reservationIds = collect($request->input('reservation_ids', []))
            ->map(fn ($id) => trim((string) $id))
            ->filter()
            ->values();

        if ($reservationIds->isEmpty()) {
            abort(422, 'No reservations selected for export.');
        }

        $reservations = Reservation::query()
            ->with([
                'arrangement:id,sifra,naziv_putovanja,destinacija,datum_polaska,datum_povratka',
                'reservationClients.client:id,ime,prezime,broj_dokumenta,datum_rodjenja,adresa,city,broj_telefona,email,fotografija_putanja',
                'reservationClients.package:id,naziv,cijena',
                'client:id,ime,prezime,broj_dokumenta,datum_rodjenja,adresa,city,broj_telefona,email,fotografija_putanja',
            ])
            ->whereIn('id', $reservationIds->all())
            ->orderBy('order_num')
            ->get();

        $rows = $reservations
            ->flatMap(function (Reservation $reservation): Collection {
                if ($reservation->reservationClients->isEmpty()) {
                    return collect([
                        $this->buildExportRowFromReservation(
                            reservation: $reservation,
                            client: $reservation->client,
                        ),
                    ]);
                }

                return $reservation->reservationClients->map(function (ReservationClient $item) use ($reservation): array {
                    return $this->buildExportRowFromReservation(
                        reservation: $reservation,
                        client: $item->client,
                    );
                });
            })
            ->values()
            ->map(function (array $row, int $index): array {
                return [
                    'redni_broj' => $index + 1,
                    'ime_i_prezime' => (string) ($row['ime_i_prezime'] ?? ''),
                    'grad' => (string) ($row['grad'] ?? ''),
                    'telefon' => (string) ($row['telefon'] ?? ''),
                    'datum_rodjenja' => (string) ($row['datum_rodjenja'] ?? ''),
                    'broj_dokumenta' => (string) ($row['broj_dokumenta'] ?? ''),
                    'broj_rezervacije' => (string) ($row['broj_rezervacije'] ?? ''),
                ];
            })
            ->values();

        $filename = sprintf('selected-reservations-clients-%s.xlsx', now()->format('Ymd_His'));

        return Excel::download(new ReservationsClientsExport($rows), $filename);
    }

    private function buildExportRowFromReservation(
        Reservation $reservation,
        ?Client $client,
    ): array {
        return [
            'ime_i_prezime' => trim((string) ($client?->ime ?? '').' '.(string) ($client?->prezime ?? '')),
            'grad' => (string) ($client?->city ?? $client?->adresa ?? ''),
            'telefon' => (string) ($client?->broj_telefona ?? ''),
            'datum_rodjenja' => $client?->datum_rodjenja?->format('d.m.Y') ?? '',
            'broj_dokumenta' => (string) ($client?->broj_dokumenta ?? ''),
            'broj_rezervacije' => (string) ($reservation->order_num ?? ''),
        ];
    }

    /**
     * Show form for creating a reservation.
     */
    public function create(): Response
    {
        return Inertia::render('reservations/create', [
            'aranzmani' => $this->arrangementOptions(),
            'contract_templates' => $this->contractTemplateOptions(),
            'settings' => $this->companySettings(request()->user()),
            'next_order_num' => ((int) Reservation::query()->max('order_num')) + 1,
        ]);
    }

    /**
     * Store a new reservation.
     */
    public function store(StoreReservationRequest $request): RedirectResponse
    {
        $validatedData = $request->validated();
        $klijentiData = $validatedData['klijenti'];

        $rezervacija = Reservation::create([
            'aranzman_id' => $validatedData['aranzman_id'],
            'contract_template_id' => $validatedData['contract_template_id'] ?? null,
            'klijent_id' => null,
            'ime_prezime' => '',
            'email' => null,
            'telefon' => null,
            'broj_putnika' => count($klijentiData),
            'status' => $validatedData['status'],
            'broj_fiskalnog_racuna' => $validatedData['broj_fiskalnog_racuna'] ?? null,
            'placanje' => $validatedData['placanje'],
            'broj_rata' => $validatedData['placanje'] === 'na_rate'
                ? (int) $validatedData['broj_rata']
                : null,
            'rate' => $this->normalizeRateDatesForStorage($validatedData),
            'napomena' => $validatedData['napomena'] ?? null,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $this->syncClientsForReservation($request, $rezervacija, $klijentiData);

        return to_route('rezervacije.index')->with('status', 'Rezervacija je uspješno dodana.');
    }

    /**
     * Show form for editing a reservation.
     */
    public function edit(Request $request, Reservation $rezervacija): Response
    {
        $rezervacija->load([
            'reservationClients.client:id,ime,prezime,broj_dokumenta,datum_rodjenja,adresa,city,broj_telefona,email,fotografija_putanja',
            'reservationClients.package:id,aranzman_id,naziv',
        ]);

        $klijenti = $rezervacija->reservationClients->map(function (ReservationClient $stavka): array {
            return [
                'ime' => $stavka->client?->ime ?? '',
                'prezime' => $stavka->client?->prezime ?? '',
                'broj_dokumenta' => $stavka->client?->broj_dokumenta ?? '',
                'datum_rodjenja' => $stavka->client?->datum_rodjenja?->toDateString() ?? '',
                'adresa' => $stavka->client?->adresa ?? '',
                'city' => $stavka->client?->city ?? '',
                'broj_telefona' => $stavka->client?->broj_telefona ?? '',
                'email' => $stavka->client?->email ?? '',
                'dodatno_na_cijenu' => $stavka->dodatno_na_cijenu,
                'popust' => $stavka->popust,
                'boravisna_taksa' => $stavka->boravisna_taksa,
                'osiguranje' => $stavka->osiguranje,
                'doplata_jednokrevetna_soba' => $stavka->doplata_jednokrevetna_soba,
                'doplata_dodatno_sjediste' => $stavka->doplata_dodatno_sjediste,
                'doplata_sjediste_po_zelji' => $stavka->doplata_sjediste_po_zelji,
                'ime_na_predracunu_racunu' => (bool) $stavka->ime_na_predracunu_racunu,
                'paket_id' => $stavka->paket_id,
                'fotografija_url' => $stavka->client?->fotografija_putanja
                    ? Storage::disk('public')->url($stavka->client->fotografija_putanja)
                    : null,
            ];
        })->values();

        return Inertia::render('reservations/edit', [
            'aranzmani' => $this->arrangementOptions(),
            'contract_templates' => $this->contractTemplateOptions(),
            'settings' => $this->companySettings($request->user()),
            'rezervacija' => [
                'id' => $rezervacija->id,
                'order_num' => $rezervacija->order_num,
                'contract_share_url' => $this->buildPublicContractShareUrl(
                    URL::temporarySignedRoute(
                        'javni.ugovor.pdf',
                        now()->addDays(30),
                        ['rezervacija' => $rezervacija->id],
                        absolute: false
                    )
                ),
                'financial_document_links' => $this->buildFinancialDocumentLinks($rezervacija),
                'aranzman_id' => $rezervacija->aranzman_id,
                'contract_template_id' => $rezervacija->contract_template_id,
                'status' => $rezervacija->status,
                'broj_fiskalnog_racuna' => $rezervacija->broj_fiskalnog_racuna ?? '',
                'placanje' => $rezervacija->placanje ?? 'placeno',
                'broj_rata' => $rezervacija->broj_rata,
                'rate' => $this->rateDatesForForm($rezervacija->broj_rata, $rezervacija->rate),
                'napomena' => $rezervacija->napomena ?? '',
                'klijenti' => $klijenti,
            ],
        ]);
    }

    /**
     * Update selected reservation.
     */
    public function update(StoreReservationRequest $request, Reservation $rezervacija): RedirectResponse
    {
        $validatedData = $request->validated();
        $klijentiData = $validatedData['klijenti'];

        $rezervacija->update([
            'aranzman_id' => $validatedData['aranzman_id'],
            'contract_template_id' => $validatedData['contract_template_id'] ?? null,
            'broj_putnika' => count($klijentiData),
            'status' => $validatedData['status'],
            'broj_fiskalnog_racuna' => $validatedData['broj_fiskalnog_racuna'] ?? null,
            'placanje' => $validatedData['placanje'],
            'broj_rata' => $validatedData['placanje'] === 'na_rate'
                ? (int) $validatedData['broj_rata']
                : null,
            'rate' => $this->normalizeRateDatesForStorage($validatedData),
            'napomena' => $validatedData['napomena'] ?? null,
            'updated_by' => $request->user()?->id,
        ]);

        $this->syncClientsForReservation($request, $rezervacija, $klijentiData);

        return to_route('rezervacije.index')->with('status', 'Rezervacija je uspješno ažurirana.');
    }

    /**
     * Delete selected reservation.
     */
    public function destroy(Reservation $rezervacija): RedirectResponse
    {
        $rezervacija->delete();

        return to_route('rezervacije.index')->with('status', 'Rezervacija je obrisana.');
    }

    /**
     * Build arrangement options with package choices.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function arrangementOptions(): Collection
    {
        return Arrangement::query()
            ->with([
                'packages' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('naziv')
                    ->select(['id', 'aranzman_id', 'naziv', 'cijena']),
            ])
            ->orderBy('datum_polaska')
            ->get(['id', 'sifra', 'naziv_putovanja', 'destinacija', 'datum_polaska', 'datum_povratka'])
            ->map(fn (Arrangement $aranzman) => [
                'id' => $aranzman->id,
                'sifra' => $aranzman->sifra,
                'naziv_putovanja' => $aranzman->naziv_putovanja,
                'destinacija' => $aranzman->destinacija,
                'datum_polaska' => $aranzman->datum_polaska?->toDateString(),
                'datum_povratka' => $aranzman->datum_povratka?->toDateString(),
                'paketi' => $aranzman->packages->map(fn ($paket) => [
                    'id' => $paket->id,
                    'naziv' => $paket->naziv,
                    'cijena' => $paket->cijena,
                ])->values(),
            ]);
    }

    /**
     * Build active contract template options for reservation forms.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function contractTemplateOptions(): Collection
    {
        return ContractTemplate::query()
            ->active()
            ->orderBy('template_key')
            ->orderByDesc('version')
            ->get(['id', 'template_key', 'version', 'name'])
            ->map(fn (ContractTemplate $template) => [
                'id' => $template->id,
                'template_key' => $template->template_key,
                'version' => $template->version,
                'name' => $template->name,
            ]);
    }

    /**
     * Get company settings payload for invoice generation.
     *
     * @return array<string, string|bool|null>
     */
    private function companySettings($user = null): array
    {
        $setting = Setting::query()->first();
        $userPotpisUrl = $this->resolveDocumentImageSource($user?->potpis_path);
        $userPecatUrl = $this->resolveDocumentImageSource($user?->pecat_path);
        $companyPotpisUrl = $this->resolveDocumentImageSource($setting?->potpis_path);
        $companyPecatUrl = $this->resolveDocumentImageSource($setting?->pecat_path);

        return [
            'company_name' => $setting->company_name ?? '',
            'invoice_prefix' => $setting->invoice_prefix ?? '',
            'company_id' => $setting->company_id ?? '',
            'maticni_broj_subjekta_upisa' => $setting->maticni_broj_subjekta_upisa ?? '',
            'pdv' => $setting->pdv ?? '',
            'u_pdv_sistemu' => (bool) ($setting?->u_pdv_sistemu ?? true),
            'trn' => $setting->trn ?? '',
            'broj_kase' => $setting->broj_kase ?? '',
            'banka' => $setting->banka ?? '',
            'iban' => $setting->iban ?? '',
            'swift' => $setting->swift ?? '',
            'email' => $setting->email ?? '',
            'phone' => $setting->phone ?? '',
            'address' => $setting->address ?? '',
            'city' => $setting->city ?? '',
            'zip' => $setting->zip ?? '',
            'logo_url' => $this->resolveDocumentImageSource($setting?->logo_path),
            // User signature/stamp take priority; fallback to company assets.
            'potpis_url' => $userPotpisUrl ?? $companyPotpisUrl,
            'pecat_url' => $userPecatUrl ?? $companyPecatUrl,
        ];
    }

    /**
     * Resolve image source that works in browser print windows and generated PDFs.
     */
    private function resolveDocumentImageSource(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $absolutePath = Storage::disk('public')->path($path);
        $binary = @file_get_contents($absolutePath);
        $mimeType = @mime_content_type($absolutePath) ?: 'image/png';

        if ($binary !== false) {
            return sprintf('data:%s;base64,%s', $mimeType, base64_encode($binary));
        }

        return url(Storage::disk('public')->url($path));
    }

    /**
     * Build an absolute public contract URL from a signed relative path.
     */
    private function buildPublicContractShareUrl(string $signedPath): string
    {
        $baseUrl = (string) config('app.url');
        if ($baseUrl === '') {
            return url($signedPath);
        }

        return rtrim($baseUrl, '/').'/'.ltrim($signedPath, '/');
    }

    /**
     * Build selectable financial document links for reservation UI.
     *
     * @return list<array<string, string>>
     */
    private function buildFinancialDocumentLinks(Reservation $rezervacija): array
    {
        $documents = [[
            'key' => 'predracun',
            'label' => 'Predračun',
            'internal_url' => route('rezervacije.finansijski-dokumenti.pregled', [
                'rezervacija' => $rezervacija->id,
                'tip' => 'predracun',
            ], false),
            'share_url' => $this->buildPublicContractShareUrl(
                URL::temporarySignedRoute(
                    'javni.finansijski-dokumenti.pregled',
                    now()->addDays(30),
                    [
                        'rezervacija' => $rezervacija->id,
                        'tip' => 'predracun',
                    ],
                    absolute: false
                )
            ),
        ], [
            'key' => 'racun',
            'label' => 'Račun',
            'internal_url' => route('rezervacije.finansijski-dokumenti.pregled', [
                'rezervacija' => $rezervacija->id,
                'tip' => 'racun',
            ], false),
            'share_url' => $this->buildPublicContractShareUrl(
                URL::temporarySignedRoute(
                    'javni.finansijski-dokumenti.pregled',
                    now()->addDays(30),
                    [
                        'rezervacija' => $rezervacija->id,
                        'tip' => 'racun',
                    ],
                    absolute: false
                )
            ),
        ]];

        $rateRows = is_array($rezervacija->rate) ? array_values($rezervacija->rate) : [];
        foreach ($rateRows as $index => $row) {
            $rateNumber = $index + 1;
            $hasProformaData = (($row['iznos_predracuna'] ?? '') !== '') || (($row['datum_predracuna'] ?? '') !== '');
            $hasAdvanceData = (($row['iznos_avansne_fakture'] ?? '') !== '') || (($row['datum_avansne_fakture'] ?? '') !== '');

            if ($hasProformaData) {
                $documents[] = [
                    'key' => sprintf('rata_predracun_%d', $rateNumber),
                    'label' => sprintf('Predračun rate #%d', $rateNumber),
                    'internal_url' => route('rezervacije.finansijski-dokumenti.rata.pregled', [
                        'rezervacija' => $rezervacija->id,
                        'tip' => 'rata_predracun',
                        'rata' => $rateNumber,
                    ], false),
                    'share_url' => $this->buildPublicContractShareUrl(
                        URL::temporarySignedRoute(
                            'javni.finansijski-dokumenti.rata.pregled',
                            now()->addDays(30),
                            [
                                'rezervacija' => $rezervacija->id,
                                'tip' => 'rata_predracun',
                                'rata' => $rateNumber,
                            ],
                            absolute: false
                        )
                    ),
                ];
            }

            if ($hasAdvanceData) {
                $documents[] = [
                    'key' => sprintf('rata_avansna_%d', $rateNumber),
                    'label' => sprintf('Avansna faktura rate #%d', $rateNumber),
                    'internal_url' => route('rezervacije.finansijski-dokumenti.rata.pregled', [
                        'rezervacija' => $rezervacija->id,
                        'tip' => 'rata_avansna',
                        'rata' => $rateNumber,
                    ], false),
                    'share_url' => $this->buildPublicContractShareUrl(
                        URL::temporarySignedRoute(
                            'javni.finansijski-dokumenti.rata.pregled',
                            now()->addDays(30),
                            [
                                'rezervacija' => $rezervacija->id,
                                'tip' => 'rata_avansna',
                                'rata' => $rateNumber,
                            ],
                            absolute: false
                        )
                    ),
                ];
            }
        }

        return array_map(function (array $document): array {
            $document['internal_url'] = '/'.ltrim((string) ($document['internal_url'] ?? ''), '/');

            return $document;
        }, $documents);
    }

    /**
     * Sync reservation clients and selected packages.
     *
     * @param  array<int, array<string, mixed>>  $klijentiData
     */
    private function syncClientsForReservation(
        Request $request,
        Reservation $rezervacija,
        array $klijentiData
    ): void {
        $imePrezime = [];
        $firstClient = null;
        $activeClientIds = [];
        $selectedInvoiceClientIndex = collect($klijentiData)
            ->search(fn (array $clientData): bool => filter_var($clientData['ime_na_predracunu_racunu'] ?? false, FILTER_VALIDATE_BOOLEAN) === true);
        if ($selectedInvoiceClientIndex === false) {
            $selectedInvoiceClientIndex = 0;
        }

        foreach ($klijentiData as $index => $clientData) {
            $klijent = $this->saveClient($request, $clientData, $index);
            $activeClientIds[] = $klijent->id;

            $stavka = ReservationClient::withTrashed()
                ->firstOrNew([
                    'rezervacija_id' => $rezervacija->id,
                    'klijent_id' => $klijent->id,
                ]);

            $stavka->fill([
                'paket_id' => (string) $clientData['paket_id'],
                'ime_na_predracunu_racunu' => $index === $selectedInvoiceClientIndex,
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
            $stavka->save();

            if ($stavka->trashed()) {
                $stavka->restore();
            }

            $imePrezime[] = trim("{$klijent->ime} {$klijent->prezime}");

            if ($firstClient === null) {
                $firstClient = $klijent;
            }
        }

        $rezervacija->reservationClients()
            ->when($activeClientIds !== [], function ($query) use ($activeClientIds) {
                $query->whereNotIn('klijent_id', $activeClientIds);
            })
            ->when($activeClientIds === [], function ($query) {
                $query->whereRaw('1 = 1');
            })
            ->delete();

        if ($firstClient) {
            $rezervacija->update([
                'klijent_id' => $firstClient->id,
                'ime_prezime' => implode(', ', $imePrezime),
                'email' => $firstClient->email,
                'telefon' => $firstClient->broj_telefona,
            ]);
        }
    }

    /**
     * Normalize installment rows for DB storage.
     *
     * @param  array<string, mixed>  $validatedData
     * @return array<int, array<string, string|null>>|null
     */
    private function normalizeRateDatesForStorage(array $validatedData): ?array
    {
        if (($validatedData['placanje'] ?? null) !== 'na_rate') {
            return null;
        }

        $rate = is_array($validatedData['rate'] ?? null) ? $validatedData['rate'] : [];

        return array_map(
            static fn (array $item): array => [
                'datum_predracuna' => isset($item['datum_predracuna']) && $item['datum_predracuna'] !== ''
                    ? (string) $item['datum_predracuna']
                    : null,
                'iznos_predracuna' => isset($item['iznos_predracuna']) && $item['iznos_predracuna'] !== ''
                    ? number_format((float) $item['iznos_predracuna'], 2, '.', '')
                    : null,
                'datum_uplate' => isset($item['datum_uplate']) && $item['datum_uplate'] !== ''
                    ? (string) $item['datum_uplate']
                    : null,
                'iznos_uplate' => isset($item['iznos_uplate']) && $item['iznos_uplate'] !== ''
                    ? number_format((float) $item['iznos_uplate'], 2, '.', '')
                    : null,
                'datum_avansne_fakture' => isset($item['datum_avansne_fakture']) && $item['datum_avansne_fakture'] !== ''
                    ? (string) $item['datum_avansne_fakture']
                    : null,
                'iznos_avansne_fakture' => isset($item['iznos_avansne_fakture']) && $item['iznos_avansne_fakture'] !== ''
                    ? number_format((float) $item['iznos_avansne_fakture'], 2, '.', '')
                    : null,
            ],
            $rate
        );
    }

    /**
     * Build payment status metadata for reservation list badge.
     *
     * @return array{label: string, tone: string}
     */
    private function buildPaymentStatusMeta(Reservation $rezervacija): array
    {
        $placanje = $rezervacija->placanje ?? 'placeno';

        if ($placanje === 'placeno') {
            return [
                'label' => 'Plaćeno',
                'tone' => 'success',
            ];
        }

        if ($placanje === 'na_odgodeno') {
            return [
                'label' => 'Na odgođeno',
                'tone' => 'warning',
            ];
        }

        $totalInstallments = max((int) ($rezervacija->broj_rata ?? 0), 0);
        $paidInstallments = $this->countPaidInstallments($rezervacija->rate);
        $remainingInstallments = max($totalInstallments - $paidInstallments, 0);

        if ($remainingInstallments <= 0 && $totalInstallments > 0) {
            return [
                'label' => 'Sve rate plaćene',
                'tone' => 'success',
            ];
        }

        if ($remainingInstallments === 1) {
            return [
                'label' => '1 rata preostala',
                'tone' => 'warning',
            ];
        }

        if ($remainingInstallments > 1) {
            return [
                'label' => sprintf('%d rate preostale', $remainingInstallments),
                'tone' => 'warning',
            ];
        }

        return [
            'label' => 'Plaćanje na rate',
            'tone' => 'neutral',
        ];
    }

    /**
     * Count paid installments from stored rate payload.
     *
     * Supports both new object format and legacy date-only values.
     *
     * @param  mixed  $rate
     */
    private function countPaidInstallments($rate): int
    {
        if (! is_array($rate)) {
            return 0;
        }

        $paid = 0;

        foreach ($rate as $item) {
            if (is_array($item)) {
                $amount = $item['iznos_uplate'] ?? null;
                $date = $item['datum_uplate'] ?? null;

                $hasPaidAmount = $amount !== null && $amount !== '' && (float) $amount > 0;
                $hasDate = $date !== null && $date !== '';

                if ($hasPaidAmount || $hasDate) {
                    $paid++;
                }

                continue;
            }

            if (is_string($item) && trim($item) !== '') {
                $paid++;
            }
        }

        return $paid;
    }

    /**
     * Build installment form structure for edit page.
     *
     * @param  array<int, mixed>|null  $storedRateDates
     * @return array<int, array<string, string>>
     */
    private function rateDatesForForm(?int $brojRata, ?array $storedRateDates): array
    {
        if (! $brojRata || $brojRata < 1) {
            return [];
        }

        $safeDates = array_values(array_slice($storedRateDates ?? [], 0, $brojRata));

        while (count($safeDates) < $brojRata) {
            $safeDates[] = null;
        }

        return array_map(
            static function (mixed $row): array {
                // Backward compatibility for old data where rate was stored as date-only strings.
                if (is_string($row) || $row === null) {
                    return [
                        'datum_predracuna' => '',
                        'iznos_predracuna' => '',
                        'datum_uplate' => (string) ($row ?? ''),
                        'iznos_uplate' => '',
                        'datum_avansne_fakture' => '',
                        'iznos_avansne_fakture' => '',
                    ];
                }

                if (! is_array($row)) {
                    return [
                        'datum_predracuna' => '',
                        'iznos_predracuna' => '',
                        'datum_uplate' => '',
                        'iznos_uplate' => '',
                        'datum_avansne_fakture' => '',
                        'iznos_avansne_fakture' => '',
                    ];
                }

                return [
                    'datum_predracuna' => isset($row['datum_predracuna']) ? (string) ($row['datum_predracuna'] ?? '') : '',
                    'iznos_predracuna' => isset($row['iznos_predracuna']) ? (string) ($row['iznos_predracuna'] ?? '') : '',
                    'datum_uplate' => isset($row['datum_uplate']) ? (string) ($row['datum_uplate'] ?? '') : '',
                    'iznos_uplate' => isset($row['iznos_uplate']) ? (string) ($row['iznos_uplate'] ?? '') : '',
                    'datum_avansne_fakture' => isset($row['datum_avansne_fakture']) ? (string) ($row['datum_avansne_fakture'] ?? '') : '',
                    'iznos_avansne_fakture' => isset($row['iznos_avansne_fakture']) ? (string) ($row['iznos_avansne_fakture'] ?? '') : '',
                ];
            },
            $safeDates
        );
    }

    /**
     * Create or update a client from reservation form data.
     *
     * @param  array<string, mixed>  $clientData
     */
    private function saveClient(Request $request, array $clientData, int $index): Client
    {
        $clientPhotoPath = $request->file("klijenti.{$index}.fotografija")?->store('klijenti', 'public');

        $documentNumber = isset($clientData['broj_dokumenta']) && $clientData['broj_dokumenta'] !== ''
            ? (string) $clientData['broj_dokumenta']
            : null;

        $klijent = $documentNumber === null
            ? new Client()
            : Client::query()->firstOrNew([
                'broj_dokumenta' => $documentNumber,
            ]);

        if ($clientPhotoPath) {
            if ($klijent->exists && $klijent->fotografija_putanja) {
                Storage::disk('public')->delete($klijent->fotografija_putanja);
            }

            $klijent->fotografija_putanja = $clientPhotoPath;
        }

        $klijent->fill([
            'ime' => $clientData['ime'],
            'prezime' => $clientData['prezime'],
            'broj_dokumenta' => $documentNumber,
            'datum_rodjenja' => isset($clientData['datum_rodjenja']) && $clientData['datum_rodjenja'] !== ''
                ? $clientData['datum_rodjenja']
                : null,
            'adresa' => $clientData['adresa'],
            'city' => isset($clientData['city']) && $clientData['city'] !== ''
                ? (string) $clientData['city']
                : null,
            'broj_telefona' => $clientData['broj_telefona'],
            'email' => $clientData['email'] ?? null,
            'updated_by' => $request->user()?->id,
        ]);

        if (! $klijent->exists) {
            $klijent->created_by = $request->user()?->id;
        }

        $klijent->save();

        return $klijent;
    }
}
