<?php

namespace App\Exports;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CashDeskPaymentLedgerExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithStyles
{
    /**
     * @param  Collection<int, array{id: string, reservation_number: string, payment_date: string, payer: string, arrangement: string, payment_kind: string, payment_method: string, amount: float}>  $rows
     */
    public function __construct(private readonly Collection $rows) {}

    /**
     * @return array<int, array<int, string|float>>
     */
    public function array(): array
    {
        return $this->rows
            ->map(fn (array $row): array => [
                $row['reservation_number'],
                $this->formatDate($row['payment_date']),
                $row['payer'],
                $row['arrangement'],
                $row['payment_kind'],
                $row['payment_method'],
                round((float) $row['amount'], 2),
            ])
            ->all();
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'Broj rezervacije',
            'Datum uplate',
            'Ko je uplatio',
            'Aranžman',
            'Vrsta uplate',
            'Način uplate',
            'Iznos (KM)',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'G' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    /**
     * @return array<int, array<string, array<string, bool>>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    private function formatDate(string $date): string
    {
        if ($date === '') {
            return '';
        }

        return CarbonImmutable::parse($date)->format('d.m.Y');
    }
}
