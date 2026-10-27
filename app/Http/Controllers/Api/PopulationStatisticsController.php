<?php

namespace App\Http\Controllers\Api;

use App\Services\StatistikKependudukanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PopulationStatisticsController extends ApiController
{
    private const MODULES = ['penduduk', 'kelahiran', 'kematian', 'penduduk-datang', 'penduduk-pindah'];

    public function __invoke(Request $request, string $module, StatistikKependudukanService $statistics): JsonResponse
    {
        abort_unless(in_array($module, self::MODULES, true), 404);
        $this->authorizeModule($request, $module);
        $years = $statistics->years();
        $year = $request->integer('year', now()->year);
        $year = in_array($year, $years, true) ? $year : now()->year;

        return response()->json([
            'module' => $module,
            'year' => $year,
            'years' => $years,
            'statistics' => $statistics->for($module, $request->user(), $year, true),
        ]);
    }
}
