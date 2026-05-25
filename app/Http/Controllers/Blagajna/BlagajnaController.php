<?php

namespace App\Http\Controllers\Blagajna;

use App\Exports\CashDeskPaymentLedgerExport;
use App\Http\Controllers\Controller;
use App\Models\Arrangement;
use App\Services\CashDesk\PaymentLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BlagajnaController extends Controller
{
    /**
     * Display payment overview with filters.
     */
    public function index(Request $request, PaymentLedgerService $ledger): Response
    {
        $search = trim((string) $request->string('pretraga'));
        $arrangementId = trim((string) $request->string('aranzman_id'));
        $dateFrom = trim((string) $request->string('datum_od'));
        $dateTo = trim((string) $request->string('datum_do'));

        $paymentEntries = $ledger->paginate(
            search: $search,
            arrangementId: $arrangementId,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            perPage: 15,
            page: max((int) $request->integer('page', 1), 1),
            path: $request->url(),
            query: $request->query(),
        );

        return Inertia::render('blagajna/index', [
            'payment_entries' => $paymentEntries,
            'filters' => [
                'pretraga' => $search,
                'aranzman_id' => $arrangementId,
                'datum_od' => $dateFrom,
                'datum_do' => $dateTo,
            ],
            'selected_aranzman' => $this->selectedArrangement($arrangementId),
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
     * Export currently filtered payment ledger rows to Excel.
     */
    public function export(Request $request, PaymentLedgerService $ledger): BinaryFileResponse
    {
        $search = trim((string) $request->string('pretraga'));
        $arrangementId = trim((string) $request->string('aranzman_id'));
        $dateFrom = trim((string) $request->string('datum_od'));
        $dateTo = trim((string) $request->string('datum_do'));

        $rows = $ledger->rows(
            search: $search,
            arrangementId: $arrangementId,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
        );

        $filename = sprintf('blagajna-uplate-%s.xlsx', now()->format('Ymd_His'));

        return Excel::download(new CashDeskPaymentLedgerExport($rows), $filename);
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

        if (! $selectedAranzman) {
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
