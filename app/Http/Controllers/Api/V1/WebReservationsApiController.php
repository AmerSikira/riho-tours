<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WebReservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebReservationsApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 20), 1), 100);

        $rows = WebReservation::query()
            ->with([
                'arrangement:id,sifra,naziv_putovanja,destinacija',
                'package:id,naziv,cijena',
                'convertedReservation:id,order_num',
            ])
            ->latest('created_at')
            ->paginate($perPage);

        return response()->json($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'aranzman_id' => ['nullable', 'exists:arrangements,id'],
            'paket_id' => ['nullable', 'exists:arrangement_packages,id'],
            'ime' => ['nullable', 'string', 'max:255'],
            'prezime' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'broj_telefona' => ['nullable', 'string', 'max:50'],
            'adresa' => ['nullable', 'string', 'max:255'],
            'broj_putnika' => ['nullable', 'integer', 'min:1', 'max:100'],
            'napomena' => ['nullable', 'string'],
            'source_domain' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'string', 'max:500'],
            'landing_page_url' => ['nullable', 'string', 'max:500'],
            'referrer_url' => ['nullable', 'string', 'max:500'],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_medium' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'utm_term' => ['nullable', 'string', 'max:255'],
            'utm_content' => ['nullable', 'string', 'max:255'],
            'payload' => ['nullable', 'array'],
        ]);

        $sourceDomain = $validated['source_domain'] ?? null;
        if (! $sourceDomain && ! empty($validated['source_url'])) {
            $sourceDomain = parse_url((string) $validated['source_url'], PHP_URL_HOST) ?: null;
        }
        if (! $sourceDomain) {
            $sourceDomain = $request->headers->get('origin') ?: $request->headers->get('referer');
        }
        if ($sourceDomain && str_contains($sourceDomain, '://')) {
            $sourceDomain = parse_url($sourceDomain, PHP_URL_HOST) ?: $sourceDomain;
        }

        $row = WebReservation::query()->create([
            ...$validated,
            'source_domain' => $sourceDomain,
            'broj_putnika' => max(1, (int) ($validated['broj_putnika'] ?? 1)),
            'status' => 'novo',
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return response()->json($row->load(['arrangement', 'package']), 201);
    }

    public function show(WebReservation $webReservation): JsonResponse
    {
        return response()->json($webReservation->load([
            'arrangement',
            'package',
            'convertedReservation:id,order_num',
        ]));
    }
}
