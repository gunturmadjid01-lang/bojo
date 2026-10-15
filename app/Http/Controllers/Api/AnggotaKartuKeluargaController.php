<?php

namespace App\Http\Controllers\Api;

use App\Models\AnggotaKartuKeluarga;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnggotaKartuKeluargaController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeModule($request, 'anggota-kartu-keluarga');
        $search = trim((string) $request->input('search', ''));
        $members = AnggotaKartuKeluarga::query()->with(['kartuKeluarga:id,nomor_kk', 'penduduk:id,nik,nama_lengkap,jenis_kelamin'])
            ->when($search !== '', fn ($q) => $q->whereHas('penduduk', fn ($person) => $person->where('nama_lengkap', 'like', "%{$search}%")->orWhere('nik', 'like', "%{$search}%")))
            ->when($request->has('aktif'), fn ($q) => $q->where('aktif', $request->boolean('aktif')))
            ->latest()->paginate($this->perPage($request));
        return response()->json($members->through(fn (AnggotaKartuKeluarga $member) => $this->payload($member)));
    }

    public function show(Request $request, AnggotaKartuKeluarga $anggotaKartuKeluarga): JsonResponse
    {
        $this->authorizeModule($request, 'anggota-kartu-keluarga');
        return response()->json(['data' => $this->payload($anggotaKartuKeluarga->load(['kartuKeluarga:id,nomor_kk', 'penduduk:id,nik,nama_lengkap,jenis_kelamin']))]);
    }

    private function payload(AnggotaKartuKeluarga $member): array
    {
        return [
            'id' => $member->id, 'hubungan_keluarga' => $member->hubungan_keluarga,
            'tanggal_masuk' => $member->tanggal_masuk?->toDateString(), 'tanggal_keluar' => $member->tanggal_keluar?->toDateString(),
            'aktif' => $member->aktif, 'kartu_keluarga' => $member->kartuKeluarga?->only(['id', 'nomor_kk']),
            'penduduk' => $member->penduduk?->only(['id', 'nik', 'nama_lengkap', 'jenis_kelamin']),
        ];
    }
}
