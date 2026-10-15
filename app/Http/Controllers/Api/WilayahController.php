<?php

namespace App\Http\Controllers\Api;

use App\Models\{Desa, Dusun, Pendidikan, Pekerjaan, RT, RW};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WilayahController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeModule($request, 'penduduk');
        return response()->json([
            'desas' => Desa::query()->where('aktif', true)->orderBy('nama_desa')->get(['id', 'nama_desa']),
            'dusuns' => Dusun::query()->where('aktif', true)->orderBy('nama')->get(['id', 'desa_id', 'nama']),
            'rws' => RW::query()->where('aktif', true)->orderBy('nomor')->get(['id', 'dusun_id', 'nomor']),
            'rts' => RT::query()->where('aktif', true)->orderBy('nomor')->get(['id', 'rw_id', 'nomor']),
            'pendidikan' => Pendidikan::query()->where('aktif', true)->orderBy('nama')->get(['id', 'nama']),
            'pekerjaan' => Pekerjaan::query()->where('aktif', true)->orderBy('nama')->get(['id', 'nama']),
        ]);
    }
}
