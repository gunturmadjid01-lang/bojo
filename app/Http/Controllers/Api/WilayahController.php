<?php

namespace App\Http\Controllers\Api;

use App\Models\{Desa, Dusun, Pendidikan, Pekerjaan, RT, RW};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WilayahController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        // Rute ini berada di belakang auth:sanctum dan hanya menyediakan
        // pilihan referensi untuk form/list. Jangan ikat ke izin Penduduk:
        // halaman Dusun, Pendidikan, Pekerjaan, dan Sosial juga memakainya.
        // Izin tindakan tetap diperiksa pada endpoint modul masing-masing.
        return response()->json([
            'desas' => Desa::query()->where('aktif', true)->orderBy('nama_desa')->get(['id', 'nama_desa']),
            'dusuns' => Dusun::query()->where('aktif', true)->orderBy('nama')->get(['id', 'desa_id', 'nama']),
            'rws' => RW::query()->where('aktif', true)->orderBy('nomor')->get(['id', 'dusun_id', 'nomor']),
            'rts' => RT::query()->where('aktif', true)->orderBy('nomor')->get(['id', 'rw_id', 'nomor']),
            // Tabel referensi ini tidak memiliki kolom `aktif`. Menyaringnya
            // dengan kolom tersebut membuat seluruh respons /wilayah gagal,
            // termasuk saat halaman Dusun hanya membutuhkan daftar desa.
            'pendidikan' => Pendidikan::query()->orderBy('nama')->get(['id', 'nama']),
            'pekerjaan' => Pekerjaan::query()->orderBy('nama')->get(['id', 'nama']),
        ]);
    }
}
