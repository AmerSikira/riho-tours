<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Arrangement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ArrangementsApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 20), 1), 100);
        $search = trim((string) $request->string('search'));

        $arrangements = Arrangement::query()
            ->with([
                'packages:id,aranzman_id,naziv,cijena,is_active',
                'images:id,aranzman_id,putanja,is_primary',
                'webReservations:id,aranzman_id,paket_id,ime,prezime,email,broj_telefona,broj_putnika,status,source_domain,source_url,landing_page_url,referrer_url,utm_source,utm_medium,utm_campaign,utm_term,utm_content,payload,converted_at,created_at',
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('sifra', 'like', "%{$search}%")
                        ->orWhere('naziv_putovanja', 'like', "%{$search}%")
                        ->orWhere('destinacija', 'like', "%{$search}%");
                });
            })
            ->latest('created_at')
            ->paginate($perPage);

        $arrangements->setCollection(
            $arrangements->getCollection()->map(function (Arrangement $arrangement): array {
                $primaryImage = $arrangement->images
                    ->firstWhere('is_primary', true) ?? $arrangement->images->first();

                return [
                    ...$arrangement->toArray(),
                    'image' => $primaryImage?->putanja,
                    'image_url' => $primaryImage?->putanja
                        ? Storage::disk('public')->url($primaryImage->putanja)
                        : null,
                ];
            })
        );

        return response()->json($arrangements);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sifra' => ['required', 'string', 'max:255', 'unique:arrangements,sifra'],
            'destinacija' => ['required', 'string', 'max:255'],
            'naziv_putovanja' => ['required', 'string', 'max:255'],
            'opis_putovanja' => ['nullable', 'string'],
            'plan_putovanja' => ['nullable', 'string'],
            'datum_polaska' => ['required', 'date'],
            'datum_povratka' => ['required', 'date', 'after_or_equal:datum_polaska'],
            'trajanje_dana' => ['nullable', 'integer', 'min:1'],
            'tip_prevoza' => ['nullable', 'string', 'max:255'],
            'tip_smjestaja' => ['nullable', 'string', 'max:255'],
            'napomena' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'subagentski_aranzman' => ['nullable', 'boolean'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
        ]);

        $arrangement = Arrangement::query()->create([
            ...$validated,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'subagentski_aranzman' => (bool) ($validated['subagentski_aranzman'] ?? false),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return response()->json($arrangement->load('packages'), 201);
    }

    public function show(Arrangement $arrangement): JsonResponse
    {
        $arrangement->load([
            'packages',
            'images:id,aranzman_id,putanja,is_primary',
            'webReservations:id,aranzman_id,paket_id,ime,prezime,email,broj_telefona,broj_putnika,status,source_domain,source_url,landing_page_url,referrer_url,utm_source,utm_medium,utm_campaign,utm_term,utm_content,payload,converted_at,created_at',
        ]);

        $primaryImage = $arrangement->images
            ->firstWhere('is_primary', true) ?? $arrangement->images->first();

        return response()->json([
            ...$arrangement->toArray(),
            'image' => $primaryImage?->putanja,
            'image_url' => $primaryImage?->putanja
                ? Storage::disk('public')->url($primaryImage->putanja)
                : null,
        ]);
    }

    public function update(Request $request, Arrangement $arrangement): JsonResponse
    {
        $validated = $request->validate([
            'sifra' => ['sometimes', 'string', 'max:255', 'unique:arrangements,sifra,'.$arrangement->id],
            'destinacija' => ['sometimes', 'string', 'max:255'],
            'naziv_putovanja' => ['sometimes', 'string', 'max:255'],
            'opis_putovanja' => ['nullable', 'string'],
            'plan_putovanja' => ['nullable', 'string'],
            'datum_polaska' => ['sometimes', 'date'],
            'datum_povratka' => ['sometimes', 'date'],
            'trajanje_dana' => ['nullable', 'integer', 'min:1'],
            'tip_prevoza' => ['nullable', 'string', 'max:255'],
            'tip_smjestaja' => ['nullable', 'string', 'max:255'],
            'napomena' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'subagentski_aranzman' => ['sometimes', 'boolean'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
        ]);

        $arrangement->fill($validated);
        $arrangement->updated_by = $request->user()?->id;
        $arrangement->save();

        return response()->json($arrangement->load('packages'));
    }

    public function destroy(Arrangement $arrangement): JsonResponse
    {
        $arrangement->delete();

        return response()->json(status: 204);
    }
}
