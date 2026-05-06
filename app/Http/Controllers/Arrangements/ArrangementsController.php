<?php

namespace App\Http\Controllers\Arrangements;

use App\Http\Controllers\Controller;
use App\Http\Requests\Aranzmani\StoreAranzmanRequest;
use App\Http\Requests\Aranzmani\UpdateAranzmanRequest;
use App\Models\Arrangement;
use App\Models\ArrangementImage;
use App\Models\ArrangementPackage;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ArrangementsController extends Controller
{
    /**
     * Display the arrangements table with optional search.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('pretraga'));
        $datumOd = trim((string) $request->string('datum_od'));
        $datumDo = trim((string) $request->string('datum_do'));

        $aranzmani = Arrangement::query()
            ->withSum(
                [
                    'reservations as broj_prijavljenih' => function ($query) {
                        $query->where('status', '!=', 'otkazana');
                    },
                ],
                'broj_putnika'
            )
            ->when($search !== '', function ($query) use ($search) {
                $query->where('naziv_putovanja', 'like', "%{$search}%");
            })
            ->when($datumOd !== '', function ($query) use ($datumOd) {
                $query->whereDate('datum_polaska', '>=', $datumOd);
            })
            ->when($datumDo !== '', function ($query) use ($datumDo) {
                $query->whereDate('datum_povratka', '<=', $datumDo);
            })
            ->orderBy('datum_polaska')
            ->paginate(15, [
                'id',
                'sifra',
                'naziv_putovanja',
                'destinacija',
                'datum_polaska',
                'datum_povratka',
                'tip_prevoza',
                'tip_smjestaja',
                'is_active',
            ])
            ->withQueryString();

        return Inertia::render('arrangements/index', [
            'aranzmani' => $aranzmani,
            'filters' => [
                'pretraga' => $search,
                'datum_od' => $datumOd,
                'datum_do' => $datumDo,
            ],
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Show the create arrangement page.
     */
    public function create(): Response
    {
        return Inertia::render('arrangements/create', [
            'paketNazivSuggestions' => ArrangementPackage::query()
                ->whereNotNull('naziv')
                ->distinct()
                ->orderBy('naziv')
                ->pluck('naziv')
                ->values()
                ->all(),
            'dobavljacOptions' => Supplier::query()
                ->orderBy('company_name')
                ->get(['id', 'company_name', 'company_id'])
                ->map(fn (Supplier $supplier): array => [
                    'id' => $supplier->id,
                    'company_name' => $supplier->company_name,
                    'company_id' => $supplier->company_id,
                ])
                ->values()
                ->all(),
        ]);
    }

    /**
     * Store a newly created arrangement.
     */
    public function store(StoreAranzmanRequest $request): RedirectResponse
    {
        $validatedData = $request->validated();
        $packageRows = $validatedData['paketi'] ?? [];
        $imageFiles = $request->file('slike', []);
        $mainImageSelection = (string) ($validatedData['main_image_selection'] ?? '');

        DB::transaction(function () use (
            $validatedData,
            $packageRows,
            $request,
            $imageFiles,
            $mainImageSelection
        ): void {
            // Persist arrangement together with audit trail columns.
            $isSubagentArrangement = (bool) ($validatedData['subagentski_aranzman'] ?? false);
            $isActive = array_key_exists('is_active', $validatedData)
                ? (bool) $validatedData['is_active']
                : true;

            $aranzman = Arrangement::create([
                ...collect($validatedData)->except(['paketi', 'main_image_selection', 'slike'])->all(),
                'opis_putovanja' => (string) ($validatedData['opis_putovanja'] ?? ''),
                'tip_prevoza' => (string) ($validatedData['tip_prevoza'] ?? ''),
                'tip_smjestaja' => (string) ($validatedData['tip_smjestaja'] ?? ''),
                'trajanje_dana' => $this->calculateDurationDays(
                    (string) $validatedData['datum_polaska'],
                    (string) $validatedData['datum_povratka']
                ),
                'is_active' => $isActive,
                'subagentski_aranzman' => $isSubagentArrangement,
                'supplier_id' => $isSubagentArrangement ? ($validatedData['supplier_id'] ?? null) : null,
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]);

            $this->syncPackages(
                $aranzman,
                $packageRows,
                $request->user()?->id,
                $isSubagentArrangement
            );
            $this->syncImagesOnCreate(
                $aranzman,
                $imageFiles,
                $mainImageSelection,
                $request->user()?->id
            );
        });

        return to_route('aranzmani.index')->with('status', 'Aranžman je uspješno dodan.');
    }

    /**
     * Show the edit arrangement page.
     */
    public function edit(Arrangement $aranzman): Response
    {
        $activeReservations = $aranzman->reservations()
            ->where('status', '!=', 'otkazana')
            ->with([
                'reservationClients:id,rezervacija_id,paket_id,dodatno_na_cijenu,popust,boravisna_taksa,osiguranje,doplata_jednokrevetna_soba,doplata_dodatno_sjediste,doplata_sjediste_po_zelji',
                'reservationClients.package:id,cijena,smjestaj_trosak,transport_trosak,fakultativne_stvari_trosak,ostalo_trosak',
            ])
            ->get(['id', 'aranzman_id']);
        $reservationCount = $activeReservations->count();

        $peopleBought = 0;
        $moneyEarned = 0.0;
        $moneyToSpend = 0.0;

        foreach ($activeReservations as $rezervacija) {
            foreach ($rezervacija->reservationClients as $stavka) {
                $peopleBought++;

                $packagePrice = (float) ($stavka->package?->cijena ?? 0);
                $extraCharge = (float) ($stavka->dodatno_na_cijenu ?? 0);
                $boravisnaTaksa = (float) ($stavka->boravisna_taksa ?? 0);
                $osiguranje = (float) ($stavka->osiguranje ?? 0);
                $doplataJednokrevetnaSoba = (float) ($stavka->doplata_jednokrevetna_soba ?? 0);
                $doplataDodatnoSjediste = (float) ($stavka->doplata_dodatno_sjediste ?? 0);
                $doplataSjedistePoZelji = (float) ($stavka->doplata_sjediste_po_zelji ?? 0);
                $discount = (float) ($stavka->popust ?? 0);
                $moneyEarned += max($packagePrice + $extraCharge + $boravisnaTaksa + $osiguranje + $doplataJednokrevetnaSoba + $doplataDodatnoSjediste + $doplataSjedistePoZelji - $discount, 0);

                if ((bool) $aranzman->subagentski_aranzman) {
                    $commissionPercent = (float) ($stavka->package?->smjestaj_trosak ?? 0);
                    $commissionAmount = max(($packagePrice * $commissionPercent) / 100, 0);
                    $moneyToSpend += max($packagePrice - $commissionAmount, 0);
                } else {
                    $moneyToSpend += max(
                        (float) ($stavka->package?->smjestaj_trosak ?? 0) +
                        (float) ($stavka->package?->transport_trosak ?? 0) +
                        (float) ($stavka->package?->fakultativne_stvari_trosak ?? 0) +
                        (float) ($stavka->package?->ostalo_trosak ?? 0),
                        0
                    );
                }
            }
        }

        return Inertia::render('arrangements/edit', [
            'aranzman' => [
                ...$aranzman->toArray(),
                'datum_polaska' => $aranzman->datum_polaska?->toDateString(),
                'datum_povratka' => $aranzman->datum_povratka?->toDateString(),
                'supplier_id' => $aranzman->supplier_id,
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
                'slike' => $aranzman->images()
                    ->orderByDesc('is_primary')
                    ->orderBy('id')
                    ->get()
                    ->map(fn (ArrangementImage $slika): array => [
                        'id' => $slika->id,
                        'is_primary' => $slika->is_primary,
                        'url' => Storage::disk('public')->url($slika->putanja),
                    ]),
            ],
            'statistika' => [
                'broj_rezervacija' => $reservationCount,
                'broj_putnika' => $peopleBought,
                'ukupni_prihod' => round($moneyEarned, 2),
                'ukupni_trosak' => round($moneyToSpend, 2),
                'potencijalna_zarada' => round($moneyEarned - $moneyToSpend, 2),
            ],
            'paketNazivSuggestions' => ArrangementPackage::query()
                ->whereNotNull('naziv')
                ->distinct()
                ->orderBy('naziv')
                ->pluck('naziv')
                ->values()
                ->all(),
            'dobavljacOptions' => Supplier::query()
                ->orderBy('company_name')
                ->get(['id', 'company_name', 'company_id'])
                ->map(fn (Supplier $supplier): array => [
                    'id' => $supplier->id,
                    'company_name' => $supplier->company_name,
                    'company_id' => $supplier->company_id,
                ])
                ->values()
                ->all(),
        ]);
    }

    /**
     * Update the selected arrangement.
     */
    public function update(UpdateAranzmanRequest $request, Arrangement $aranzman): RedirectResponse
    {
        $validatedData = $request->validated();
        $packageRows = $validatedData['paketi'] ?? [];
        $keepImageIds = collect($validatedData['zadrzane_slike'] ?? [])
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();
        $newImageFiles = $request->file('nove_slike', []);
        $mainImageSelection = (string) ($validatedData['main_image_selection'] ?? '');

        DB::transaction(function () use (
            $validatedData,
            $packageRows,
            $request,
            $aranzman,
            $keepImageIds,
            $newImageFiles,
            $mainImageSelection
        ): void {
            $isSubagentArrangement = array_key_exists('subagentski_aranzman', $validatedData)
                ? (bool) $validatedData['subagentski_aranzman']
                : (bool) $aranzman->subagentski_aranzman;
            $isActive = array_key_exists('is_active', $validatedData)
                ? (bool) $validatedData['is_active']
                : (bool) $aranzman->is_active;

            $aranzman->update([
                ...collect($validatedData)
                    ->except(['paketi', 'zadrzane_slike', 'nove_slike', 'main_image_selection'])
                    ->all(),
                'opis_putovanja' => (string) ($validatedData['opis_putovanja'] ?? ''),
                'tip_prevoza' => (string) ($validatedData['tip_prevoza'] ?? ''),
                'tip_smjestaja' => (string) ($validatedData['tip_smjestaja'] ?? ''),
                'trajanje_dana' => $this->calculateDurationDays(
                    (string) $validatedData['datum_polaska'],
                    (string) $validatedData['datum_povratka']
                ),
                'is_active' => $isActive,
                'subagentski_aranzman' => $isSubagentArrangement,
                'supplier_id' => $isSubagentArrangement ? ($validatedData['supplier_id'] ?? null) : null,
                'updated_by' => $request->user()?->id,
            ]);

            $this->syncPackages(
                $aranzman,
                $packageRows,
                $request->user()?->id,
                $isSubagentArrangement
            );
            $this->syncImagesOnUpdate(
                $aranzman,
                $keepImageIds,
                $newImageFiles,
                $mainImageSelection,
                $request->user()?->id
            );
        });

        return to_route('aranzmani.index')->with('status', 'Aranžman je uspješno ažuriran.');
    }

    /**
     * Soft delete the selected arrangement.
     */
    public function destroy(Arrangement $aranzman): RedirectResponse
    {
        $aranzman->delete();

        return to_route('aranzmani.index')->with('status', 'Aranžman je obrisan.');
    }

    /**
     * Synchronize arrangement packages from add/edit form payload.
     *
     * @param  array<int, array<string, mixed>>  $packageRows
     */
    private function syncPackages(
        Arrangement $aranzman,
        array $packageRows,
        string|int|null $userId,
        bool $isSubagentArrangement
    ): void
    {
        $incomingRows = collect($packageRows);
        $incomingIds = $incomingRows
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->values();

        // Remove packages not present anymore in edit payload.
        if ($aranzman->exists) {
            $aranzman->packages()
                ->when($incomingIds->isNotEmpty(), function ($query) use ($incomingIds) {
                    $query->whereNotIn('id', $incomingIds->all());
                })
                ->when($incomingIds->isEmpty(), function ($query) {
                    $query->whereRaw('1 = 1');
                })
                ->delete();
        }

        foreach ($incomingRows as $row) {
            $rowId = isset($row['id']) && $row['id'] !== null ? (string) $row['id'] : null;

            if ($rowId) {
                $paket = $aranzman->packages()->whereKey($rowId)->first();

                if (! $paket) {
                    continue;
                }

                $normalizedFinancials = $this->normalizePackageFinancials(
                    $row,
                    $isSubagentArrangement
                );

                $paket->update([
                    'naziv' => $row['naziv'],
                    'opis' => $row['opis'] ?? null,
                    'cijena' => $row['cijena'],
                    ...$normalizedFinancials,
                    'is_active' => (bool) ($row['is_active'] ?? true),
                    'updated_by' => $userId,
                ]);

                continue;
            }

            $normalizedFinancials = $this->normalizePackageFinancials(
                $row,
                $isSubagentArrangement
            );

            $aranzman->packages()->create([
                'naziv' => $row['naziv'],
                'opis' => $row['opis'] ?? null,
                'cijena' => $row['cijena'],
                ...$normalizedFinancials,
                'is_active' => (bool) ($row['is_active'] ?? true),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }
    }

    /**
     * Normalize package financial fields based on arrangement mode.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, float>
     */
    private function normalizePackageFinancials(array $row, bool $isSubagentArrangement): array
    {
        if (! $isSubagentArrangement) {
            return [
                'smjestaj_trosak' => (float) ($row['smjestaj_trosak'] ?? 0),
                'transport_trosak' => (float) ($row['transport_trosak'] ?? 0),
                'fakultativne_stvari_trosak' => (float) ($row['fakultativne_stvari_trosak'] ?? 0),
                'ostalo_trosak' => (float) ($row['ostalo_trosak'] ?? 0),
            ];
        }

        return [
            // In subagent mode, this column stores commission percent (0-100).
            'smjestaj_trosak' => (float) ($row['commission_percent'] ?? 0),
            'transport_trosak' => 0.0,
            'fakultativne_stvari_trosak' => 0.0,
            'ostalo_trosak' => 0.0,
        ];
    }

    /**
     * @param  array<int, UploadedFile>  $imageFiles
     */
    private function syncImagesOnCreate(
        Arrangement $aranzman,
        array $imageFiles,
        string $mainImageSelection,
        string|int|null $userId
    ): void {
        if (count($imageFiles) === 0) {
            return;
        }

        $mainIndex = $this->extractMainNewIndex($mainImageSelection);

        if (! isset($imageFiles[$mainIndex])) {
            throw ValidationException::withMessages([
                'main_image_selection' => 'Odabrana glavna slika nije validna.',
            ]);
        }

        foreach ($imageFiles as $index => $imageFile) {
            $path = $imageFile->store("aranzmani/{$aranzman->id}", 'public');

            $aranzman->images()->create([
                'putanja' => $path,
                'is_primary' => $index === $mainIndex,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }
    }

    /**
     * @param  array<int>  $keepImageIds
     * @param  array<int, UploadedFile>  $newImageFiles
     */
    private function syncImagesOnUpdate(
        Arrangement $aranzman,
        array $keepImageIds,
        array $newImageFiles,
        string $mainImageSelection,
        string|int|null $userId
    ): void {
        $existingImages = $aranzman->images()->get(['id', 'putanja']);
        $existingIds = $existingImages->pluck('id')->map(fn ($id) => (string) $id);
        $allowedKeepIds = collect($keepImageIds)
            ->filter(fn ($id) => $existingIds->contains($id))
            ->values();

        $mainExistingId = null;
        $mainNewIndex = null;
        $selection = trim($mainImageSelection);
        if ($selection === '') {
            $mainExistingId = null;
            $mainNewIndex = null;
        } elseif (str_starts_with($selection, 'existing:')) {
            $mainExistingId = (string) str_replace('existing:', '', $selection);

            if (! $allowedKeepIds->contains($mainExistingId)) {
                throw ValidationException::withMessages([
                    'main_image_selection' => 'Glavna slika mora biti među zadržanim slikama.',
                ]);
            }
        } elseif (str_starts_with($selection, 'new:')) {
            $mainNewIndex = $this->extractMainNewIndex($selection);

            if (! isset($newImageFiles[$mainNewIndex])) {
                throw ValidationException::withMessages([
                    'main_image_selection' => 'Odabrana glavna nova slika nije validna.',
                ]);
            }
        }

        $imagesForDelete = $existingImages
            ->filter(fn (ArrangementImage $slika) => ! $allowedKeepIds->contains((string) $slika->id))
            ->values();

        foreach ($imagesForDelete as $imageForDelete) {
            Storage::disk('public')->delete($imageForDelete->putanja);
            $aranzman->images()->whereKey($imageForDelete->id)->delete();
        }

        if ($allowedKeepIds->isNotEmpty()) {
            $aranzman->images()
                ->whereIn('id', $allowedKeepIds->all())
                ->update([
                    'is_primary' => false,
                    'updated_by' => $userId,
                ]);
        }

        if ($mainExistingId) {
            $aranzman->images()->whereKey($mainExistingId)->update([
                'is_primary' => true,
                'updated_by' => $userId,
            ]);
        }

        foreach ($newImageFiles as $index => $newImageFile) {
            $path = $newImageFile->store("aranzmani/{$aranzman->id}", 'public');

            $aranzman->images()->create([
                'putanja' => $path,
                'is_primary' => $mainNewIndex !== null && $index === $mainNewIndex,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }

        $hasPrimary = $aranzman->images()
            ->where('is_primary', true)
            ->exists();

        if (! $hasPrimary) {
            $fallbackImage = $aranzman->images()->orderBy('id')->first();

            if ($fallbackImage) {
                $fallbackImage->update([
                    'is_primary' => true,
                    'updated_by' => $userId,
                ]);
            }
        }
    }

    private function extractMainNewIndex(string $selection): int
    {
        return (int) str_replace('new:', '', $selection);
    }

    private function calculateDurationDays(string $datumPolaska, string $datumPovratka): int
    {
        return Carbon::parse($datumPolaska)->diffInDays(Carbon::parse($datumPovratka)) + 1;
    }
}
