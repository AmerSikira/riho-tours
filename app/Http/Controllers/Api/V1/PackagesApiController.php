<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ArrangementPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackagesApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 20), 1), 100);
        $arrangementId = trim((string) $request->string('aranzman_id'));

        $packages = ArrangementPackage::query()
            ->with('arrangement:id,sifra,naziv_putovanja')
            ->when($arrangementId !== '', fn ($query) => $query->where('aranzman_id', $arrangementId))
            ->latest('created_at')
            ->paginate($perPage);

        return response()->json($packages);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'aranzman_id' => ['required', 'exists:arrangements,id'],
            'naziv' => ['required', 'string', 'max:255'],
            'opis' => ['nullable', 'string'],
            'cijena' => ['required', 'numeric', 'min:0'],
            'smjestaj_trosak' => ['nullable', 'numeric', 'min:0'],
            'transport_trosak' => ['nullable', 'numeric', 'min:0'],
            'fakultativne_stvari_trosak' => ['nullable', 'numeric', 'min:0'],
            'ostalo_trosak' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $package = ArrangementPackage::query()->create([
            ...$validated,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return response()->json($package->load('arrangement'), 201);
    }

    public function show(ArrangementPackage $package): JsonResponse
    {
        return response()->json($package->load('arrangement'));
    }

    public function update(Request $request, ArrangementPackage $package): JsonResponse
    {
        $validated = $request->validate([
            'aranzman_id' => ['sometimes', 'exists:arrangements,id'],
            'naziv' => ['sometimes', 'string', 'max:255'],
            'opis' => ['nullable', 'string'],
            'cijena' => ['sometimes', 'numeric', 'min:0'],
            'smjestaj_trosak' => ['nullable', 'numeric', 'min:0'],
            'transport_trosak' => ['nullable', 'numeric', 'min:0'],
            'fakultativne_stvari_trosak' => ['nullable', 'numeric', 'min:0'],
            'ostalo_trosak' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $package->fill($validated);
        $package->updated_by = $request->user()?->id;
        $package->save();

        return response()->json($package->load('arrangement'));
    }

    public function destroy(ArrangementPackage $package): JsonResponse
    {
        $package->delete();

        return response()->json(status: 204);
    }
}
