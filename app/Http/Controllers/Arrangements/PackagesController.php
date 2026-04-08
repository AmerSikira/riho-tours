<?php

namespace App\Http\Controllers\Arrangements;

use App\Http\Controllers\Controller;
use App\Models\ArrangementPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PackagesController extends Controller
{
    /**
     * Display global package list grouped by package name.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('pretraga'));

        $paketi = ArrangementPackage::query()
            ->whereHas('arrangement')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('naziv', 'like', "%{$search}%");
            })
            ->select([
                DB::raw('MIN(id) as id'),
                'naziv',
                DB::raw('COUNT(DISTINCT aranzman_id) as broj_aranzmana'),
                DB::raw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as broj_aktivnih'),
            ])
            ->groupBy('naziv')
            ->orderBy('naziv')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('packages/index', [
            'paketi' => $paketi,
            'filters' => [
                'pretraga' => $search,
            ],
        ]);
    }

    /**
     * Show arrangements linked to selected package (same package name).
     */
    public function show(ArrangementPackage $paket): Response
    {
        $vezaniPaketi = ArrangementPackage::query()
            ->where('naziv', $paket->naziv)
            ->whereHas('arrangement')
            ->with([
                'arrangement:id,sifra,naziv_putovanja,destinacija,datum_polaska,datum_povratka,is_active',
            ])
            ->orderBy('aranzman_id')
            ->get(['id', 'aranzman_id', 'is_active', 'updated_at'])
            ->map(function (ArrangementPackage $stavka): array {
                $aranzman = $stavka->arrangement;

                return [
                    'id' => $stavka->id,
                    'is_active' => $stavka->is_active,
                    'updated_at' => $stavka->updated_at?->toDateTimeString(),
                    'aranzman' => [
                        'id' => $aranzman->id,
                        'sifra' => $aranzman->sifra,
                        'naziv_putovanja' => $aranzman->naziv_putovanja,
                        'destinacija' => $aranzman->destinacija,
                        'datum_polaska' => $aranzman->datum_polaska?->toDateString(),
                        'datum_povratka' => $aranzman->datum_povratka?->toDateString(),
                        'is_active' => (bool) $aranzman->is_active,
                    ],
                ];
            })
            ->values();

        return Inertia::render('packages/show', [
            'paket' => [
                'id' => $paket->id,
                'naziv' => $paket->naziv,
            ],
            'aranzmani' => $vezaniPaketi,
        ]);
    }
}
