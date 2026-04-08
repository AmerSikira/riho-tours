<?php

namespace App\Services\Contracts;

use App\Models\Reservation;
use App\Models\ReservationClient;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class ContractDataBuilder
{
    /**
     * Build a normalized contract data payload for one reservation.
     *
     * @return array<string, mixed>
     */
    public function build(Reservation $reservation): array
    {
        $reservation->loadMissing([
            'arrangement:id,sifra,naziv_putovanja,destinacija,datum_polaska,datum_povratka',
            'reservationClients.client:id,ime,prezime,adresa,broj_telefona,email',
            'reservationClients.package:id,naziv,cijena',
            'client:id,ime,prezime,adresa,broj_telefona,email',
        ]);

        $setting = Setting::query()->first();
        $items = $this->buildItems($reservation);
        $travelers = $this->buildTravelers($reservation);
        $primaryTraveler = $travelers[0] ?? [
            'full_name' => trim((string) $reservation->ime_prezime),
            'address' => null,
            'phone' => $reservation->telefon,
            'email' => $reservation->email,
        ];

        $total = array_reduce($items, static fn (float $sum, array $item): float => $sum + (float) $item['total'], 0.0);

        $contractDate = Carbon::now();

        $company = [
                'name' => (string) ($setting?->company_name ?? ''),
                'address' => trim(implode(', ', array_filter([
                    $setting?->address,
                    $setting?->zip,
                    $setting?->city,
                ]))),
                'id_number' => (string) ($setting?->company_id ?? ''),
                'registry_number' => (string) ($setting?->maticni_broj_subjekta_upisa ?? ''),
                'vat_number' => (string) ($setting?->pdv ?? ''),
                'representative_name' => (string) ($setting?->trn ?? ''),
                'bank_name' => (string) ($setting?->banka ?? ''),
                'iban' => (string) ($setting?->iban ?? ''),
                'swift' => (string) ($setting?->swift ?? ''),
                'insurance_company' => (string) ($setting?->osiguravajuce_drustvo ?? ''),
                'osiguravajuce_drustvo' => (string) ($setting?->osiguravajuce_drustvo ?? ''),
                'phone' => (string) ($setting?->phone ?? ''),
                'email' => (string) ($setting?->email ?? ''),
                'logo_url' => $this->resolveDocumentImageSource($setting?->logo_path),
                'signature_url' => $this->resolveDocumentImageSource($setting?->potpis_path),
                'stamp_url' => $this->resolveDocumentImageSource($setting?->pecat_path),
            ];
        $contract = [
            'number' => $this->contractNumber($reservation, $contractDate),
            'date' => $contractDate->format('d.m.Y'),
        ];
        $traveler = [
                'full_name' => $primaryTraveler['full_name'] ?? '',
                'address' => $primaryTraveler['address'] ?? '',
                'phone' => $primaryTraveler['phone'] ?? '',
                'email' => $primaryTraveler['email'] ?? '',
            ];
        $arrangement = [
            'name' => (string) ($reservation->arrangement?->naziv_putovanja ?? ''),
            'period' => $this->formatPeriod(
                $reservation->arrangement?->datum_polaska?->format('Y-m-d'),
                $reservation->arrangement?->datum_povratka?->format('Y-m-d')
            ),
            'destination' => (string) ($reservation->arrangement?->destinacija ?? ''),
            'code' => (string) ($reservation->arrangement?->sifra ?? ''),
            'insurance_policy' => (string) ($setting?->polisa_osiguranja ?? ''),
            'polisa_osiguranja' => (string) ($setting?->polisa_osiguranja ?? ''),
        ];
        $finance = [
            'total' => number_format($total, 2, '.', ''),
        ];

        return [
            'company' => $company,
            'contract' => $contract,
            'traveler' => $traveler,
            'travelers' => $travelers,
            'arrangement' => $arrangement,
            'finance' => $finance,
            // Bosnian aliases for localized contract placeholders.
            'kompanija' => [
                'naziv' => $company['name'],
                'adresa' => $company['address'],
                'id_broj' => $company['id_number'],
                'maticni_broj_subjekta_upisa' => $company['registry_number'],
                'pdv_broj' => $company['vat_number'],
                'trn' => $company['representative_name'],
                'banka' => $company['bank_name'],
                'iban' => $company['iban'],
                'swift' => $company['swift'],
                'telefon' => $company['phone'],
                'email' => $company['email'],
                'osiguravajuce_drustvo' => $company['osiguravajuce_drustvo'],
                'potpis_url' => $company['signature_url'],
                'pecat_url' => $company['stamp_url'],
            ],
            'ugovor' => [
                'broj' => $contract['number'],
                'datum' => $contract['date'],
            ],
            'putnik' => [
                'puno_ime' => $traveler['full_name'],
                'adresa' => $traveler['address'],
                'telefon' => $traveler['phone'],
                'email' => $traveler['email'],
            ],
            'aranzman' => [
                'naziv' => $arrangement['name'],
                'period' => $arrangement['period'],
                'destinacija' => $arrangement['destination'],
                'sifra' => $arrangement['code'],
                'polisa_osiguranja' => $arrangement['polisa_osiguranja'],
            ],
            'finansije' => [
                'ukupno' => $finance['total'],
            ],
            'items' => $items,
        ];
    }

    /**
     * Build computed placeholders from normalized contract data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    public function buildComputedPlaceholders(array $data): array
    {
        return [
            'travelers_list' => $this->renderTravelersList($data['travelers'] ?? []),
            'items_table' => $this->renderItemsTable($data['items'] ?? []),
            'company.full_legal_block' => $this->renderCompanyLegalBlock($data['company'] ?? []),
            'company.signature_image' => $this->renderImageTag(data_get($data, 'company.signature_url'), 'Potpis'),
            'company.stamp_image' => $this->renderImageTag(data_get($data, 'company.stamp_url'), 'Pečat'),
            // Bosnian aliases for computed placeholders.
            'lista_putnika' => $this->renderTravelersList($data['travelers'] ?? []),
            'tabela_stavki' => $this->renderItemsTable($data['items'] ?? []),
            'kompanija.puni_pravni_blok' => $this->renderCompanyLegalBlock($data['company'] ?? []),
            'kompanija.potpis_slika' => $this->renderImageTag(data_get($data, 'company.signature_url'), 'Potpis'),
            'kompanija.pecat_slika' => $this->renderImageTag(data_get($data, 'company.stamp_url'), 'Pečat'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildItems(Reservation $reservation): array
    {
        $period = $this->formatPeriod(
            $reservation->arrangement?->datum_polaska?->format('Y-m-d'),
            $reservation->arrangement?->datum_povratka?->format('Y-m-d')
        );

        return $reservation->reservationClients
            ->map(function (ReservationClient $item, int $index): array {
                $basePrice = (float) ($item->package?->cijena ?? 0);
                $priceAdjustment = (float) ($item->dodatno_na_cijenu ?? 0);
                $discount = (float) ($item->popust ?? 0);
                $lineTotal = max($basePrice + $priceAdjustment - $discount, 0);

                return [
                    'index' => $index + 1,
                    'name' => (string) ($item->package?->naziv ?? 'Package'),
                    'traveler' => trim(implode(' ', array_filter([
                        $item->client?->ime,
                        $item->client?->prezime,
                    ]))),
                    'base_price' => $basePrice,
                    'price_adjustment' => $priceAdjustment,
                    'discount' => $discount,
                    'total' => $lineTotal,
                ];
            })
            ->map(function (array $item) use ($period): array {
                $item['period'] = $period;
                $item['quantity'] = 1;

                return $item;
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, string|null>>
     */
    private function buildTravelers(Reservation $reservation): array
    {
        $travelers = $reservation->reservationClients
            ->map(function (ReservationClient $item): array {
                return [
                    'full_name' => trim(implode(' ', array_filter([
                        $item->client?->ime,
                        $item->client?->prezime,
                    ]))),
                    'address' => $item->client?->adresa,
                    'phone' => $item->client?->broj_telefona,
                    'email' => $item->client?->email,
                ];
            })
            ->filter(fn (array $traveler): bool => $traveler['full_name'] !== '')
            ->values();

        if ($travelers->isNotEmpty()) {
            return $travelers->all();
        }

        if ($reservation->client) {
            return [[
                'full_name' => trim(implode(' ', array_filter([
                    $reservation->client->ime,
                    $reservation->client->prezime,
                ]))),
                'address' => $reservation->client->adresa,
                'phone' => $reservation->client->broj_telefona,
                'email' => $reservation->client->email,
            ]];
        }

        return [];
    }

    private function formatPeriod(?string $from, ?string $to): string
    {
        if ($from === null || $to === null) {
            return '';
        }

        return sprintf('%s - %s', $this->formatDate($from), $this->formatDate($to));
    }

    private function formatDate(string $value): string
    {
        try {
            return Carbon::parse($value)->format('d.m.Y');
        } catch (\Throwable) {
            return $value;
        }
    }

    /**
     * Build stable contract number from reservation and current date.
     */
    private function contractNumber(Reservation $reservation, Carbon $date): string
    {
        return $reservation->documentNumber($date);
    }

    private function renderTravelersList(mixed $travelers): string
    {
        if (! is_array($travelers) || $travelers === []) {
            return '<ol class="travelers-list"><li>Nema putnika</li></ol>';
        }

        $items = collect($travelers)
            ->map(function ($traveler, int $index): string {
                $name = is_array($traveler) ? (string) ($traveler['full_name'] ?? 'Nepoznat putnik') : 'Nepoznat putnik';

                return sprintf('<li><span class="strong">%d. %s</span></li>', $index + 1, e($name));
            })
            ->implode('');

        return sprintf('<ol class="travelers-list">%s</ol>', $items);
    }

    private function renderItemsTable(mixed $items): string
    {
        if (! is_array($items) || $items === []) {
            return '<table class="items-table"><tbody><tr><td colspan="6">Nema stavki</td></tr></tbody></table>';
        }

        $totalAmount = 0.0;
        $rows = collect($items)
            ->map(function ($item): string {
                if (! is_array($item)) {
                    return '';
                }

                $lineTotal = (float) ($item['total'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? 1);
                $unitPrice = $quantity > 0 ? $lineTotal / $quantity : $lineTotal;

                return sprintf(
                    '<tr>
                        <td class="text-center">%s</td>
                        <td>%s</td>
                        <td class="text-center">%s</td>
                        <td class="text-right">%s</td>
                        <td class="text-center">%s</td>
                        <td class="text-right">%s</td>
                    </tr>',
                    e((string) ($item['index'] ?? '')),
                    e((string) ($item['name'] ?? '')),
                    e((string) ($item['period'] ?? '')),
                    e(number_format($unitPrice, 2, '.', '')),
                    e((string) $quantity),
                    e(number_format($lineTotal, 2, '.', ''))
                );
            })
            ->implode('');

        foreach ($items as $item) {
            if (is_array($item)) {
                $totalAmount += (float) ($item['total'] ?? 0);
            }
        }

        return sprintf(
            '<table class="items-table">
                <thead>
                    <tr>
                        <th>Br.</th>
                        <th>Usluga</th>
                        <th>Termin</th>
                        <th>Cijena (KM)</th>
                        <th>Količina</th>
                        <th>Iznos (KM)</th>
                    </tr>
                </thead>
                <tbody>%s</tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-right"><strong>UKUPNO</strong></td>
                        <td class="text-right"><strong>%s</strong></td>
                    </tr>
                </tfoot>
            </table>',
            $rows,
            e(number_format($totalAmount, 2, '.', ''))
        );
    }

    private function renderCompanyLegalBlock(mixed $company): string
    {
        if (! is_array($company)) {
            return '';
        }

        $lines = [
            $company['name'] ?? '',
            $company['address'] ?? '',
            'ID: '.($company['id_number'] ?? ''),
            'VAT: '.($company['vat_number'] ?? ''),
            'Representative: '.($company['representative_name'] ?? ''),
        ];

        $htmlLines = collect($lines)
            ->filter(fn (mixed $line): bool => trim((string) $line) !== '')
            ->map(fn (mixed $line): string => e((string) $line))
            ->implode('<br>');

        return sprintf('<div class="company-legal-block">%s</div>', $htmlLines);
    }

    private function renderImageTag(mixed $src, string $alt): string
    {
        if (! is_string($src) || trim($src) === '') {
            return '';
        }

        return sprintf(
            '<img src="%s" alt="%s" style="max-height:80px; max-width:220px; object-fit:contain;" />',
            e($src),
            e($alt)
        );
    }

    /**
     * Resolve image source that renders reliably in generated PDF output.
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
}
