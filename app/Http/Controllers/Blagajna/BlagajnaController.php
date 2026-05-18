<?php

namespace App\Http\Controllers\Blagajna;

use App\Http\Controllers\Controller;
use App\Models\Arrangement;
use App\Models\Reservation;
use App\Models\ReservationClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BlagajnaController extends Controller
{
    /**
     * Display payment overview with filters.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('pretraga'));
        $aranzmanId = trim((string) $request->string('aranzman_id'));
        $datumOd = trim((string) $request->string('datum_od'));
        $datumDo = trim((string) $request->string('datum_do'));

        $uplate = $this->baseQuery(
            search: $search,
            aranzmanId: $aranzmanId,
            datumOd: $datumOd,
            datumDo: $datumDo,
        )
            ->paginate(15)
            ->withQueryString();

        $uplate->setCollection(
            $uplate->getCollection()->map(fn (Reservation $rezervacija): array => $this->transformReservation($rezervacija))
        );

        return Inertia::render('blagajna/index', [
            'uplate' => $uplate,
            'filters' => [
                'pretraga' => $search,
                'aranzman_id' => $aranzmanId,
                'datum_od' => $datumOd,
                'datum_do' => $datumDo,
            ],
            'selected_aranzman' => $this->selectedArrangement($aranzmanId),
        ]);
    }

    /**
     * Search arrangements for blagajna filter autocomplete.
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
     * Export currently filtered payments to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $search = trim((string) $request->string('pretraga'));
        $aranzmanId = trim((string) $request->string('aranzman_id'));
        $datumOd = trim((string) $request->string('datum_od'));
        $datumDo = trim((string) $request->string('datum_do'));

        $rows = $this->baseQuery(
            search: $search,
            aranzmanId: $aranzmanId,
            datumOd: $datumOd,
            datumDo: $datumDo,
        )->get();

        $filename = sprintf('blagajna-uplate-%s.csv', now()->format('Ymd_His'));

        return response()->streamDownload(function () use ($rows): void {
            $stream = fopen('php://output', 'wb');

            if ($stream === false) {
                return;
            }

            fputcsv($stream, ['Ko je uplatio', 'Datum', 'Aranžman', 'Iznos', 'Nacin uplate']);

            /** @var Reservation $rezervacija */
            foreach ($rows as $rezervacija) {
                $item = $this->transformReservation($rezervacija);

                fputcsv($stream, [
                    $item['uplatio'],
                    $item['datum'],
                    $item['za_sta'],
                    $item['iznos'],
                    $item['nacin_uplate_label'],
                ]);
            }

            fclose($stream);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Build base filtered query.
     */
    private function baseQuery(string $search, string $aranzmanId, string $datumOd, string $datumDo)
    {
        return Reservation::query()
            ->with([
                'arrangement:id,sifra,naziv_putovanja,destinacija,datum_polaska,datum_povratka',
                'reservationClients.client:id,ime,prezime',
                'reservationClients.package:id,cijena',
                'client:id,ime,prezime',
            ])
            ->whereHas('arrangement', function ($query) use ($aranzmanId) {
                if ($aranzmanId !== '') {
                    $query->whereKey($aranzmanId);
                }
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('ime_prezime', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($clientQuery) use ($search) {
                            $clientQuery
                                ->where('ime', 'like', "%{$search}%")
                                ->orWhere('prezime', 'like', "%{$search}%");
                        })
                        ->orWhereHas('reservationClients.client', function ($clientQuery) use ($search) {
                            $clientQuery
                                ->where('ime', 'like', "%{$search}%")
                                ->orWhere('prezime', 'like', "%{$search}%");
                        })
                        ->orWhereHas('arrangement', function ($arrangementQuery) use ($search) {
                            $arrangementQuery
                                ->where('sifra', 'like', "%{$search}%")
                                ->orWhere('naziv_putovanja', 'like', "%{$search}%")
                                ->orWhere('destinacija', 'like', "%{$search}%");
                        });
                });
            })
            ->when($datumOd !== '', fn ($query) => $query->whereDate('created_at', '>=', $datumOd))
            ->when($datumDo !== '', fn ($query) => $query->whereDate('created_at', '<=', $datumDo))
            ->latest('created_at');
    }

    /**
     * Transform reservation row for UI/export.
     *
     * @return array<string, string>
     */
    private function transformReservation(Reservation $rezervacija): array
    {
        return [
            'id' => (string) $rezervacija->id,
            'uplatio' => $this->payerLabel($rezervacija),
            'datum' => $rezervacija->created_at?->toDateString() ?? '',
            'za_sta' => trim((string) ($rezervacija->arrangement?->sifra ?? '').' - '.(string) ($rezervacija->arrangement?->naziv_putovanja ?? '')),
            'iznos' => number_format($this->reservationTotalAmount($rezervacija), 2, '.', ''),
            'nacin_uplate' => (string) $rezervacija->nacin_uplate,
            'nacin_uplate_label' => $rezervacija->nacin_uplate === 'bank' ? 'Banka' : 'Gotovina',
        ];
    }

    /**
     * Calculate reservation total amount from packages, add-ons and discounts.
     */
    private function reservationTotalAmount(Reservation $rezervacija): float
    {
        $packageTotal = 0.0;
        $addOnsTotal = 0.0;
        $discountTotal = 0.0;

        foreach ($rezervacija->reservationClients as $stavka) {
            $packageTotal += (float) ($stavka->package?->cijena ?? 0);
            $addOnsTotal += (float) ($stavka->dodatno_na_cijenu ?? 0);
            $addOnsTotal += (float) ($stavka->boravisna_taksa ?? 0);
            $addOnsTotal += (float) ($stavka->osiguranje ?? 0);
            $addOnsTotal += (float) ($stavka->doplata_jednokrevetna_soba ?? 0);
            $addOnsTotal += (float) ($stavka->doplata_dodatno_sjediste ?? 0);
            $addOnsTotal += (float) ($stavka->doplata_sjediste_po_zelji ?? 0);
            $discountTotal += (float) ($stavka->popust ?? 0);
        }

        return $packageTotal + $addOnsTotal - $discountTotal;
    }

    /**
     * Build payer display label from linked clients.
     */
    private function payerLabel(Reservation $rezervacija): string
    {
        $linkedClients = $rezervacija->reservationClients
            ->map(fn (ReservationClient $stavka) => trim("{$stavka->client?->ime} {$stavka->client?->prezime}"))
            ->filter()
            ->values();

        if ($linkedClients->isNotEmpty()) {
            return $linkedClients->implode(', ');
        }

        $singleClient = trim((string) ($rezervacija->client?->ime ?? '').' '.(string) ($rezervacija->client?->prezime ?? ''));
        if ($singleClient !== '') {
            return $singleClient;
        }

        return (string) ($rezervacija->ime_prezime ?? '-');
    }

    /**
     * Resolve selected arrangement for filter label.
     *
     * @return array<string, string|null>|null
     */
    private function selectedArrangement(string $aranzmanId): ?array
    {
        if ($aranzmanId === '') {
            return null;
        }

        $selectedAranzman = Arrangement::query()
            ->find($aranzmanId, ['id', 'sifra', 'naziv_putovanja', 'destinacija', 'datum_polaska', 'datum_povratka']);

        if (!$selectedAranzman) {
            return null;
        }

        return [
            'id' => $selectedAranzman->id,
            'sifra' => $selectedAranzman->sifra,
            'naziv_putovanja' => $selectedAranzman->naziv_putovanja,
            'destinacija' => $selectedAranzman->destinacija,
            'datum_polaska' => $selectedAranzman->datum_polaska?->toDateString(),
            'datum_povratka' => $selectedAranzman->datum_povratka?->toDateString(),
        ];
    }
}
