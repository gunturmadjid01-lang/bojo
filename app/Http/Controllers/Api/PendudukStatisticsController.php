<?php

namespace App\Http\Controllers\Api;

use App\Services\StatistikKependudukanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PendudukStatisticsController extends ApiController
{
    public function __invoke(Request $request, StatistikKependudukanService $statistics): JsonResponse
    {
        $this->authorizeModule($request, 'penduduk');
        $years = $statistics->years();
        $year = $request->integer('year', now()->year);
        $year = in_array($year, $years, true) ? $year : now()->year;

        return response()->json([
            'year' => $year,
            'years' => $years,
            'statistics' => $statistics->for('penduduk', $request->user(), $year, true),
        ]);
    }
}
