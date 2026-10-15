<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionMatrixController extends Controller
{
    protected function groups(): array
    {
        return \App\Support\ModulePermissions::GROUPS;
    }

    protected function getPermissionNames(): array
    {
        $permissionNames = [];

        foreach ($this->groups() as $groupName => $items) {
            foreach ($items as $title) {
                $label = strtolower($title);
                $permissionNames = array_merge($permissionNames, [
                    'view ' . $label,
                    'create ' . $label,
                    'edit ' . $label,
                    'delete ' . $label,
                ]);
            }
        }

        return $permissionNames;
    }

    protected function ensurePermissionsExist(): void
    {
        foreach ($this->getPermissionNames() as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }
    }

    public function index(): Response
    {
        $this->ensurePermissionsExist();

        $roles = Role::query()->orderBy('name')->get(['id', 'name']);

        $permissionGroups = [];
        foreach ($this->groups() as $groupName => $items) {
            foreach ($items as $title) {
                $label = strtolower($title);
                $permissionNames = [
                    'view ' . $label,
                    'create ' . $label,
                    'edit ' . $label,
                    'delete ' . $label,
                ];

                $permissionGroups[] = [
                    'group' => $groupName,
                    'title' => $title,
                    'permissions' => $permissionNames,
                ];
            }
        }

        $rolesWithPermissions = $roles->mapWithKeys(function (Role $role) {
            $owned = $role->permissions()->pluck('name')->map('strtolower');
            $effective = collect($this->groups())->flatMap(function ($items) use ($owned) {
                return collect($items)->flatMap(function ($title) use ($owned) {
                    $module = \App\Support\ModulePermissions::moduleForLabel($title);
                    return collect(['view', 'create', 'edit', 'delete'])
                        ->filter(fn ($action) => $module && $owned->intersect(\App\Support\ModulePermissions::permissionNames($module, $action))->isNotEmpty())
                        ->map(fn ($action) => "$action ".strtolower($title));
                });
            })->values()->all();

            return [$role->id => $effective];
        });

        return Inertia::render('permissions/index', [
            'roles' => $roles,
            'permissionGroups' => $permissionGroups,
            'rolePermissions' => $rolesWithPermissions,
            'selectedRoleId' => $roles->first()?->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensurePermissionsExist();

        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
            'permissions' => ['nullable', 'array'],
        ]);

        $role = Role::findOrFail($validated['role_id']);
        $permissions = $validated['permissions'] ?? [];
        $selectedPermissionNames = collect($permissions)
            ->filter(fn ($value) => $value === true || $value === 'true' || $value === 1 || $value === '1')
            ->keys()
            ->all();

        $selectedPermissionNames = array_map('strval', $selectedPermissionNames);
        $selectedPermissionNames = array_values(array_intersect($selectedPermissionNames, $this->getPermissionNames()));
        // Replace all CRUD aliases managed by the matrix, preserving only action-specific permissions.
        $retained = $role->permissions()->pluck('name')->filter(
            fn (string $name): bool => ! \App\Support\ModulePermissions::managedPermissionNames()->contains(strtolower($name)),
        )->all();
        $role->syncPermissions(array_merge($retained, $selectedPermissionNames));

        return to_route('permissions.index')->with('success', 'Permission berhasil diperbarui.');
    }
}
