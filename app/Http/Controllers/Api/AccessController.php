<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Support\ModulePermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccessController extends ApiController
{
    private function authorize(Request $request, string $module, string $action = 'view'): void
    {
        $this->authorizeModule($request, $module, $action);
    }

    public function users(Request $request): JsonResponse
    {
        $this->authorize($request, 'users');
        $users = User::query()->with('roles:id,name')->latest()->paginate($this->perPage($request));
        return response()->json($users->through(fn (User $user) => $this->userPayload($user)));
    }

    public function roles(Request $request): JsonResponse
    {
        $this->authorize($request, 'users');
        return response()->json(['data' => Role::query()->orderBy('name')->get(['id', 'name'])]);
    }

    public function storeUser(Request $request): JsonResponse
    {
        $this->authorize($request, 'users', 'create');
        $data = $request->validate($this->userRules());
        $user = User::create([
            'name' => $data['name'], 'username' => $data['username'], 'email' => $data['email'] ?? null,
            'jenis_kelamin' => $data['jenis_kelamin'] ?? null, 'password' => Hash::make($data['password']),
        ]);
        $user->syncRoles([$data['role']]);
        return response()->json(['data' => $this->userPayload($user->load('roles:id,name'))], 201);
    }

    public function updateUser(Request $request, string $id): JsonResponse
    {
        $this->authorize($request, 'users', 'edit');
        $user = User::query()->findOrFail((int) $id);
        $data = $request->validate($this->userRules($user));
        $userData = ['name' => $data['name'], 'username' => $data['username'], 'email' => $data['email'] ?? null, 'jenis_kelamin' => $data['jenis_kelamin'] ?? null];
        if (!empty($data['password'])) $userData['password'] = Hash::make($data['password']);
        $user->update($userData);
        $user->syncRoles([$data['role']]);
        return response()->json(['data' => $this->userPayload($user->fresh()->load('roles:id,name'))]);
    }

    public function destroyUser(Request $request, string $id): JsonResponse
    {
        $this->authorize($request, 'users', 'delete');
        abort_if((int) $id === (int) $request->user()->id, 422, 'User yang sedang digunakan tidak dapat dihapus.');
        User::query()->findOrFail((int) $id)->delete();
        return response()->json(['message' => 'User berhasil dihapus.']);
    }

    public function matrix(Request $request): JsonResponse
    {
        $this->authorize($request, 'permissions');
        $permissionGroups = [];
        $permissionNames = [];
        foreach (ModulePermissions::GROUPS as $group => $items) foreach ($items as $title) {
            $label = strtolower($title);
            $names = array_map(fn (string $action) => "$action $label", ['view', 'create', 'edit', 'delete']);
            $permissionGroups[] = ['group' => $group, 'title' => $title, 'permissions' => $names];
            $permissionNames = array_merge($permissionNames, $names);
        }
        foreach ($permissionNames as $name) Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        $roles = Role::query()->orderBy('name')->get(['id', 'name']);
        $rolePermissions = $roles->mapWithKeys(function (Role $role) {
            $owned = $role->permissions()->pluck('name')->map('strtolower');
            $effective = collect(ModulePermissions::GROUPS)->flatMap(function ($items) use ($owned) {
                return collect($items)->flatMap(function ($title) use ($owned) {
                    $module = ModulePermissions::moduleForLabel($title);
                    return collect(['view', 'create', 'edit', 'delete'])
                        ->filter(fn ($action) => $module && $owned->intersect(ModulePermissions::permissionNames($module, $action))->isNotEmpty())
                        ->map(fn ($action) => "$action ".strtolower($title));
                });
            })->values()->all();
            return [$role->id => $effective];
        });
        return response()->json(['permissionGroups' => $permissionGroups, 'roles' => $roles, 'rolePermissions' => $rolePermissions]);
    }

    public function updateMatrix(Request $request): JsonResponse
    {
        $this->authorize($request, 'permissions', 'edit');
        $data = $request->validate(['role_id' => ['required', 'exists:roles,id'], 'permissions' => ['nullable', 'array']]);
        $allowed = collect($this->matrixPermissionNames());
        $selected = collect($data['permissions'] ?? [])->filter(fn ($value) => $value === true || $value === 'true' || $value === 1 || $value === '1')->keys()->map('strval')->intersect($allowed)->values()->all();
        $role = Role::findOrFail($data['role_id']);
        $retained = $role->permissions()->pluck('name')->filter(
            fn (string $name): bool => ! ModulePermissions::managedPermissionNames()->contains(strtolower($name)),
        )->all();
        $role->syncPermissions(array_merge($retained, $selected));
        return response()->json(['message' => 'Permission berhasil diperbarui.']);
    }

    private function matrixPermissionNames(): array
    {
        return collect(ModulePermissions::GROUPS)->flatMap(fn ($items) => collect($items)->flatMap(fn ($title) => collect(['view', 'create', 'edit', 'delete'])->map(fn ($action) => "$action " . strtolower($title))))->values()->all();
    }

    private function userRules(?User $user = null): array
    {
        $suffix = $user ? ',' . $user->id : '';
        return ['name' => ['required', 'string', 'max:255'], 'username' => ['required', 'string', 'max:255', 'unique:users,username' . $suffix], 'email' => ['nullable', 'email', 'max:255', 'unique:users,email' . $suffix], 'jenis_kelamin' => ['nullable', Rule::in(['Laki-laki', 'Perempuan'])], 'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'], 'role' => ['required', 'exists:roles,name']];
    }

    private function userPayload(User $user): array
    {
        return ['id' => $user->id, 'name' => $user->name, 'username' => $user->username, 'email' => $user->email, 'jenis_kelamin' => $user->jenis_kelamin, 'roles' => $user->roles->pluck('name')->values()->all(), 'created_at' => $user->created_at];
    }
}
