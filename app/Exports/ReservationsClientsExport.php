<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReservationsClientsExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    public function __construct(private readonly Collection $rows)
    {
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function collection(): Collection
    {
        return $this->rows;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'Reservation ID',
            'Reservation Number',
            'Arrangement Code',
            'Arrangement Name',
            'Destination',
            'Departure Date',
            'Return Date',
            'Reservation Status',
            'Payment Status',
            'Fiscal Invoice Number',
            'Client ID',
            'First Name',
            'Last Name',
            'Broj dokumenta',
            'Date of Birth',
            'Address',
            'Phone',
            'Email',
            'Photo Path',
            'Package Name',
            'Package Price',
            'Extra Charge',
            'Discount',
            'Line Total',
            'Reservation Note',
        ];
    }
}
