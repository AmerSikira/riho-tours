<?php

namespace App\Services\CashDesk;

use App\Models\Arrangement;
use App\Models\Reservation;
use App\Models\ReservationClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PaymentLedgerService
{
    /**
     * Build all ledger rows matching the current Blagajna filters.
     *
     * @return Collection<int, array{id: string, reservation_number: string, payment_date: string, payer: string, arrangement: string, payment_kind: string, payment_method: string, amount: float}>
     */
    public function rows(string $search, string $arrangementId, string $dateFrom, string $dateTo): Collection
    {
        return $this->reservationQuery(
            search: $search,
            arrangementId: $arrangementId,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        )
            ->get()
            ->flatMap(fn (Reservation $reservation): Collection => $this->rowsForReservation($reservation))
            ->values();
    }

    /**
     * Paginate ledger rows after flattening reservations into payment entries.
     *
     * @return LengthAwarePaginator<int, array{id: string, reservation_number: string, payment_date: string, payer: string, arrangement: string, payment_kind: string, payment_method: string, amount: float}>
     */
    public function paginate(
        string $search,
        string $arrangementId,
        string $dateFrom,
        string $dateTo,
        int $perPage,
        int $page,
        string $path,
        array $query
    ): LengthAwarePaginator {
        $rows = $this->rows(
            search: $search,
            arrangementId: $arrangementId,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        );

        return new LengthAwarePaginator(
            items: $rows->forPage($page, $perPage)->values(),
            total: $rows->count(),
            perPage: $perPage,
            currentPage: $page,
            options: [
                'path' => $path,
                'query' => $query,
            ],
        );
    }

    /**
     * Build the reservation query shared by the ledger page and export.
     *
     * @return Builder<Reservation>
     */
    private function reservationQuery(string $search, string $arrangementId, string $dateFrom, string $dateTo): Builder
    {
        return Reservation::query()
            ->with([
                'arrangement:id,sifra,naziv_putovanja,destinacija,datum_polaska,datum_povratka',
                'reservationClients.client:id,ime,prezime',
                'reservationClients.package:id,cijena',
                'client:id,ime,prezime',
            ])
            ->whereHas('arrangement', function (Builder $query) use ($arrangementId): void {
                if ($arrangementId !== '') {
                    $query->whereKey($arrangementId);
                }
            })
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('ime_prezime', 'like', "%{$search}%")
                        ->orWhereHas('client', function (Builder $clientQuery) use ($search): void {
                            $clientQuery
                                ->where('ime', 'like', "%{$search}%")
                                ->orWhere('prezime', 'like', "%{$search}%");
                        })
                        ->orWhereHas('reservationClients.client', function (Builder $clientQuery) use ($search): void {
                            $clientQuery
                                ->where('ime', 'like', "%{$search}%")
                                ->orWhere('prezime', 'like', "%{$search}%");
                        })
                        ->orWhereHas('arrangement', function (Builder $arrangementQuery) use ($search): void {
                            $arrangementQuery
                                ->where('sifra', 'like', "%{$search}%")
                                ->orWhere('naziv_putovanja', 'like', "%{$search}%")
                                ->orWhere('destinacija', 'like', "%{$search}%");
                        });
                });
            })
            ->when($dateFrom !== '', fn (Builder $query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn (Builder $query) => $query->whereDate('created_at', '<=', $dateTo))
            ->orderByDesc('created_at')
            ->orderByDesc('order_num');
    }

    /**
     * Build payment entries for one reservation without splitting passengers into separate rows.
     *
     * @return Collection<int, array{id: string, reservation_number: string, payment_date: string, payer: string, arrangement: string, payment_kind: string, payment_method: string, amount: float}>
     */
    private function rowsForReservation(Reservation $reservation): Collection
    {
        $paymentPlan = (string) ($reservation->placanje ?? '');

        if ($paymentPlan === 'placeno') {
            $amount = round($this->reservationTotalAmount($reservation), 2);

            if ($amount <= 0) {
                return collect();
            }

            return collect([
                $this->rowForPayment(
                    reservation: $reservation,
                    entryId: 'full',
                    paymentDate: $reservation->created_at?->toDateString() ?? '',
                    paymentKind: 'Kompletna uplata',
                    amount: $amount,
                ),
            ]);
        }

        if ($paymentPlan !== 'na_rate') {
            return collect();
        }

        $rateRows = is_array($reservation->rate) ? array_values($reservation->rate) : [];

        return collect($rateRows)
            ->map(function (mixed $rate, int $index) use ($reservation): ?array {
                if (! is_array($rate)) {
                    return null;
                }

                $amount = $this->moneyValue($rate['iznos_uplate'] ?? null);
                if ($amount <= 0) {
                    return null;
                }

                $paymentDate = trim((string) ($rate['datum_uplate'] ?? ''));

                return $this->rowForPayment(
                    reservation: $reservation,
                    entryId: sprintf('installment-%d', $index + 1),
                    paymentDate: $paymentDate !== '' ? $paymentDate : ($reservation->created_at?->toDateString() ?? ''),
                    paymentKind: sprintf('Rata %d', $index + 1),
                    amount: round($amount, 2),
                );
            })
            ->filter()
            ->values();
    }

    /**
     * @return array{id: string, reservation_number: string, payment_date: string, payer: string, arrangement: string, payment_kind: string, payment_method: string, amount: float}
     */
    private function rowForPayment(
        Reservation $reservation,
        string $entryId,
        string $paymentDate,
        string $paymentKind,
        float $amount
    ): array {
        return [
            'id' => sprintf('%s:%s', (string) $reservation->id, $entryId),
            'reservation_number' => (string) ($reservation->order_num ?? ''),
            'payment_date' => $paymentDate,
            'payer' => $this->payerLabel($reservation),
            'arrangement' => $this->arrangementLabel($reservation->arrangement),
            'payment_kind' => $paymentKind,
            'payment_method' => $this->paymentMethodLabel((string) ($reservation->nacin_uplate ?? 'cash')),
            'amount' => $amount,
        ];
    }

    /**
     * Calculate reservation total amount from packages, add-ons and discounts.
     */
    private function reservationTotalAmount(Reservation $reservation): float
    {
        $packageTotal = 0.0;
        $addOnsTotal = 0.0;
        $discountTotal = 0.0;

        foreach ($reservation->reservationClients as $item) {
            $packageTotal += (float) ($item->package?->cijena ?? 0);
            $addOnsTotal += (float) ($item->dodatno_na_cijenu ?? 0);
            $addOnsTotal += (float) ($item->boravisna_taksa ?? 0);
            $addOnsTotal += (float) ($item->osiguranje ?? 0);
            $addOnsTotal += (float) ($item->doplata_jednokrevetna_soba ?? 0);
            $addOnsTotal += (float) ($item->doplata_dodatno_sjediste ?? 0);
            $addOnsTotal += (float) ($item->doplata_sjediste_po_zelji ?? 0);
            $discountTotal += (float) ($item->popust ?? 0);
        }

        return $packageTotal + $addOnsTotal - $discountTotal;
    }

    /**
     * Build payer display label from linked clients.
     */
    private function payerLabel(Reservation $reservation): string
    {
        $linkedClients = $reservation->reservationClients
            ->map(fn (ReservationClient $item) => trim("{$item->client?->ime} {$item->client?->prezime}"))
            ->filter()
            ->values();

        if ($linkedClients->isNotEmpty()) {
            return $linkedClients->implode(', ');
        }

        $singleClient = trim((string) ($reservation->client?->ime ?? '').' '.(string) ($reservation->client?->prezime ?? ''));
        if ($singleClient !== '') {
            return $singleClient;
        }

        return (string) ($reservation->ime_prezime ?? '-');
    }

    private function arrangementLabel(?Arrangement $arrangement): string
    {
        if (! $arrangement) {
            return '-';
        }

        $parts = array_filter([
            (string) ($arrangement->sifra ?? ''),
            (string) ($arrangement->naziv_putovanja ?? ''),
        ]);

        return $parts === [] ? '-' : implode(' - ', $parts);
    }

    private function paymentMethodLabel(string $paymentMethod): string
    {
        return $paymentMethod === 'bank' ? 'Banka' : 'Gotovina';
    }

    private function moneyValue(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (! is_string($value)) {
            return 0.0;
        }

        $normalized = str_replace(',', '.', trim($value));

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }
}
