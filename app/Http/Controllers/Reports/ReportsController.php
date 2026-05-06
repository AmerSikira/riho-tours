<?php

namespace App\Http\Controllers\Reports;

use App\Exports\Reports\ReportsWorkbookExport;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportsController extends Controller
{
    /**
     * Display analytics for the selected date range.
     */
    public function index(Request $request): Response
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);
        $reportData = $this->buildReportData($dateFrom, $dateTo);

        return Inertia::render('reports/index', [
            'filters' => [
                'datum_od' => $dateFrom->toDateString(),
                'datum_do' => $dateTo->toDateString(),
            ],
            ...$reportData,
        ]);
    }

    /**
     * Export report workbook for the selected date range.
     */
    public function export(Request $request): BinaryFileResponse
    {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);
        $reportData = $this->buildReportData($dateFrom, $dateTo);

        $filename = sprintf('izvjestaji-%s-do-%s.xlsx', $dateFrom->format('Ymd'), $dateTo->format('Ymd'));

        return Excel::download(
            new ReportsWorkbookExport(
                dateFrom: $dateFrom,
                dateTo: $dateTo,
                summary: $reportData['summary'],
                dailyTrend: $reportData['daily_trend'],
                arrangementPerformance: $reportData['arrangement_export_rows']->all(),
                reservations: $reportData['reservation_rows']->all(),
            ),
            $filename
        );
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function resolveDateRange(Request $request): array
    {
        $defaultFrom = now()->startOfMonth();
        $defaultTo = now()->endOfMonth();

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

    /**
     * @return array<string, mixed>
     */
    private function buildReportData(CarbonImmutable $dateFrom, CarbonImmutable $dateTo): array
    {
        $reservationQuery = Reservation::query()
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        $totalReservations = (int) (clone $reservationQuery)->count();
        $totalPeople = (int) (clone $reservationQuery)->sum('broj_putnika');
        $totalArrangements = (int) (clone $reservationQuery)->distinct('aranzman_id')->count('aranzman_id');

        $totalRevenue = (float) DB::table('reservation_clients')
            ->join('reservations', 'reservations.id', '=', 'reservation_clients.rezervacija_id')
            ->leftJoin('arrangement_packages', 'arrangement_packages.id', '=', 'reservation_clients.paket_id')
            ->whereNull('reservation_clients.deleted_at')
            ->whereNull('reservations.deleted_at')
            ->whereBetween('reservations.created_at', [$dateFrom, $dateTo])
            ->selectRaw(
                'COALESCE(SUM(COALESCE(arrangement_packages.cijena, 0) + COALESCE(reservation_clients.dodatno_na_cijenu, 0) + COALESCE(reservation_clients.boravisna_taksa, 0) + COALESCE(reservation_clients.osiguranje, 0) + COALESCE(reservation_clients.doplata_jednokrevetna_soba, 0) + COALESCE(reservation_clients.doplata_dodatno_sjediste, 0) + COALESCE(reservation_clients.doplata_sjediste_po_zelji, 0) - COALESCE(reservation_clients.popust, 0)), 0) as total'
            )
            ->value('total');

        $statusBreakdown = (clone $reservationQuery)
            ->select('status')
            ->selectRaw('COUNT(*) as reservations_count')
            ->groupBy('status')
            ->orderByDesc('reservations_count')
            ->get()
            ->map(fn ($row) => [
                'status' => (string) $row->status,
                'count' => (int) $row->reservations_count,
            ])
            ->values();

        $dailyReservationRows = (clone $reservationQuery)
            ->selectRaw('DATE(created_at) as metric_date')
            ->selectRaw('COUNT(*) as reservations_count')
            ->selectRaw('COALESCE(SUM(broj_putnika), 0) as people_count')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('metric_date')
            ->get()
            ->keyBy('metric_date');

        $dailyRevenueRows = DB::table('reservation_clients')
            ->join('reservations', 'reservations.id', '=', 'reservation_clients.rezervacija_id')
            ->leftJoin('arrangement_packages', 'arrangement_packages.id', '=', 'reservation_clients.paket_id')
            ->whereNull('reservation_clients.deleted_at')
            ->whereNull('reservations.deleted_at')
            ->whereBetween('reservations.created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(reservations.created_at) as metric_date')
            ->selectRaw(
                'COALESCE(SUM(COALESCE(arrangement_packages.cijena, 0) + COALESCE(reservation_clients.dodatno_na_cijenu, 0) + COALESCE(reservation_clients.boravisna_taksa, 0) + COALESCE(reservation_clients.osiguranje, 0) + COALESCE(reservation_clients.doplata_jednokrevetna_soba, 0) + COALESCE(reservation_clients.doplata_dodatno_sjediste, 0) + COALESCE(reservation_clients.doplata_sjediste_po_zelji, 0) - COALESCE(reservation_clients.popust, 0)), 0) as total_revenue'
            )
            ->groupBy(DB::raw('DATE(reservations.created_at)'))
            ->orderBy('metric_date')
            ->get()
            ->keyBy('metric_date');

        $dailyTrend = $this->buildDailyTrend($dateFrom, $dateTo, $dailyReservationRows, $dailyRevenueRows);

        $reservationRows = Reservation::query()
            ->with([
                'arrangement:id,sifra,naziv_putovanja,destinacija,datum_polaska,datum_povratka',
                'reservationClients.client:id,ime,prezime,email',
                'reservationClients.package:id,naziv,cijena',
            ])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Reservation $reservation): array {
                $clients = $reservation->reservationClients
                    ->map(fn ($item) => trim(($item->client?->ime ?? '').' '.($item->client?->prezime ?? '')))
                    ->filter()
                    ->values();

                $revenue = $reservation->reservationClients
                    ->sum(fn ($item) => (float) ($item->package?->cijena ?? 0)
                        + (float) ($item->dodatno_na_cijenu ?? 0)
                        + (float) ($item->boravisna_taksa ?? 0)
                        + (float) ($item->osiguranje ?? 0)
                        + (float) ($item->doplata_jednokrevetna_soba ?? 0)
                        + (float) ($item->doplata_dodatno_sjediste ?? 0)
                        + (float) ($item->doplata_sjediste_po_zelji ?? 0)
                        - (float) ($item->popust ?? 0));

                return [
                    'reservation_id' => (string) $reservation->id,
                    'reservation_number' => (string) ($reservation->order_num ?? ''),
                    'created_at' => $reservation->created_at?->toDateString() ?? '',
                    'status' => (string) ($reservation->status ?? ''),
                    'payment_status' => (string) ($reservation->placanje ?? ''),
                    'people_count' => (int) ($reservation->broj_putnika ?? 0),
                    'client_names' => $clients->implode(', '),
                    'arrangement_id' => (string) ($reservation->arrangement?->id ?? ''),
                    'arrangement_code' => (string) ($reservation->arrangement?->sifra ?? ''),
                    'arrangement_name' => (string) ($reservation->arrangement?->naziv_putovanja ?? ''),
                    'destination' => (string) ($reservation->arrangement?->destinacija ?? ''),
                    'departure_date' => $reservation->arrangement?->datum_polaska?->toDateString() ?? '',
                    'return_date' => $reservation->arrangement?->datum_povratka?->toDateString() ?? '',
                    'revenue' => (float) $revenue,
                ];
            })
            ->values();

        $allArrangementRows = $reservationRows
            ->groupBy(fn (array $row) => ($row['arrangement_id'] ?: 'unknown').'|'.$row['arrangement_code'].'|'.$row['arrangement_name'])
            ->map(function (Collection $rows): array {
                /** @var array<string, mixed> $firstRow */
                $firstRow = $rows->first() ?? [];

                return [
                    'arrangement_id' => (string) ($firstRow['arrangement_id'] ?? ''),
                    'arrangement_code' => (string) ($firstRow['arrangement_code'] ?? ''),
                    'arrangement_name' => (string) ($firstRow['arrangement_name'] ?? ''),
                    'destination' => (string) ($firstRow['destination'] ?? ''),
                    'departure_date' => (string) ($firstRow['departure_date'] ?? ''),
                    'return_date' => (string) ($firstRow['return_date'] ?? ''),
                    'reservations_count' => (int) $rows->count(),
                    'people_count' => (int) $rows->sum(fn (array $row) => (int) $row['people_count']),
                    'total_revenue' => round((float) $rows->sum(fn (array $row) => (float) $row['revenue']), 2),
                ];
            })
            ->sortByDesc('total_revenue');

        $arrangementPerformance = $allArrangementRows
            ->take(10)
            ->values();

        return [
            'summary' => [
                'total_revenue' => round($totalRevenue, 2),
                'total_people' => $totalPeople,
                'total_reservations' => $totalReservations,
                'total_arrangements' => $totalArrangements,
            ],
            'status_breakdown' => $statusBreakdown,
            'daily_trend' => $dailyTrend,
            'arrangement_performance' => $arrangementPerformance,
            'arrangement_export_rows' => $allArrangementRows->values(),
            'reservation_rows' => $reservationRows,
        ];
    }

    /**
     * @param  Collection<string, object>  $dailyReservationRows
     * @param  Collection<string, object>  $dailyRevenueRows
     * @return array<int, array<string, mixed>>
     */
    private function buildDailyTrend(
        CarbonImmutable $dateFrom,
        CarbonImmutable $dateTo,
        Collection $dailyReservationRows,
        Collection $dailyRevenueRows,
    ): array {
        $trend = [];
        $cursor = $dateFrom->startOfDay();
        $end = $dateTo->startOfDay();

        while ($cursor->lessThanOrEqualTo($end)) {
            $dateKey = $cursor->toDateString();
            $reservationRow = $dailyReservationRows->get($dateKey);
            $revenueRow = $dailyRevenueRows->get($dateKey);

            $trend[] = [
                'date' => $dateKey,
                'reservations_count' => (int) ($reservationRow?->reservations_count ?? 0),
                'people_count' => (int) ($reservationRow?->people_count ?? 0),
                'total_revenue' => round((float) ($revenueRow?->total_revenue ?? 0), 2),
            ];

            $cursor = $cursor->addDay();
        }

        return $trend;
    }
}
