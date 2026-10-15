<?php

namespace App\Http\Controllers;

use App\Models\AnggotaKartuKeluarga;
use App\Models\Desa;
use App\Models\Dusun;
use App\Models\KartuKeluarga;
use App\Models\Pekerjaan;
use App\Models\Penduduk;
use App\Models\Pendidikan;
use App\Models\PendudukPindah;
use App\Models\PerpindahanPenduduk;
use App\Models\RT;
use App\Models\RW;
use App\Services\PendudukPindahService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PendudukPindahController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('penduduk-pindah/index', [
            'pendudukPindahs' => PendudukPindah::query()
                ->with(['penduduk:id,nik,nama_lengkap'])
                ->latest()
                ->get([
                    'id',
                    'penduduk_id',
                    'tanggal_pindah',
                    'alamat_tujuan',
                    'desa_tujuan',
                    'kecamatan_tujuan',
                    'kabupaten_tujuan',
                    'provinsi_tujuan',
                    'nomor_surat_pindah',
                    'alasan',
                    'status',
                    'catatan',
                    'created_at',
                ]),
            'perpindahans' => PerpindahanPenduduk::query()
                ->with(['kartuKeluargaAsal:id,nomor_kk,dusun_id,rw_id,rt_id,alamat', 'kartuKeluargaTujuan:id,nomor_kk,dusun_id,rw_id,rt_id,alamat', 'anggotaPerpindahan.penduduk:id,nik,nama_lengkap'])
                ->latest()
                ->get(),
            ...app(\App\Services\StatistikKependudukanService::class)->indexPayload('penduduk-pindah', $request),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('penduduk-pindah/form', [
            'pendudukPindah' => null,
            'penduduk' => Penduduk::query()->where('status_penduduk', '!=', 'pindah')->where('masih_hidup', true)->with(['dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'anggotaKartuKeluarga' => fn ($query) => $query->where('aktif', true)->with('kartuKeluarga:id,nomor_kk,alamat')])->orderBy('nama_lengkap')->get(['id', 'nik', 'nama_lengkap', 'jenis_kelamin', 'alamat', 'dusun_id', 'rw_id', 'rt_id']),
            'kartuKeluargas' => KartuKeluarga::query()->where('status', 'aktif')->with(['desa:id,nama_desa', 'dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'anggotaKeluarga' => fn ($query) => $query->where('aktif', true)->with('penduduk:id,nik,nama_lengkap,jenis_kelamin')])->orderBy('nomor_kk')->get(['id', 'nomor_kk', 'alamat', 'desa_id', 'dusun_id', 'rw_id', 'rt_id']),
            'desas' => Desa::query()->orderBy('nama_desa')->get(['id', 'nama_desa']),
            'dusuns' => Dusun::query()->orderBy('nama')->get(['id', 'desa_id', 'nama']),
            'rws' => RW::query()->orderBy('nomor')->get(['id', 'dusun_id', 'nomor']),
            'rts' => RT::query()->orderBy('nomor')->get(['id', 'rw_id', 'nomor']),
            'pendidikans' => Pendidikan::query()->orderBy('nama')->get(['id', 'nama']),
            'pekerjaans' => Pekerjaan::query()->orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'penduduk_id' => ['required', new \App\Rules\WilayahExists(Penduduk::class)], 'tanggal_pindah' => ['required', 'date'],
            'alamat_tujuan' => ['nullable', 'string'], 'desa_tujuan' => ['nullable', 'string', 'max:255'],
            'kecamatan_tujuan' => ['nullable', 'string', 'max:255'], 'kabupaten_tujuan' => ['nullable', 'string', 'max:255'],
            'provinsi_tujuan' => ['nullable', 'string', 'max:255'], 'nomor_surat_pindah' => ['nullable', 'string', 'max:255'],
            'alasan' => ['nullable', 'string', 'max:255'], 'catatan' => ['nullable', 'string'],
        ]);
        app(\App\Services\IndividualMigrationService::class)->departure($validated, auth()->user());
        return to_route('penduduk-pindah.index')->with('success', 'Penduduk keluar berhasil dicatat.');

        /* Legacy family flow retained below only for historical reference. */
        $request->merge([
            'jenis_pindah' => $request->input('jenis_pindah', $request->input('penduduk_id') ? 'individu' : 'keluarga'),
            'jenis_tujuan_kk' => $request->input('jenis_tujuan_kk', $request->input('kk_baru') ? 'kk_baru' : 'gabung_kk'),
        ]);

        $validated = $request->validate([
            'jenis_pindah' => ['nullable', 'in:individu,keluarga'],
            'tujuan_pindah' => ['nullable', 'in:dalam_desa,keluar_desa'],
            'kartu_keluarga_asal_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\KartuKeluarga::class)],
            'jenis_tujuan_kk' => ['nullable', 'in:gabung_kk,kk_baru'],
            'tanggal_pindah' => ['required', 'date'],
            'kartu_keluarga_tujuan_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\KartuKeluarga::class)],
            'hubungan_keluarga' => ['nullable', 'string', 'max:50'],
            'penduduk_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Penduduk::class)],
            'nik' => ['nullable', 'string', 'max:16'],
            'nama_lengkap' => ['nullable', 'string', 'max:255'],
            'tempat_lahir' => ['nullable', 'string', 'max:255'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['nullable', 'in:Laki-laki,Perempuan'],
            'agama' => ['nullable', 'string', 'max:50'],
            'golongan_darah' => ['nullable', 'in:A,B,AB,O'],
            'status_perkawinan' => ['nullable', 'string', 'max:50'],
            'pendidikan_id' => ['nullable', 'exists:pendidikans,id'],
            'pekerjaan_id' => ['nullable', 'exists:pekerjaans,id'],
            'kewarganegaraan' => ['nullable', 'string', 'max:50'],
            'nama_ayah' => ['nullable', 'string', 'max:255'],
            'nama_ibu' => ['nullable', 'string', 'max:255'],
            'telepon' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'alamat' => ['nullable', 'string'],
            'dusun_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Dusun::class)],
            'rw_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RW::class)],
            'rt_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RT::class)],
            'status_penduduk' => ['nullable', 'in:aktif,pindah,meninggal,sementara'],
            'masih_hidup' => ['nullable', 'boolean'],
            'alamat_tujuan' => ['nullable', 'string'],
            'desa_tujuan' => ['nullable', 'string', 'max:255'],
            'kecamatan_tujuan' => ['nullable', 'string', 'max:255'],
            'kabupaten_tujuan' => ['nullable', 'string', 'max:255'],
            'provinsi_tujuan' => ['nullable', 'string', 'max:255'],
            'nomor_surat_pindah' => ['nullable', 'string', 'max:255'],
            'alasan' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:selesai,proses,ditolak'],
            'catatan' => ['nullable', 'string'],
            'penduduk' => ['nullable', 'array'],
            'anggota' => ['nullable', 'array'],
            'anggota.*.nik' => ['nullable', 'string', 'max:16'],
            'anggota.*.nama_lengkap' => ['nullable', 'string', 'max:255'],
            'anggota.*.hubungan_keluarga' => ['nullable', 'string', 'max:50'],
            'anggota.*.alamat' => ['nullable', 'string'],
            'anggota.*.dusun_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Dusun::class)],
            'anggota.*.rw_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RW::class)],
            'anggota.*.rt_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RT::class)],
            'kk_baru' => ['nullable', 'array'],
            'kk_baru.nomor_kk' => ['nullable', 'string', 'max:16'],
            'kk_baru.desa_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Desa::class)],
            'kk_baru.dusun_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Dusun::class)],
            'kk_baru.rw_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RW::class)],
            'kk_baru.rt_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RT::class)],
            'kk_baru.alamat' => ['nullable', 'string'],
            'kk_baru.kode_pos' => ['nullable', 'string', 'max:10'],
            'domisili_tujuan' => ['nullable', 'array'],
            'domisili_tujuan.dusun_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Dusun::class)],
            'domisili_tujuan.rw_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RW::class)],
            'domisili_tujuan.rt_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RT::class)],
            'domisili_tujuan.alamat' => ['nullable', 'string'],
        ]);

        $validated['status'] = $validated['status'] ?? 'selesai';
        $validated['dibuat_oleh'] = auth()->id();

        app(PendudukPindahService::class)->store($validated, auth()->user());

        return to_route('penduduk-pindah.index')->with('success', 'Penduduk pindah berhasil ditambahkan.');
    }

    public function show(PendudukPindah $pendudukPindah): Response
    {
        return Inertia::render('penduduk-pindah/show', [
            'pendudukPindah' => $pendudukPindah->load(['penduduk:id,nik,nama_lengkap']),
        ]);
    }

    public function showPerpindahan(PerpindahanPenduduk $perpindahanPenduduk): Response
    {
        return Inertia::render('penduduk-pindah/show', [
            'perpindahan' => $perpindahanPenduduk->load(['kartuKeluargaAsal.dusun:id,nama', 'kartuKeluargaTujuan.dusun:id,nama', 'anggotaPerpindahan.penduduk:id,nik,nama_lengkap,status_penduduk']),
            'pendudukPindah' => null,
        ]);
    }

    public function edit(PendudukPindah $pendudukPindah): Response
    {
        return Inertia::render('penduduk-pindah/form', [
            'pendudukPindah' => $pendudukPindah,
            'penduduk' => Penduduk::query()->orderBy('nama_lengkap')->get(['id', 'nik', 'nama_lengkap', 'jenis_kelamin', 'alamat', 'dusun_id', 'rw_id', 'rt_id']),
            'kartuKeluargas' => KartuKeluarga::query()->with(['desa:id,nama_desa', 'dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'anggotaKeluarga' => fn ($query) => $query->where('aktif', true)->with('penduduk:id,nama_lengkap')])->orderBy('nomor_kk')->get(['id', 'nomor_kk', 'alamat', 'desa_id', 'dusun_id', 'rw_id', 'rt_id']),
            'desas' => Desa::query()->orderBy('nama_desa')->get(['id', 'nama_desa']),
            'dusuns' => Dusun::query()->orderBy('nama')->get(['id', 'desa_id', 'nama']),
            'rws' => RW::query()->orderBy('nomor')->get(['id', 'dusun_id', 'nomor']),
            'rts' => RT::query()->orderBy('nomor')->get(['id', 'rw_id', 'nomor']),
            'pendidikans' => Pendidikan::query()->orderBy('nama')->get(['id', 'nama']),
            'pekerjaans' => Pekerjaan::query()->orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function update(Request $request, PendudukPindah $pendudukPindah): RedirectResponse
    {
        if ($request->filled('tujuan_pindah') || $pendudukPindah->status === 'selesai') {
            abort(403, 'Perpindahan selesai tidak dapat diubah.');
        }

        $validated = $request->validate([
            'penduduk_id' => ['required', new \App\Rules\WilayahExists(\App\Models\Penduduk::class)],
            'tanggal_pindah' => ['required', 'date'],
            'alamat_tujuan' => ['nullable', 'string'],
            'desa_tujuan' => ['nullable', 'string', 'max:255'],
            'kecamatan_tujuan' => ['nullable', 'string', 'max:255'],
            'kabupaten_tujuan' => ['nullable', 'string', 'max:255'],
            'provinsi_tujuan' => ['nullable', 'string', 'max:255'],
            'nomor_surat_pindah' => ['nullable', 'string', 'max:255'],
            'alasan' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:selesai,proses,ditolak'],
            'catatan' => ['nullable', 'string'],
        ]);

        $validated['status'] = $validated['status'] ?? $pendudukPindah->status ?? 'selesai';
        $validated['dibuat_oleh'] = auth()->id() ?? $pendudukPindah->dibuat_oleh;

        $pendudukPindah->update($validated);

        return to_route('penduduk-pindah.show', $pendudukPindah)->with('success', 'Penduduk pindah berhasil diperbarui.');
    }

    public function destroy(PendudukPindah $pendudukPindah): RedirectResponse
    {
        if ($pendudukPindah->status === 'selesai') {
            abort(403, 'Perpindahan selesai tidak dapat dihapus.');
        }
        $pendudukPindah->delete();

        return to_route('penduduk-pindah.index')->with('success', 'Penduduk pindah berhasil dihapus.');
    }
}
