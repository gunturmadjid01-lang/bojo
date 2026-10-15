<?php

namespace App\Http\Controllers;

use App\Models\KartuKeluarga;
use App\Models\SosialEkonomiKeluarga;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SosialEkonomiKeluargaController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('sosial-ekonomi-keluarga/index', [
            'sosialEkonomiKeluargas' => SosialEkonomiKeluarga::query()
                ->with(['kartuKeluarga:id,nomor_kk', 'didataOleh:id,name'])
                ->latest()
                ->get([
                    'id',
                    'kartu_keluarga_id',
                    'status_kepemilikan_rumah',
                    'kondisi_rumah',
                    'jenis_lantai',
                    'jenis_dinding',
                    'jenis_atap',
                    'sumber_air',
                    'sumber_listrik',
                    'daya_listrik',
                    'fasilitas_toilet',
                    'bahan_bakar_memasak',
                    'memiliki_kendaraan',
                    'akses_internet',
                    'kategori_ekonomi',
                    'tanggal_pendataan',
                    'didata_oleh',
                    'created_at',
                ]),
            ...app(\App\Services\StatistikKependudukanService::class)->indexPayload('sosial-ekonomi-keluarga', $request),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('sosial-ekonomi-keluarga/form', [
            'sosialEkonomiKeluarga' => null,
            'kartuKeluargas' => KartuKeluarga::query()->orderBy('nomor_kk')->get(['id', 'nomor_kk', 'alamat']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kartu_keluarga_id' => ['required', new \App\Rules\WilayahExists(\App\Models\KartuKeluarga::class)],
            'status_kepemilikan_rumah' => ['nullable', 'string', 'max:255'],
            'kondisi_rumah' => ['nullable', 'string', 'max:255'],
            'jenis_lantai' => ['nullable', 'string', 'max:255'],
            'jenis_dinding' => ['nullable', 'string', 'max:255'],
            'jenis_atap' => ['nullable', 'string', 'max:255'],
            'sumber_air' => ['nullable', 'string', 'max:255'],
            'sumber_listrik' => ['nullable', 'string', 'max:255'],
            'daya_listrik' => ['nullable', 'string', 'max:255'],
            'fasilitas_toilet' => ['nullable', 'string', 'max:255'],
            'bahan_bakar_memasak' => ['nullable', 'string', 'max:255'],
            'memiliki_kendaraan' => ['nullable', 'boolean'],
            'akses_internet' => ['nullable', 'boolean'],
            'kategori_ekonomi' => ['nullable', 'string', 'max:255'],
            'tanggal_pendataan' => ['nullable', 'date'],
        ]);

        $validated['didata_oleh'] = auth()->id();

        SosialEkonomiKeluarga::create($validated);

        return to_route('sosial-ekonomi-keluarga.index')->with('success', 'Sosial ekonomi keluarga berhasil ditambahkan.');
    }

    public function show(SosialEkonomiKeluarga $sosialEkonomiKeluarga): Response
    {
        return Inertia::render('sosial-ekonomi-keluarga/show', [
            'sosialEkonomiKeluarga' => $sosialEkonomiKeluarga->load(['kartuKeluarga:id,nomor_kk,alamat', 'didataOleh:id,name']),
        ]);
    }

    public function edit(SosialEkonomiKeluarga $sosialEkonomiKeluarga): Response
    {
        return Inertia::render('sosial-ekonomi-keluarga/form', [
            'sosialEkonomiKeluarga' => $sosialEkonomiKeluarga,
            'kartuKeluargas' => KartuKeluarga::query()->orderBy('nomor_kk')->get(['id', 'nomor_kk', 'alamat']),
        ]);
    }

    public function update(Request $request, SosialEkonomiKeluarga $sosialEkonomiKeluarga): RedirectResponse
    {
        $validated = $request->validate([
            'kartu_keluarga_id' => ['required', new \App\Rules\WilayahExists(\App\Models\KartuKeluarga::class)],
            'status_kepemilikan_rumah' => ['nullable', 'string', 'max:255'],
            'kondisi_rumah' => ['nullable', 'string', 'max:255'],
            'jenis_lantai' => ['nullable', 'string', 'max:255'],
            'jenis_dinding' => ['nullable', 'string', 'max:255'],
            'jenis_atap' => ['nullable', 'string', 'max:255'],
            'sumber_air' => ['nullable', 'string', 'max:255'],
            'sumber_listrik' => ['nullable', 'string', 'max:255'],
            'daya_listrik' => ['nullable', 'string', 'max:255'],
            'fasilitas_toilet' => ['nullable', 'string', 'max:255'],
            'bahan_bakar_memasak' => ['nullable', 'string', 'max:255'],
            'memiliki_kendaraan' => ['nullable', 'boolean'],
            'akses_internet' => ['nullable', 'boolean'],
            'kategori_ekonomi' => ['nullable', 'string', 'max:255'],
            'tanggal_pendataan' => ['nullable', 'date'],
        ]);

        $validated['didata_oleh'] = auth()->id() ?? $sosialEkonomiKeluarga->didata_oleh;

        $sosialEkonomiKeluarga->update($validated);

        return to_route('sosial-ekonomi-keluarga.show', $sosialEkonomiKeluarga)->with('success', 'Sosial ekonomi keluarga berhasil diperbarui.');
    }

    public function destroy(SosialEkonomiKeluarga $sosialEkonomiKeluarga): RedirectResponse
    {
        $sosialEkonomiKeluarga->delete();

        return to_route('sosial-ekonomi-keluarga.index')->with('success', 'Sosial ekonomi keluarga berhasil dihapus.');
    }
}
