<?php

namespace App\Http\Middleware;

use App\Support\ModulePermissions;
use Closure;
use Illuminate\Http\Request;

class AuthorizeModule
{
    public function handle(Request $request, Closure $next)
    {
        $name = $request->route()->getName();
        if ($name === 'dashboard') return $next($request);
        [$module] = explode('.', (string) $name);
        if (!$module || !isset(ModulePermissions::MODULES[$module])) {
            abort(403, 'Route modul tidak terdaftar.');
        }

        $method = $request->route()->getActionMethod();
        if ($module === 'reports') {
            abort_unless(ModulePermissions::allows($request->user(), 'reports', 'view'), 403, 'Anda tidak memiliki izin untuk melihat laporan.');
            return $next($request);
        }
        $action = match ($method) {
            'index', 'show', 'showPerpindahan', 'history' => 'view',
            'create', 'store' => $module === 'permissions' ? 'edit' : 'create',
            'destroy' => 'delete',
            default => 'edit',
        };
        $allowed = ModulePermissions::allows($request->user(), $module, $action);
        abort_unless($allowed, 403, 'Anda tidak memiliki izin untuk tindakan ini.');
        return $next($request);
    }
}
