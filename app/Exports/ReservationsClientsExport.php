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
            'Redni broj',
            'Ime i prezime',
            'Grad',
            'Telefon',
            'Datum rođenja',
            'Broj dokumenta',
            'Broj rezervacije',
        ];
    }
}
