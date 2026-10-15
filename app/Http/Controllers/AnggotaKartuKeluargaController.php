<?php

namespace App\Http\Controllers;

use App\Models\AnggotaKartuKeluarga;
use App\Models\KartuKeluarga;
use App\Models\Penduduk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AnggotaKartuKeluargaController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('anggota-kartu-keluarga/index', [
            'anggotaKartuKeluargas' => AnggotaKartuKeluarga::query()
                ->with(['kartuKeluarga:id,nomor_kk', 'penduduk:id,nik,nama_lengkap'])
                ->latest()
                ->get([
                    'id',
                    'kartu_keluarga_id',
                    'penduduk_id',
                    'hubungan_keluarga',
                    'tanggal_masuk',
                    'tanggal_keluar',
                    'aktif',
                    'alasan_keluar',
                    'created_at',
                ]),
            ...app(\App\Services\StatistikKependudukanService::class)->indexPayload('anggota-kartu-keluarga', $request),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('anggota-kartu-keluarga/create', [
            'selectedKartuKeluargaId' => $request->integer('kartu_keluarga_id') ?: null,
            'kartuKeluargas' => KartuKeluarga::query()
                ->with(['anggotaKeluarga' => fn ($query) => $query->where('aktif', true)->with('penduduk:id,nama_lengkap')])
                ->withExists(['anggotaKeluarga as memiliki_kepala_keluarga' => fn ($query) => $query->where('aktif', true)->where('hubungan_keluarga', 'Kepala Keluarga')])
                ->where('status', 'aktif')
                ->orderBy('nomor_kk')
                ->get(['id', 'nomor_kk', 'alamat']),
            'penduduk' => Penduduk::query()
                ->where('status_penduduk', 'aktif')
                ->where('masih_hidup', true)
                ->whereDoesntHave('anggotaKartuKeluarga', fn ($query) => $query->where('aktif', true))
                ->orderBy('nama_lengkap')
                ->get(['id', 'nik', 'nama_lengkap', 'jenis_kelamin', 'alamat']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kartu_keluarga_id' => ['required', new \App\Rules\WilayahExists(\App\Models\KartuKeluarga::class)],
            'anggotas' => ['required', 'array', 'min:1'],
            'anggotas.*.penduduk_id' => ['required', 'integer', 'distinct', new \App\Rules\WilayahExists(\App\Models\Penduduk::class)],
            'anggotas.*.hubungan_keluarga' => ['required', Rule::in(['Kepala Keluarga', 'Suami', 'Istri', 'Anak', 'Menantu', 'Cucu', 'Orang Tua', 'Mertua', 'Famili Lain', 'Lainnya'])],
            'anggotas.*.tanggal_masuk' => ['required', 'date'],
        ], [
            'anggotas.required' => 'Pilih minimal satu penduduk.',
            'anggotas.min' => 'Pilih minimal satu penduduk.',
        ]);

        DB::transaction(function () use ($validated): void {
            $pendudukIds = collect($validated['anggotas'])->pluck('penduduk_id');
            $sudahAktif = AnggotaKartuKeluarga::query()
                ->whereIn('penduduk_id', $pendudukIds)
                ->where('aktif', true)
                ->lockForUpdate()
                ->exists();

            if ($sudahAktif) {
                throw ValidationException::withMessages([
                    'anggotas' => 'Salah satu penduduk sudah terdaftar sebagai anggota aktif pada kartu keluarga lain.',
                ]);
            }

            $kepalaBaru = collect($validated['anggotas'])->where('hubungan_keluarga', 'Kepala Keluarga')->count();
            $kepalaAktif = AnggotaKartuKeluarga::query()
                ->where('kartu_keluarga_id', $validated['kartu_keluarga_id'])
                ->where('hubungan_keluarga', 'Kepala Keluarga')
                ->where('aktif', true)
                ->lockForUpdate()
                ->exists();

            if ($kepalaBaru > 1 || ($kepalaBaru === 1 && $kepalaAktif)) {
                throw ValidationException::withMessages([
                    'anggotas' => 'Satu kartu keluarga hanya boleh memiliki satu kepala keluarga aktif.',
                ]);
            }

            foreach ($validated['anggotas'] as $anggota) {
                AnggotaKartuKeluarga::create([
                    'kartu_keluarga_id' => $validated['kartu_keluarga_id'],
                    'penduduk_id' => $anggota['penduduk_id'],
                    'hubungan_keluarga' => $anggota['hubungan_keluarga'],
                    'tanggal_masuk' => $anggota['tanggal_masuk'],
                    'aktif' => true,
                ]);
            }
        });

        return to_route('kartu-keluarga.show', $validated['kartu_keluarga_id'])
            ->with('success', count($validated['anggotas']).' anggota keluarga berhasil didaftarkan.');
    }

    public function show(AnggotaKartuKeluarga $anggotaKartuKeluarga): Response
    {
        return Inertia::render('anggota-kartu-keluarga/show', [
            'anggotaKartuKeluarga' => $anggotaKartuKeluarga->load(['kartuKeluarga:id,nomor_kk', 'penduduk:id,nik,nama_lengkap']),
        ]);
    }

    public function edit(AnggotaKartuKeluarga $anggotaKartuKeluarga): Response
    {
        return Inertia::render('anggota-kartu-keluarga/form', [
            'anggotaKartuKeluarga' => $anggotaKartuKeluarga,
            'kartuKeluargas' => KartuKeluarga::query()->with(['anggotaKeluarga' => fn ($query) => $query->where('aktif', true)->with('penduduk:id,nama_lengkap')])->orderBy('nomor_kk')->get(['id', 'nomor_kk', 'alamat']),
            'penduduk' => Penduduk::query()->orderBy('nama_lengkap')->get(['id', 'nik', 'nama_lengkap']),
        ]);
    }

    public function update(Request $request, AnggotaKartuKeluarga $anggotaKartuKeluarga): RedirectResponse
    {
        $validated = $request->validate([
            'kartu_keluarga_id' => ['required', new \App\Rules\WilayahExists(\App\Models\KartuKeluarga::class)],
            'penduduk_id' => ['required', new \App\Rules\WilayahExists(\App\Models\Penduduk::class)],
            'hubungan_keluarga' => ['required', 'string', 'max:50'],
            'tanggal_masuk' => ['nullable', 'date'],
            'tanggal_keluar' => ['nullable', 'date'],
            'aktif' => ['nullable', 'boolean'],
            'alasan_keluar' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['aktif'] = $validated['aktif'] ?? $anggotaKartuKeluarga->aktif ?? true;

        $anggotaKartuKeluarga->update($validated);

        return to_route('anggota-kartu-keluarga.show', $anggotaKartuKeluarga)->with('success', 'Anggota keluarga berhasil diperbarui.');
    }

    public function destroy(AnggotaKartuKeluarga $anggotaKartuKeluarga): RedirectResponse
    {
        $anggotaKartuKeluarga->delete();

        return to_route('anggota-kartu-keluarga.index')->with('success', 'Anggota keluarga berhasil dihapus.');
    }
}
