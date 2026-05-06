<?php

namespace App\Http\Controllers\Suppliers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Suppliers\StoreSupplierRequest;
use App\Http\Requests\Suppliers\UpdateSupplierRequest;
use App\Models\Reservation;
use App\Models\Supplier;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class SuppliersController extends Controller
{
    /**
     * Display suppliers with optional search.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('pretraga'));

        $suppliers = Supplier::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery
                        ->where('company_name', 'like', "%{$search}%")
                        ->orWhere('company_id', 'like', "%{$search}%")
                        ->orWhere('pdv', 'like', "%{$search}%");
                });
            })
            ->orderBy('company_name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('suppliers/index', [
            'dobavljaci' => $suppliers->through(fn (Supplier $supplier) => [
                'id' => $supplier->id,
                'company_name' => $supplier->company_name,
                'company_id' => $supplier->company_id,
                'pdv' => $supplier->pdv,
                'phone' => $supplier->phone,
                'email' => $supplier->email,
                'address' => $supplier->address,
                'city' => $supplier->city,
            ]),
            'filters' => [
                'pretraga' => $search,
            ],
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Display supplier details and period report.
     */
    public function show(Request $request, Supplier $dobavljac): Response
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $reservations = Reservation::query()
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereHas('arrangement', function ($query) use ($dobavljac) {
                $query->where('supplier_id', $dobavljac->id);
            })
            ->with([
                'arrangement:id,sifra,naziv_putovanja,destinacija,subagentski_aranzman,supplier_id',
                'reservationClients:id,rezervacija_id,paket_id,dodatno_na_cijenu,popust,boravisna_taksa,osiguranje,doplata_jednokrevetna_soba,doplata_dodatno_sjediste,doplata_sjediste_po_zelji',
                'reservationClients.package:id,cijena,smjestaj_trosak,transport_trosak,fakultativne_stvari_trosak,ostalo_trosak',
            ])
            ->orderByDesc('created_at')
            ->get(['id', 'order_num', 'aranzman_id', 'broj_putnika', 'status', 'created_at']);

        $reservationCount = $reservations->count();
        $peopleCount = (int) $reservations->sum(fn (Reservation $reservation) => (int) ($reservation->broj_putnika ?? 0));
        $arrangementCount = (int) $reservations->pluck('aranzman_id')->filter()->unique()->count();

        $income = 0.0;
        $cost = 0.0;

        foreach ($reservations as $reservation) {
            foreach ($reservation->reservationClients as $item) {
                $packagePrice = (float) ($item->package?->cijena ?? 0);
                $extraCharge = (float) ($item->dodatno_na_cijenu ?? 0);
                $boravisnaTaksa = (float) ($item->boravisna_taksa ?? 0);
                $osiguranje = (float) ($item->osiguranje ?? 0);
                $doplataJednokrevetnaSoba = (float) ($item->doplata_jednokrevetna_soba ?? 0);
                $doplataDodatnoSjediste = (float) ($item->doplata_dodatno_sjediste ?? 0);
                $doplataSjedistePoZelji = (float) ($item->doplata_sjediste_po_zelji ?? 0);
                $discount = (float) ($item->popust ?? 0);
                $income += max($packagePrice + $extraCharge + $boravisnaTaksa + $osiguranje + $doplataJednokrevetnaSoba + $doplataDodatnoSjediste + $doplataSjedistePoZelji - $discount, 0);

                if ((bool) ($reservation->arrangement?->subagentski_aranzman ?? false)) {
                    $commissionPercent = (float) ($item->package?->smjestaj_trosak ?? 0);
                    $commissionAmount = max(($packagePrice * $commissionPercent) / 100, 0);
                    $cost += max($packagePrice - $commissionAmount, 0);
                } else {
                    $cost += max(
                        (float) ($item->package?->smjestaj_trosak ?? 0) +
                        (float) ($item->package?->transport_trosak ?? 0) +
                        (float) ($item->package?->fakultativne_stvari_trosak ?? 0) +
                        (float) ($item->package?->ostalo_trosak ?? 0),
                        0
                    );
                }
            }
        }

        $totalProfit = $income - $cost;

        $arrangementBreakdown = $reservations
            ->groupBy('aranzman_id')
            ->map(function (Collection $rows): array {
                /** @var Reservation|null $first */
                $first = $rows->first();

                $arrangementIncome = 0.0;
                $arrangementCost = 0.0;

                foreach ($rows as $reservation) {
                    foreach ($reservation->reservationClients as $item) {
                        $packagePrice = (float) ($item->package?->cijena ?? 0);
                        $extraCharge = (float) ($item->dodatno_na_cijenu ?? 0);
                        $boravisnaTaksa = (float) ($item->boravisna_taksa ?? 0);
                        $osiguranje = (float) ($item->osiguranje ?? 0);
                        $doplataJednokrevetnaSoba = (float) ($item->doplata_jednokrevetna_soba ?? 0);
                        $doplataDodatnoSjediste = (float) ($item->doplata_dodatno_sjediste ?? 0);
                        $doplataSjedistePoZelji = (float) ($item->doplata_sjediste_po_zelji ?? 0);
                        $discount = (float) ($item->popust ?? 0);
                        $arrangementIncome += max($packagePrice + $extraCharge + $boravisnaTaksa + $osiguranje + $doplataJednokrevetnaSoba + $doplataDodatnoSjediste + $doplataSjedistePoZelji - $discount, 0);

                        if ((bool) ($reservation->arrangement?->subagentski_aranzman ?? false)) {
                            $commissionPercent = (float) ($item->package?->smjestaj_trosak ?? 0);
                            $commissionAmount = max(($packagePrice * $commissionPercent) / 100, 0);
                            $arrangementCost += max($packagePrice - $commissionAmount, 0);
                        } else {
                            $arrangementCost += max(
                                (float) ($item->package?->smjestaj_trosak ?? 0) +
                                (float) ($item->package?->transport_trosak ?? 0) +
                                (float) ($item->package?->fakultativne_stvari_trosak ?? 0) +
                                (float) ($item->package?->ostalo_trosak ?? 0),
                                0
                            );
                        }
                    }
                }

                return [
                    'arrangement_id' => (string) ($first?->aranzman_id ?? ''),
                    'arrangement_code' => (string) ($first?->arrangement?->sifra ?? ''),
                    'arrangement_name' => (string) ($first?->arrangement?->naziv_putovanja ?? ''),
                    'destination' => (string) ($first?->arrangement?->destinacija ?? ''),
                    'reservations_count' => (int) $rows->count(),
                    'people_count' => (int) $rows->sum(fn (Reservation $reservation) => (int) ($reservation->broj_putnika ?? 0)),
                    'income' => round($arrangementIncome, 2),
                    'profit' => round($arrangementIncome - $arrangementCost, 2),
                ];
            })
            ->sortByDesc('profit')
            ->values();

        return Inertia::render('suppliers/show', [
            'dobavljac' => [
                'id' => $dobavljac->id,
                'company_name' => $dobavljac->company_name,
                'company_id' => $dobavljac->company_id,
                'pdv' => $dobavljac->pdv,
                'email' => $dobavljac->email,
                'phone' => $dobavljac->phone,
                'address' => $dobavljac->address,
                'city' => $dobavljac->city,
                'zip' => $dobavljac->zip,
            ],
            'filters' => [
                'datum_od' => $dateFrom->toDateString(),
                'datum_do' => $dateTo->toDateString(),
            ],
            'report' => [
                'reservation_count' => $reservationCount,
                'people_count' => $peopleCount,
                'arrangement_count' => $arrangementCount,
                'income' => round($income, 2),
                'total_profit' => round($totalProfit, 2),
                'avg_profit_per_person' => $peopleCount > 0 ? round($totalProfit / $peopleCount, 2) : 0.0,
                'avg_profit_per_arrangement' => $arrangementCount > 0 ? round($totalProfit / $arrangementCount, 2) : 0.0,
                'avg_profit_per_reservation' => $reservationCount > 0 ? round($totalProfit / $reservationCount, 2) : 0.0,
            ],
            'arrangement_breakdown' => $arrangementBreakdown,
        ]);
    }

    /**
     * Show form for creating a new supplier.
     */
    public function create(): Response
    {
        return Inertia::render('suppliers/create');
    }

    /**
     * Store a newly created supplier.
     */
    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $validatedData = $request->validated();

        Supplier::query()->create([
            ...$validatedData,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return to_route('dobavljaci.index')->with('status', 'Dobavljač je uspješno dodan.');
    }

    /**
     * Show form for editing selected supplier.
     */
    public function edit(Supplier $dobavljac): Response
    {
        return Inertia::render('suppliers/edit', [
            'dobavljac' => [
                'id' => $dobavljac->id,
                'company_name' => $dobavljac->company_name,
                'company_id' => $dobavljac->company_id,
                'maticni_broj_subjekta_upisa' => $dobavljac->maticni_broj_subjekta_upisa,
                'pdv' => $dobavljac->pdv,
                'trn' => $dobavljac->trn,
                'banka' => $dobavljac->banka,
                'iban' => $dobavljac->iban,
                'swift' => $dobavljac->swift,
                'osiguravajuce_drustvo' => $dobavljac->osiguravajuce_drustvo,
                'email' => $dobavljac->email,
                'phone' => $dobavljac->phone,
                'address' => $dobavljac->address,
                'city' => $dobavljac->city,
                'zip' => $dobavljac->zip,
            ],
        ]);
    }

    /**
     * Update selected supplier.
     */
    public function update(
        UpdateSupplierRequest $request,
        Supplier $dobavljac
    ): RedirectResponse {
        $validatedData = $request->validated();

        $dobavljac->update([
            ...$validatedData,
            'updated_by' => $request->user()?->id,
        ]);

        return to_route('dobavljaci.index')->with('status', 'Dobavljač je uspješno ažuriran.');
    }

    /**
     * Delete selected supplier.
     */
    public function destroy(Supplier $dobavljac): RedirectResponse
    {
        $dobavljac->delete();

        return to_route('dobavljaci.index')->with('status', 'Dobavljač je uspješno obrisan.');
    }

    /**
     * Resolve report period from request or fallback to previous calendar month.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function resolveDateRange(Request $request): array
    {
        $defaultFrom = now()->subMonthNoOverflow()->startOfMonth();
        $defaultTo = now()->subMonthNoOverflow()->endOfMonth();

        try {
            $dateFrom = CarbonImmutable::parse((string) $request->string('datum_od', $defaultFrom->toDateString()))
                ->startOfDay();
        } catch (\Throwable) {
            $dateFrom = CarbonImmutable::instance($defaultFrom)->startOfDay();
        }

        try {
            $dateTo = CarbonImmutable::parse((string) $request->string('datum_do', $defaultTo->toDateString()))
                ->endOfDay();
        } catch (\Throwable) {
            $dateTo = CarbonImmutable::instance($defaultTo)->endOfDay();
        }

        if ($dateFrom->greaterThan($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->startOfDay(), $dateFrom->endOfDay()];
        }

        return [$dateFrom, $dateTo];
    }
}
