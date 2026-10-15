<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ModulePermissions;
use Illuminate\Http\Request;

abstract class ApiController extends Controller
{
    protected function authorizeModule(Request $request, string $module, string $action = 'view'): void
    {
        abort_unless(ModulePermissions::allows($request->user(), $module, $action), 403, 'Anda tidak memiliki izin untuk modul ini.');
        abort_unless($action === 'view' || $request->user()->tokenCan('api:write'), 403, 'Token tidak memiliki kemampuan menulis.');
    }

    protected function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 25), 1), 100);
    }
}
