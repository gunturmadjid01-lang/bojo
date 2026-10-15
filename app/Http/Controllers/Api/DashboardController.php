<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StatistikKependudukanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, StatistikKependudukanService $statistics): JsonResponse
    {
        $years = $statistics->years();
        $year = $request->integer('year', now()->year);
        $year = in_array($year, $years, true) ? $year : now()->year;
        $modules = ['kartu-keluarga', 'penduduk', 'kelahiran', 'kematian', 'penduduk-datang', 'penduduk-pindah'];
        $payload = collect($modules)->filter(fn($module) => \App\Support\ModulePermissions::allows($request->user(), $module, 'view'))
            ->mapWithKeys(fn($module) => [$module => $statistics->for($module, $request->user(), $year)])->all();
        return response()->json(['year' => $year, 'years' => $years, 'modules' => $payload]);
    }
}
