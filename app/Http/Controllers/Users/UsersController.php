<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Korisnici\StoreKorisnikRequest;
use App\Http\Requests\Korisnici\UpdateKorisnikRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class UsersController extends Controller
{
    /**
     * Display the users table with optional search by name.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('pretraga'));

        $users = User::query()
            ->with('roles:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(15, ['id', 'name', 'email', 'is_active', 'created_at'])
            ->withQueryString();

        $users->setCollection(
            $users->getCollection()->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'role' => $user->roles->pluck('name')->first(),
                'created_at' => $user->created_at?->toDateTimeString(),
            ])
        );

        return Inertia::render('users/index', [
            'users' => $users,
            'filters' => [
                'pretraga' => $search,
            ],
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Show the create user form.
     */
    public function create(): Response
    {
        return Inertia::render('users/create', [
            'roles' => Role::query()
                ->whereIn('name', ['admin', 'agent'])
                ->orderBy('name')
                ->pluck('name')
                ->values()
                ->all(),
        ]);
    }

    /**
     * Store a newly created user and assign the selected role.
     */
    public function store(StoreKorisnikRequest $request): RedirectResponse
    {
        $validatedData = $request->validated();

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'email_verified_at' => now(),
            'is_active' => (bool) $validatedData['is_active'],
        ]);

        $user->assignRole($validatedData['role']);

        return to_route('korisnici.index')->with(
            'status',
            'Korisnik je uspješno dodan.'
        );
    }

    /**
     * Show the user profile page with edit form.
     */
    public function show(User $korisnik): Response
    {
        return Inertia::render('users/edit', [
            'user' => [
                'id' => $korisnik->id,
                'name' => $korisnik->name,
                'email' => $korisnik->email,
                'is_active' => $korisnik->is_active,
                'role' => $korisnik->roles()->pluck('name')->first(),
                'created_at' => $korisnik->created_at?->toDateTimeString(),
                'email_verified_at' => $korisnik->email_verified_at?->toDateTimeString(),
                'potpis_url' => $korisnik->potpis_path
                    ? Storage::disk('public')->url($korisnik->potpis_path)
                    : null,
                'pecat_url' => $korisnik->pecat_path
                    ? Storage::disk('public')->url($korisnik->pecat_path)
                    : null,
            ],
            'roles' => Role::query()
                ->whereIn('name', ['admin', 'agent'])
                ->orderBy('name')
                ->pluck('name')
                ->values()
                ->all(),
        ]);
    }

    /**
     * Backward-compatible edit endpoint.
     */
    public function edit(User $korisnik): Response
    {
        return $this->show($korisnik);
    }

    /**
     * Update the selected user and synchronize role assignment.
     */
    public function update(UpdateKorisnikRequest $request, User $korisnik): RedirectResponse
    {
        $validatedData = $request->validated();

        $korisnik->fill([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'is_active' => (bool) $validatedData['is_active'],
        ]);

        // Keep the existing password when no new password is submitted.
        if (! empty($validatedData['password'])) {
            $korisnik->password = Hash::make($validatedData['password']);
        }

        $potpisPath = $korisnik->potpis_path;
        if ($request->hasFile('potpis')) {
            if ($potpisPath) {
                Storage::disk('public')->delete($potpisPath);
            }

            $potpisPath = $request->file('potpis')?->store('users/signatures', 'public');
        }

        $pecatPath = $korisnik->pecat_path;
        if ($request->hasFile('pecat')) {
            if ($pecatPath) {
                Storage::disk('public')->delete($pecatPath);
            }

            $pecatPath = $request->file('pecat')?->store('users/stamps', 'public');
        }

        $korisnik->potpis_path = $potpisPath;
        $korisnik->pecat_path = $pecatPath;

        $korisnik->save();
        $korisnik->syncRoles([$validatedData['role']]);

        return to_route('korisnici.index')->with(
            'status',
            'Korisnik je uspješno ažuriran.'
        );
    }

    /**
     * Toggle the active status for the selected user.
     */
    public function toggleStatus(User $korisnik): RedirectResponse
    {
        $korisnik->is_active = ! $korisnik->is_active;
        $korisnik->save();

        return to_route('korisnici.index')->with(
            'status',
            $korisnik->is_active
                ? 'Korisnik je aktiviran.'
                : 'Korisnik je deaktiviran.'
        );
    }

    /**
     * Delete the selected user account.
     */
    public function destroy(User $korisnik): RedirectResponse
    {
        $korisnik->delete();

        return to_route('korisnici.index')->with(
            'status',
            'Korisnik je obrisan.'
        );
    }
}
