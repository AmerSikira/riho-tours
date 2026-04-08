<?php

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Uloge\StoreUlogaRequest;
use App\Http\Requests\Uloge\UpdateUlogaRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RolesController extends Controller
{
    /**
     * Display the roles table with optional search.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('pretraga'));

        $roles = Role::query()
            ->with(['permissions:id,name'])
            ->withCount('users')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get(['id', 'name', 'guard_name']);

        return Inertia::render('roles/index', [
            'roles' => $roles->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'users_count' => $role->users_count,
                'permissions' => $role->permissions->pluck('name')->values()->all(),
            ]),
            'filters' => [
                'pretraga' => $search,
            ],
            'status' => $request->session()->get('status'),
            'error' => $request->session()->get('error'),
        ]);
    }

    /**
     * Show the create role page.
     */
    public function create(): Response
    {
        return Inertia::render('roles/create', [
            'permissions' => $this->permissionOptions(),
        ]);
    }

    /**
     * Store a new role and assign selected permissions.
     */
    public function store(StoreUlogaRequest $request): RedirectResponse
    {
        $validatedData = $request->validated();

        $role = Role::create([
            'name' => $validatedData['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($validatedData['permissions'] ?? []);

        return to_route('uloge.index')->with('status', 'Uloga je uspješno dodana.');
    }

    /**
     * Show the edit role page.
     */
    public function edit(Role $uloga): Response
    {
        return Inertia::render('roles/edit', [
            'role' => [
                'id' => $uloga->id,
                'name' => $uloga->name,
                'permissions' => $uloga->permissions()->pluck('name')->values()->all(),
            ],
            'permissions' => $this->permissionOptions(),
        ]);
    }

    /**
     * Update an existing role and synchronize permissions.
     */
    public function update(UpdateUlogaRequest $request, Role $uloga): RedirectResponse
    {
        $validatedData = $request->validated();

        $uloga->name = $validatedData['name'];
        $uloga->save();

        $uloga->syncPermissions($validatedData['permissions'] ?? []);

        return to_route('uloge.index')->with('status', 'Uloga je uspješno ažurirana.');
    }

    /**
     * Delete the selected role when business constraints allow it.
     */
    public function destroy(Role $uloga): RedirectResponse
    {
        try {
            $uloga->delete();
        } catch (ValidationException $exception) {
            return to_route('uloge.index')->with(
                'error',
                'Rola se ne može obrisati dok ima korisnike.'
            );
        }

        return to_route('uloge.index')->with('status', 'Uloga je obrisana.');
    }

    /**
     * Return all available permission names for role assignment checkboxes.
     *
     * @return array<int, string>
     */
    private function permissionOptions(): array
    {
        return Permission::query()
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();
    }
}
