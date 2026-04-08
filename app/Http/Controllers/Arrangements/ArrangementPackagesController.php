<?php

namespace App\Http\Controllers\Arrangements;

use App\Http\Controllers\Controller;
use App\Http\Requests\Aranzmani\StoreAranzmanPaketRequest;
use App\Http\Requests\Aranzmani\UpdateAranzmanPaketRequest;
use App\Models\Arrangement;
use App\Models\ArrangementPackage;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ArrangementPackagesController extends Controller
{
    /**
     * Display all packages for a specific arrangement.
     */
    public function index(Arrangement $aranzman): Response
    {
        return Inertia::render('arrangements/packages/index', [
            'aranzman' => [
                'id' => $aranzman->id,
                'sifra' => $aranzman->sifra,
                'naziv_putovanja' => $aranzman->naziv_putovanja,
                'subagentski_aranzman' => (bool) $aranzman->subagentski_aranzman,
            ],
            'paketi' => $aranzman->packages()
                ->orderBy('naziv')
                ->get([
                    'id',
                    'naziv',
                    'opis',
                    'cijena',
                    'smjestaj_trosak',
                    'transport_trosak',
                    'fakultativne_stvari_trosak',
                    'ostalo_trosak',
                    'is_active',
                ]),
            'status' => session('status'),
        ]);
    }

    /**
     * Show form for creating a package for the arrangement.
     */
    public function create(Arrangement $aranzman): Response
    {
        return Inertia::render('arrangements/packages/create', [
            'aranzman' => [
                'id' => $aranzman->id,
                'sifra' => $aranzman->sifra,
                'naziv_putovanja' => $aranzman->naziv_putovanja,
                'subagentski_aranzman' => (bool) $aranzman->subagentski_aranzman,
            ],
        ]);
    }

    /**
     * Store a package in a dedicated table linked to an arrangement.
     */
    public function store(StoreAranzmanPaketRequest $request, Arrangement $aranzman): RedirectResponse
    {
        $validatedData = $request->validated();
        $financials = $this->normalizePackageFinancials(
            $validatedData,
            (bool) $aranzman->subagentski_aranzman
        );

        ArrangementPackage::create([
            'aranzman_id' => $aranzman->id,
            'naziv' => $validatedData['naziv'],
            'opis' => $validatedData['opis'] ?? null,
            'cijena' => $validatedData['cijena'],
            ...$financials,
            'is_active' => (bool) $validatedData['is_active'],
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return to_route('aranzmani.paketi.index', $aranzman)->with(
            'status',
            'Paket je uspješno dodan.'
        );
    }

    /**
     * Show form for editing a package.
     */
    public function edit(Arrangement $aranzman, ArrangementPackage $paket): Response
    {
        $this->ensurePackageBelongsToArrangement($aranzman, $paket);

        return Inertia::render('arrangements/packages/edit', [
            'aranzman' => [
                'id' => $aranzman->id,
                'sifra' => $aranzman->sifra,
                'naziv_putovanja' => $aranzman->naziv_putovanja,
                'subagentski_aranzman' => (bool) $aranzman->subagentski_aranzman,
            ],
            'paket' => $paket,
        ]);
    }

    /**
     * Update package data.
     */
    public function update(
        UpdateAranzmanPaketRequest $request,
        Arrangement $aranzman,
        ArrangementPackage $paket
    ): RedirectResponse {
        $this->ensurePackageBelongsToArrangement($aranzman, $paket);

        $validatedData = $request->validated();
        $financials = $this->normalizePackageFinancials(
            $validatedData,
            (bool) $aranzman->subagentski_aranzman
        );

        $paket->update([
            'naziv' => $validatedData['naziv'],
            'opis' => $validatedData['opis'] ?? null,
            'cijena' => $validatedData['cijena'],
            ...$financials,
            'is_active' => (bool) $validatedData['is_active'],
            'updated_by' => $request->user()?->id,
        ]);

        return to_route('aranzmani.paketi.index', $aranzman)->with(
            'status',
            'Paket je uspješno ažuriran.'
        );
    }

    /**
     * Soft delete a package.
     */
    public function destroy(Arrangement $aranzman, ArrangementPackage $paket): RedirectResponse
    {
        $this->ensurePackageBelongsToArrangement($aranzman, $paket);

        $paket->delete();

        return to_route('aranzmani.paketi.index', $aranzman)->with(
            'status',
            'Paket je obrisan.'
        );
    }

    /**
     * Ensure nested package belongs to current arrangement.
     */
    private function ensurePackageBelongsToArrangement(
        Arrangement $aranzman,
        ArrangementPackage $paket
    ): void {
        abort_unless($paket->aranzman_id === $aranzman->id, 404);
    }

    /**
     * Normalize package financial fields based on arrangement mode.
     *
     * @param  array<string, mixed>  $validatedData
     * @return array<string, float>
     */
    private function normalizePackageFinancials(array $validatedData, bool $isSubagentArrangement): array
    {
        if (! $isSubagentArrangement) {
            return [
                'smjestaj_trosak' => (float) $validatedData['smjestaj_trosak'],
                'transport_trosak' => (float) $validatedData['transport_trosak'],
                'fakultativne_stvari_trosak' => (float) $validatedData['fakultativne_stvari_trosak'],
                'ostalo_trosak' => (float) $validatedData['ostalo_trosak'],
            ];
        }

        return [
            // In subagent mode, this column stores commission percent (0-100).
            'smjestaj_trosak' => (float) ($validatedData['commission_percent'] ?? 0),
            'transport_trosak' => 0.0,
            'fakultativne_stvari_trosak' => 0.0,
            'ostalo_trosak' => 0.0,
        ];
    }
}
