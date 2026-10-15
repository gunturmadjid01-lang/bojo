<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Support\ModulePermissions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'super_admin',
            'operator',
            'kepala_desa',
            'kepala_dusun',
            'sekdes',
        ];

        $modules = collect(ModulePermissions::GROUPS)->flatten()->map(fn (string $label): string => strtolower($label));

        $permissions = collect([
            'lihat-penugasan-perangkat',
            'tambah-penugasan-perangkat',
            'ganti-penugasan-perangkat',
            'akhiri-penugasan-perangkat',
            'lihat-riwayat-penugasan',
        ])->map(fn (string $name): Permission => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));

        $actions = ['create', 'edit', 'delete', 'view'];
        $permissions = $permissions->merge($modules
            ->crossJoin($actions)
            ->map(fn (array $permission): string => "{$permission[1]} {$permission[0]}")
            ->map(fn (string $name): Permission => Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ])));

        $reportPermissions = collect(['view reports', 'create reports', 'edit reports', 'delete reports'])
            ->map(fn (string $name): Permission => Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]));
        $permissions = $permissions->merge($reportPermissions);

        $roleModels = collect($roles)->mapWithKeys(function (string $roleName) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
            return [$roleName => $role];
        });

        $defaults = [
            'super_admin' => $permissions,
            'operator' => collect(),
            'kepala_desa' => $permissions->filter(fn (Permission $permission) => str_starts_with($permission->name, 'view ') || $permission->name === 'lihat-riwayat-penugasan'),
            'sekdes' => $permissions->filter(fn (Permission $permission) => !str_starts_with($permission->name, 'delete ')),
            'kepala_dusun' => $permissions->filter(fn (Permission $permission) => str_starts_with($permission->name, 'view ')),
        ];

        foreach ($roleModels as $roleName => $role) {
            if ($role->permissions()->doesntExist()) {
                $role->syncPermissions($defaults[$roleName]->values()->all());
            }
        }

    }
}
