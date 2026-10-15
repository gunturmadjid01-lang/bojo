<?php

namespace App\Http\Controllers\Api;

use App\Models\KartuKeluarga;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KartuKeluargaController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeModule($request, 'kartu-keluarga');
        $search = trim((string) $request->input('search', ''));
        $families = KartuKeluarga::query()->with(['desa:id,nama_desa', 'dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'anggotaKeluarga.penduduk:id,nama_lengkap,nik'])
            ->when($search !== '', fn ($q) => $q->where('nomor_kk', 'like', "%{$search}%"))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()->paginate($this->perPage($request));
        return response()->json($families->through(fn (KartuKeluarga $family) => $this->payload($family)));
    }

    public function show(Request $request, KartuKeluarga $kartuKeluarga): JsonResponse
    {
        $this->authorizeModule($request, 'kartu-keluarga');
        return response()->json(['data' => $this->payload($kartuKeluarga->load(['desa:id,nama_desa', 'dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'anggotaKeluarga.penduduk:id,nama_lengkap,nik,jenis_kelamin']))]);
    }

    private function payload(KartuKeluarga $family): array
    {
        return [
            'id' => $family->id, 'nomor_kk' => $family->nomor_kk, 'alamat' => $family->alamat,
            'kode_pos' => $family->kode_pos, 'tanggal_diterbitkan' => $family->tanggal_diterbitkan?->toDateString(), 'status' => $family->status,
            'wilayah' => ['desa' => $family->desa?->only(['id', 'nama_desa']), 'dusun' => $family->dusun?->only(['id', 'nama']), 'rw' => $family->rw?->only(['id', 'nomor']), 'rt' => $family->rt?->only(['id', 'nomor'])],
            'anggota' => $family->anggotaKeluarga->map(fn ($member) => ['id' => $member->id, 'hubungan_keluarga' => $member->hubungan_keluarga, 'aktif' => $member->aktif, 'penduduk' => $member->penduduk?->only(['id', 'nama_lengkap', 'nik', 'jenis_kelamin'])])->values()->all(),
        ];
    }
}
