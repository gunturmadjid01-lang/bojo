<?php

namespace App\Http\Controllers;

use App\Models\AnggotaKartuKeluarga;
use App\Models\Desa;
use App\Models\Dusun;
use App\Models\KartuKeluarga;
use App\Models\Penduduk;
use App\Models\RT;
use App\Models\RW;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class KartuKeluargaController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('kartu-keluarga/index', [
            'kartuKeluargas' => KartuKeluarga::query()
                ->with(['desa:id,nama_desa', 'dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'kepalaKeluarga.penduduk:id,nama_lengkap'])
                ->latest()
                ->get([
                    'id',
                    'desa_id',
                    'dusun_id',
                    'rw_id',
                    'rt_id',
                    'nomor_kk',
                    'alamat',
                    'kode_pos',
                    'tanggal_diterbitkan',
                    'status',
                    'created_at',
                ]),
            ...app(\App\Services\StatistikKependudukanService::class)->indexPayload('kartu-keluarga', $request),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('kartu-keluarga/form', [
            'kartuKeluarga' => null,
            'penduduk' => Penduduk::query()
                ->where('status_penduduk', 'aktif')
                ->where('masih_hidup', true)
                ->whereDoesntHave('anggotaKartuKeluarga', fn ($query) => $query->where('aktif', true))
                ->orderBy('nama_lengkap')
                ->get(['id', 'nik', 'nama_lengkap', 'jenis_kelamin', 'alamat', 'dusun_id', 'rw_id', 'rt_id']),
            'desas' => Desa::query()->orderBy('nama_desa')->get(['id', 'nama_desa']),
            'dusuns' => Dusun::query()->orderBy('nama')->get(['id', 'desa_id', 'nama']),
            'rws' => RW::query()->with('dusun:id,nama')->orderBy('nomor')->get(['id', 'dusun_id', 'nomor']),
            'rts' => RT::query()->with('rw:id,nomor')->orderBy('nomor')->get(['id', 'rw_id', 'nomor']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'desa_id' => ['required', new \App\Rules\WilayahExists(\App\Models\Desa::class)],
            'dusun_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Dusun::class)],
            'rw_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RW::class)],
            'rt_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RT::class)],
            'penduduk_id' => ['required', new \App\Rules\WilayahExists(\App\Models\Penduduk::class)],
            'nomor_kk' => ['required', 'string', 'max:16', 'unique:kartu_keluargas,nomor_kk'],
            'alamat' => ['required', 'string'],
            'kode_pos' => ['nullable', 'string', 'max:10'],
            'tanggal_diterbitkan' => ['nullable', 'date'],
            'status' => ['required', 'in:aktif,nonaktif'],
            'anggotas' => ['nullable', 'array'],
            'anggotas.*.penduduk_id' => ['required', 'integer', 'distinct', new \App\Rules\WilayahExists(\App\Models\Penduduk::class)],
            'anggotas.*.hubungan_keluarga' => ['required', 'string', 'max:50', 'not_in:Kepala Keluarga'],
            'anggotas.*.tanggal_masuk' => ['required', 'date'],
        ], [
            'penduduk_id.required' => 'Kepala keluarga wajib dipilih.',
        ]);

        $penduduk = Penduduk::query()->lockForUpdate()->findOrFail($validated['penduduk_id']);
        if ($penduduk->status_penduduk !== 'aktif' || ! $penduduk->masih_hidup) {
            throw ValidationException::withMessages([
                'penduduk_id' => 'Penduduk yang dipilih tidak dapat dijadikan kepala keluarga karena statusnya tidak aktif atau sudah meninggal.',
            ]);
        }

        if (($validated['dusun_id'] !== null && (int) $penduduk->dusun_id !== (int) $validated['dusun_id']) || ($validated['rw_id'] !== null && (int) $penduduk->rw_id !== (int) $validated['rw_id']) || ($validated['rt_id'] !== null && (int) $penduduk->rt_id !== (int) $validated['rt_id'])) {
            throw ValidationException::withMessages([
                'penduduk_id' => 'Kepala keluarga harus berasal dari dusun, RW, dan RT yang dipilih pada kartu keluarga.',
            ]);
        }

        if (AnggotaKartuKeluarga::query()->where('penduduk_id', $penduduk->id)->where('aktif', true)->exists()) {
            throw ValidationException::withMessages([
                'penduduk_id' => 'Penduduk yang dipilih sudah terdaftar aktif pada kartu keluarga lain.',
            ]);
        }

        DB::transaction(function () use ($validated, $penduduk): void {
            $anggotaTambahan = collect($validated['anggotas'] ?? []);
            if ($anggotaTambahan->contains(fn ($item) => (int) $item['penduduk_id'] === (int) $penduduk->id)) {
                throw ValidationException::withMessages(['anggotas' => 'Kepala keluarga tidak boleh dipilih ulang sebagai anggota.']);
            }
            $pendudukTambahan = Penduduk::query()->whereIn('id', $anggotaTambahan->pluck('penduduk_id'))->lockForUpdate()->get()->keyBy('id');
            foreach ($anggotaTambahan as $index => $anggota) {
                $calon = $pendudukTambahan->get($anggota['penduduk_id']);
                if (! $calon || $calon->status_penduduk !== 'aktif' || ! $calon->masih_hidup || ($validated['dusun_id'] !== null && (int) $calon->dusun_id !== (int) $validated['dusun_id']) || ($validated['rw_id'] !== null && (int) $calon->rw_id !== (int) $validated['rw_id']) || ($validated['rt_id'] !== null && (int) $calon->rt_id !== (int) $validated['rt_id'])) {
                    throw ValidationException::withMessages(["anggotas.$index.penduduk_id" => 'Anggota harus aktif, masih hidup, dan berasal dari wilayah KK yang dipilih.']);
                }
            }
            if (AnggotaKartuKeluarga::query()->whereIn('penduduk_id', $anggotaTambahan->pluck('penduduk_id'))->where('aktif', true)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['anggotas' => 'Salah satu penduduk sudah menjadi anggota aktif pada KK lain.']);
            }
            $kk = KartuKeluarga::create([
                'desa_id' => $validated['desa_id'],
                'dusun_id' => $validated['dusun_id'] ?? null,
                'rw_id' => $validated['rw_id'] ?? null,
                'rt_id' => $validated['rt_id'] ?? null,
                'nomor_kk' => $validated['nomor_kk'],
                'alamat' => $validated['alamat'],
                'kode_pos' => $validated['kode_pos'] ?? null,
                'tanggal_diterbitkan' => $validated['tanggal_diterbitkan'] ?? null,
                'status' => $validated['status'],
            ]);

            AnggotaKartuKeluarga::create([
                'kartu_keluarga_id' => $kk->id,
                'penduduk_id' => $penduduk->id,
                'hubungan_keluarga' => 'Kepala Keluarga',
                'tanggal_masuk' => now()->toDateString(),
                'aktif' => true,
            ]);
            foreach ($anggotaTambahan as $anggota) {
                AnggotaKartuKeluarga::create(['kartu_keluarga_id' => $kk->id, 'penduduk_id' => $anggota['penduduk_id'], 'hubungan_keluarga' => $anggota['hubungan_keluarga'], 'tanggal_masuk' => $anggota['tanggal_masuk'], 'aktif' => true]);
            }
        });

        return to_route('kartu-keluarga.index')->with('success', 'Kartu keluarga dan kepala keluarga berhasil ditambahkan.');
    }

    public function show(KartuKeluarga $kartuKeluarga): Response
    {
        return Inertia::render('kartu-keluarga/show', [
            'kartuKeluarga' => $kartuKeluarga->load([
                'desa:id,nama_desa',
                'dusun:id,nama',
                'rw:id,nomor',
                'rt:id,nomor',
                'kepalaKeluarga.penduduk:id,nama_lengkap,nik',
                'anggotaKeluarga' => fn ($query) => $query->with('penduduk:id,nik,nama_lengkap,jenis_kelamin')->orderByDesc('aktif')->orderBy('hubungan_keluarga'),
            ]),
        ]);
    }

    public function edit(KartuKeluarga $kartuKeluarga): Response
    {
        $kepalaKeluarga = $kartuKeluarga->anggotaKeluarga()->where('aktif', true)->where('hubungan_keluarga', 'Kepala Keluarga')->with('penduduk:id,nama_lengkap,nik')->first();

        return Inertia::render('kartu-keluarga/form', [
            'kartuKeluarga' => $kartuKeluarga->load(['desa:id,nama_desa', 'dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'kepalaKeluarga.penduduk:id,nama_lengkap,nik']),
            'penduduk' => Penduduk::query()
                ->where('status_penduduk', 'aktif')
                ->where('masih_hidup', true)
                ->where(function ($query) use ($kepalaKeluarga): void {
                    $query->whereDoesntHave('anggotaKartuKeluarga', fn ($innerQuery) => $innerQuery->where('aktif', true))
                        ->orWhereKey($kepalaKeluarga?->penduduk_id);
                })
                ->orderBy('nama_lengkap')
                ->get(['id', 'nik', 'nama_lengkap', 'jenis_kelamin', 'alamat', 'dusun_id', 'rw_id', 'rt_id']),
            'desas' => Desa::query()->orderBy('nama_desa')->get(['id', 'nama_desa']),
            'dusuns' => Dusun::query()->orderBy('nama')->get(['id', 'desa_id', 'nama']),
            'rws' => RW::query()->with('dusun:id,nama')->orderBy('nomor')->get(['id', 'dusun_id', 'nomor']),
            'rts' => RT::query()->with('rw:id,nomor')->orderBy('nomor')->get(['id', 'rw_id', 'nomor']),
        ]);
    }

    public function update(Request $request, KartuKeluarga $kartuKeluarga): RedirectResponse
    {
        $validated = $request->validate([
            'desa_id' => ['required', new \App\Rules\WilayahExists(\App\Models\Desa::class)],
            'dusun_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Dusun::class)],
            'rw_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RW::class)],
            'rt_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RT::class)],
            'nomor_kk' => ['required', 'string', 'max:16', 'unique:kartu_keluargas,nomor_kk,'.$kartuKeluarga->id],
            'alamat' => ['required', 'string'],
            'kode_pos' => ['nullable', 'string', 'max:10'],
            'tanggal_diterbitkan' => ['nullable', 'date'],
            'status' => ['required', 'in:aktif,nonaktif'],
        ]);

        $kartuKeluarga->update($validated);

        return to_route('kartu-keluarga.show', $kartuKeluarga)->with('success', 'Kartu keluarga berhasil diperbarui.');
    }

    public function destroy(KartuKeluarga $kartuKeluarga): RedirectResponse
    {
        $kartuKeluarga->delete();

        return to_route('kartu-keluarga.index')->with('success', 'Kartu keluarga berhasil dihapus.');
    }
}
