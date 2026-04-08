<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientsApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 20), 1), 100);
        $search = trim((string) $request->string('search'));

        $clients = Client::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('ime', 'like', "%{$search}%")
                        ->orWhere('prezime', 'like', "%{$search}%")
                        ->orWhere('broj_dokumenta', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('broj_telefona', 'like', "%{$search}%");
                });
            })
            ->latest('created_at')
            ->paginate($perPage);

        return response()->json($clients);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ime' => ['required', 'string', 'max:255'],
            'prezime' => ['required', 'string', 'max:255'],
            'broj_dokumenta' => ['nullable', 'string', 'max:255'],
            'datum_rodjenja' => ['nullable', 'date'],
            'adresa' => ['required', 'string', 'max:255'],
            'broj_telefona' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $client = Client::query()->create([
            ...$validated,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return response()->json($client, 201);
    }

    public function show(Client $client): JsonResponse
    {
        return response()->json($client);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'ime' => ['sometimes', 'string', 'max:255'],
            'prezime' => ['sometimes', 'string', 'max:255'],
            'broj_dokumenta' => ['nullable', 'string', 'max:255'],
            'datum_rodjenja' => ['nullable', 'date'],
            'adresa' => ['sometimes', 'string', 'max:255'],
            'broj_telefona' => ['sometimes', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $client->fill($validated);
        $client->updated_by = $request->user()?->id;
        $client->save();

        return response()->json($client);
    }

    public function destroy(Client $client): JsonResponse
    {
        $client->delete();

        return response()->json(status: 204);
    }
}
