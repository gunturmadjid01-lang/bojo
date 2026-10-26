<?php

namespace App\Http\Controllers\Api;

use App\Models\{Desa, Dusun, KartuKeluarga, Pekerjaan, Pendidikan, Penduduk, RT, RW};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PopulationOptionsController extends ApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->authorizeModule($request, 'penduduk');

        return response()->json([
            'desas' => Desa::query()->orderBy('nama_desa')->get(['id', 'nama_desa']),
            'dusuns' => Dusun::query()->orderBy('nama')->get(['id', 'desa_id', 'nama']),
            'rws' => RW::query()->orderBy('nomor')->get(['id', 'dusun_id', 'nomor']),
            'rts' => RT::query()->orderBy('nomor')->get(['id', 'rw_id', 'nomor']),
            'pendidikans' => Pendidikan::query()->orderBy('nama')->get(['id', 'kode', 'nama']),
            'pekerjaans' => Pekerjaan::query()->orderBy('nama')->get(['id', 'kode', 'nama']),
            'penduduk' => Penduduk::query()->with(['dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'anggotaKartuKeluarga' => fn ($query) => $query->where('aktif', true)->with('kartuKeluarga:id,nomor_kk,alamat')])->orderBy('nama_lengkap')->get(['id', 'nik', 'nama_lengkap', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama', 'golongan_darah', 'status_perkawinan', 'pendidikan_id', 'pekerjaan_id', 'kewarganegaraan', 'nama_ayah', 'nama_ibu', 'telepon', 'status_penduduk', 'masih_hidup', 'alamat', 'dusun_id', 'rw_id', 'rt_id']),
            'kartuKeluargas' => KartuKeluarga::query()->where('status', 'aktif')->with(['desa:id,nama_desa', 'dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'anggotaKeluarga' => fn ($query) => $query->where('aktif', true)->with('penduduk:id,nik,nama_lengkap,jenis_kelamin')])->orderBy('nomor_kk')->get(['id', 'nomor_kk', 'desa_id', 'dusun_id', 'rw_id', 'rt_id', 'alamat', 'status']),
        ]);
    }
}
