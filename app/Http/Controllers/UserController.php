<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('users/index', [
            'users' => User::query()->with('roles:id,name')->latest()->get(['id', 'name', 'username', 'avatar', 'jenis_kelamin', 'created_at']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('users/form', [
            'user' => null,
            'roles' => Role::query()->whereNotIn('name', ['super_admin', 'superadmin', 'Super Admin'])->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'jenis_kelamin' => ['nullable', 'in:Laki-laki,Perempuan'],
            'avatar' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'exists:roles,name', Rule::notIn(['super_admin', 'superadmin', 'Super Admin'])],
        ]);

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('users', 'public');
        }

        $userData = collect($validated)->except('role')->all();
        $user = User::create($userData);
        $user->syncRoles([$validated['role']]);

        return to_route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function show(User $user): Response
    {
        return Inertia::render('users/show', [
            'user' => $user->load('roles:id,name'),
        ]);
    }

    public function edit(User $user): Response
    {
        return Inertia::render('users/form', [
            'user' => $user->load('roles:id,name'),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'jenis_kelamin' => ['nullable', 'in:Laki-laki,Perempuan'],
            'avatar' => ['sometimes', 'nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'exists:roles,name'],
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $validated['avatar'] = $request->file('avatar')->store('users', 'public');
        } elseif (! array_key_exists('avatar', $validated)) {
            $validated['avatar'] = $user->avatar;
        }

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $user->update(collect($validated)->except('role')->all());
        $user->syncRoles([$validated['role']]);

        return to_route('users.show', $user)->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return to_route('users.index')->with('success', 'User berhasil dihapus.');
    }
}
