<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ModulePermissions;
use Illuminate\Http\Request;

abstract class ApiController extends Controller
{
    protected function authorizeModule(Request $request, string $module, string $action = 'view'): void
    {
        $user = $request->user();
        if (! ModulePermissions::allows($user, $module, $action)) {
            $roles = $user?->roles()->pluck('name')->implode(', ') ?: 'tanpa role';
            $required = ModulePermissions::permissionNames($module, $action)->implode(' / ');
            $owned = $user?->getAllPermissions()
                ->pluck('name')
                ->filter(fn (string $name): bool => str_starts_with(strtolower($name), strtolower($action).' '))
                ->implode(', ') ?: 'tidak ada';

            abort(403, sprintf(
                'Izin %s modul %s tidak ditemukan untuk akun %s. Role API: %s. Dibutuhkan: %s. Izin %s akun: %s. [AUTH-20261026]',
                $action,
                $module,
                $user?->username ?? (string) $user?->id,
                $roles,
                $required,
                $action,
                $owned,
            ));
        }
        abort_unless($action === 'view' || $request->user()->tokenCan('api:write'), 403, 'Token tidak memiliki kemampuan menulis.');
    }

    protected function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 25), 1), 100);
    }
}
