<?php

namespace App\Http\Controllers\Api;

use App\Models\Penduduk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PendudukController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeModule($request, 'penduduk');
        $search = trim((string) $request->input('search', ''));
        $query = Penduduk::query()->with(['dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'pendidikan:id,nama', 'pekerjaan:id,nama'])
            ->when($search !== '', fn ($q) => $q->where(fn ($searchQuery) => $searchQuery->where('nama_lengkap', 'like', "%{$search}%")->orWhere('nik', 'like', "%{$search}%")))
            ->when($request->filled('status'), fn ($q) => $q->where('status_penduduk', $request->string('status')))
            ->latest();
        return response()->json($query->paginate($this->perPage($request))->through(fn (Penduduk $person) => $this->payload($person)));
    }

    public function show(Request $request, Penduduk $penduduk): JsonResponse
    {
        $this->authorizeModule($request, 'penduduk');
        return response()->json(['data' => $this->payload($penduduk->load(['dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'pendidikan:id,nama', 'pekerjaan:id,nama']))]);
    }

    private function payload(Penduduk $person): array
    {
        return [
            'id' => $person->id, 'nik' => $person->nik, 'nama_lengkap' => $person->nama_lengkap,
            'tempat_lahir' => $person->tempat_lahir, 'tanggal_lahir' => $person->tanggal_lahir?->toDateString(),
            'jenis_kelamin' => $person->jenis_kelamin, 'agama' => $person->agama, 'golongan_darah' => $person->golongan_darah,
            'status_perkawinan' => $person->status_perkawinan, 'kewarganegaraan' => $person->kewarganegaraan,
            'alamat' => $person->alamat, 'telepon' => $person->telepon, 'email' => $person->email,
            'nama_ayah' => $person->nama_ayah, 'nama_ibu' => $person->nama_ibu,
            'pendidikan_id' => $person->pendidikan_id, 'pekerjaan_id' => $person->pekerjaan_id,
            'dusun_id' => $person->dusun_id, 'rw_id' => $person->rw_id, 'rt_id' => $person->rt_id,
            'status_penduduk' => $person->status_penduduk, 'masih_hidup' => $person->masih_hidup,
            'wilayah' => ['dusun' => $person->dusun?->only(['id', 'nama']), 'rw' => $person->rw?->only(['id', 'nomor']), 'rt' => $person->rt?->only(['id', 'nomor'])],
            'pendidikan' => $person->pendidikan?->only(['id', 'nama']), 'pekerjaan' => $person->pekerjaan?->only(['id', 'nama']),
        ];
    }
}
