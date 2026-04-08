<?php

namespace App\Exports\Reports;

use Carbon\CarbonImmutable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ReportsWorkbookExport implements WithMultipleSheets
{
    /**
     * @param  array{total_revenue: float, total_people: int, total_reservations: int, total_arrangements: int}  $summary
     * @param  array<int, array{date: string, reservations_count: int, people_count: int, total_revenue: float}>  $dailyTrend
     * @param  array<int, array{arrangement_id: string, arrangement_code: string, arrangement_name: string, destination: string, departure_date: string, return_date: string, reservations_count: int, people_count: int, total_revenue: float}>  $arrangementPerformance
     * @param  array<int, array{reservation_id: string, reservation_number: string, created_at: string, status: string, payment_status: string, people_count: int, client_names: string, arrangement_id: string, arrangement_code: string, arrangement_name: string, destination: string, departure_date: string, return_date: string, revenue: float}>  $reservations
     */
    public function __construct(
        private readonly CarbonImmutable $dateFrom,
        private readonly CarbonImmutable $dateTo,
        private readonly array $summary,
        private readonly array $dailyTrend,
        private readonly array $arrangementPerformance,
        private readonly array $reservations,
    ) {
    }

    /**
     * @return array<int, object>
     */
    public function sheets(): array
    {
        $dailyRows = array_map(
            static fn (array $row): array => [
                $row['date'],
                $row['reservations_count'],
                $row['people_count'],
                $row['total_revenue'],
            ],
            $this->dailyTrend
        );

        $weeklyRows = $this->aggregateByWeek($this->dailyTrend);
        $monthlyRows = $this->aggregateByMonth($this->dailyTrend);

        $financialRows = [
            [
                'Ukupno',
                sprintf('%s - %s', $this->dateFrom->format('d.m.Y'), $this->dateTo->format('d.m.Y')),
                number_format($this->summary['total_revenue'], 2, '.', ''),
                $this->summary['total_reservations'],
                $this->summary['total_people'],
            ],
            ...array_map(
                static fn (array $row): array => [
                    'Po danu',
                    CarbonImmutable::parse($row[0])->format('d.m.Y'),
                    number_format((float) $row[3], 2, '.', ''),
                    (int) $row[1],
                    (int) $row[2],
                ],
                $dailyRows
            ),
            ...array_map(
                static fn (array $row): array => [
                    'Po sedmici',
                    (string) $row[0],
                    number_format((float) $row[3], 2, '.', ''),
                    (int) $row[1],
                    (int) $row[2],
                ],
                $weeklyRows
            ),
            ...array_map(
                static fn (array $row): array => [
                    'Po mjesecu',
                    (string) $row[0],
                    number_format((float) $row[3], 2, '.', ''),
                    (int) $row[1],
                    (int) $row[2],
                ],
                $monthlyRows
            ),
            ...array_map(
                static fn (array $row): array => [
                    'Po aranžmanu',
                    sprintf('%s - %s', $row['arrangement_code'], $row['arrangement_name']),
                    number_format((float) $row['total_revenue'], 2, '.', ''),
                    (int) $row['reservations_count'],
                    (int) $row['people_count'],
                ],
                $this->arrangementPerformance
            ),
        ];

        $arrangementRows = array_map(
            static fn (array $row): array => [
                $row['arrangement_code'],
                $row['arrangement_name'],
                $row['destination'],
                $row['departure_date'] ? CarbonImmutable::parse($row['departure_date'])->format('d.m.Y') : '',
                $row['return_date'] ? CarbonImmutable::parse($row['return_date'])->format('d.m.Y') : '',
                $row['reservations_count'],
                $row['people_count'],
                number_format($row['total_revenue'], 2, '.', ''),
                number_format(
                    $row['reservations_count'] > 0 ? ($row['total_revenue'] / $row['reservations_count']) : 0,
                    2,
                    '.',
                    ''
                ),
            ],
            $this->arrangementPerformance
        );

        $reservationRows = array_map(
            static fn (array $row): array => [
                $row['reservation_number'],
                $row['created_at'],
                $row['status'],
                $row['payment_status'],
                $row['people_count'],
                $row['client_names'],
                $row['arrangement_code'],
                $row['arrangement_name'],
                $row['destination'],
                $row['departure_date'] ? CarbonImmutable::parse($row['departure_date'])->format('d.m.Y') : '',
                $row['return_date'] ? CarbonImmutable::parse($row['return_date'])->format('d.m.Y') : '',
                number_format($row['revenue'], 2, '.', ''),
            ],
            $this->reservations
        );

        return [
            new ArraySheetExport(
                title: 'Finansije',
                headings: ['Sekcija', 'Period', 'Iznos (KM)', 'Broj rezervacija', 'Broj putnika'],
                rows: $financialRows,
            ),
            new ArraySheetExport(
                title: 'Aranžmani',
                headings: ['Šifra', 'Naziv aranžmana', 'Destinacija', 'Datum polaska', 'Datum povratka', 'Broj rezervacija', 'Broj putnika', 'Iznos (KM)', 'Prosjek po rezervaciji (KM)'],
                rows: $arrangementRows,
            ),
            new ArraySheetExport(
                title: 'Rezervacije',
                headings: ['Broj', 'Datum rezervacije', 'Status', 'Plaćanje', 'Broj putnika', 'Putnici', 'Šifra aranžmana', 'Naziv aranžmana', 'Destinacija', 'Datum polaska', 'Datum povratka', 'Iznos (KM)'],
                rows: $reservationRows,
            ),
        ];
    }

    /**
     * @param  array<int, array{date: string, reservations_count: int, people_count: int, total_revenue: float}>  $dailyRows
     * @return array<int, array{0: string, 1: int, 2: int, 3: float}>
     */
    private function aggregateByWeek(array $dailyRows): array
    {
        $weekBuckets = [];

        foreach ($dailyRows as $row) {
            $date = CarbonImmutable::parse($row['date']);
            $bucket = sprintf('%d-W%02d', $date->isoWeekYear(), $date->isoWeek());

            if (! isset($weekBuckets[$bucket])) {
                $weekBuckets[$bucket] = [0, 0, 0.0];
            }

            $weekBuckets[$bucket][0] += (int) $row['reservations_count'];
            $weekBuckets[$bucket][1] += (int) $row['people_count'];
            $weekBuckets[$bucket][2] += (float) $row['total_revenue'];
        }

        $result = [];
        foreach ($weekBuckets as $period => [$reservations, $people, $revenue]) {
            $result[] = [$period, $reservations, $people, round($revenue, 2)];
        }

        return $result;
    }

    /**
     * @param  array<int, array{date: string, reservations_count: int, people_count: int, total_revenue: float}>  $dailyRows
     * @return array<int, array{0: string, 1: int, 2: int, 3: float}>
     */
    private function aggregateByMonth(array $dailyRows): array
    {
        $monthBuckets = [];

        foreach ($dailyRows as $row) {
            $date = CarbonImmutable::parse($row['date']);
            $bucket = $date->format('Y-m');

            if (! isset($monthBuckets[$bucket])) {
                $monthBuckets[$bucket] = [0, 0, 0.0];
            }

            $monthBuckets[$bucket][0] += (int) $row['reservations_count'];
            $monthBuckets[$bucket][1] += (int) $row['people_count'];
            $monthBuckets[$bucket][2] += (float) $row['total_revenue'];
        }

        $result = [];
        foreach ($monthBuckets as $period => [$reservations, $people, $revenue]) {
            $result[] = [$period, $reservations, $people, round($revenue, 2)];
        }

        return $result;
    }
}
