<?php

namespace App\Http\Controllers;

use App\Models\AnggotaKedatangan;
use App\Models\KartuKeluarga;
use App\Models\KedatanganPenduduk;
use App\Models\Pekerjaan;
use App\Models\Penduduk;
use App\Models\PendudukDatang;
use App\Models\Pendidikan;
use App\Services\PendudukDatangService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PendudukDatangController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('penduduk-datang/index', [
            'pendudukDatangs' => KedatanganPenduduk::query()
                ->with(['penduduk:id,nik,nama_lengkap'])
                ->latest()
                ->get([
                    'id',
                    'penduduk_id',
                    'jenis_kedatangan',
                    'tanggal_datang',
                    'desa_sebelumnya',
                    'kecamatan_sebelumnya',
                    'kabupaten_sebelumnya',
                    'provinsi_sebelumnya',
                    'kartu_keluarga_tujuan_id',
                    'nomor_surat_pindah',
                    'jenis_tujuan_kk',
                    'alasan',
                    'status',
                    'catatan',
                    'created_at',
                ]),
            ...app(\App\Services\StatistikKependudukanService::class)->indexPayload('penduduk-datang', $request),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('penduduk-datang/form', [
            'pendudukDatang' => null,
            'penduduk' => Penduduk::query()->orderBy('nama_lengkap')->get(['id', 'nik', 'nama_lengkap', 'jenis_kelamin', 'desa_id', 'dusun_id', 'rw_id', 'rt_id']),
            'kartuKeluargas' => KartuKeluarga::query()->with(['desa:id,nama_desa', 'dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'anggotaKeluarga' => fn ($query) => $query->where('aktif', true)->with('penduduk:id,nama_lengkap')])->orderBy('nomor_kk')->get(['id', 'nomor_kk', 'alamat', 'desa_id', 'dusun_id', 'rw_id', 'rt_id']),
            'desas' => \App\Models\Desa::query()->orderBy('nama_desa')->get(['id', 'nama_desa']),
            'dusuns' => \App\Models\Dusun::query()->orderBy('nama')->get(['id', 'desa_id', 'nama']),
            'rws' => \App\Models\RW::query()->orderBy('nomor')->get(['id', 'dusun_id', 'nomor']),
            'rts' => \App\Models\RT::query()->orderBy('nomor')->get(['id', 'rw_id', 'nomor']),
            'pendidikans' => Pendidikan::query()->orderBy('nama')->get(['id', 'nama']),
            'pekerjaans' => Pekerjaan::query()->orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tanggal_datang' => ['required', 'date'], 'nomor_surat_pindah' => ['nullable', 'string', 'max:255'],
            'alamat_sebelumnya' => ['nullable', 'string'], 'desa_sebelumnya' => ['nullable', 'string', 'max:255'],
            'kecamatan_sebelumnya' => ['nullable', 'string', 'max:255'], 'kabupaten_sebelumnya' => ['nullable', 'string', 'max:255'],
            'provinsi_sebelumnya' => ['nullable', 'string', 'max:255'], 'alasan' => ['nullable', 'string', 'max:255'], 'catatan' => ['nullable', 'string'],
            'penduduk' => ['required', 'array'], 'penduduk.nik' => ['required', 'string', 'max:16', 'unique:penduduks,nik'],
            'penduduk.nama_lengkap' => ['required', 'string', 'max:255'], 'penduduk.tempat_lahir' => ['nullable', 'string', 'max:255'],
            'penduduk.tanggal_lahir' => ['nullable', 'date'], 'penduduk.jenis_kelamin' => ['required', 'in:Laki-laki,Perempuan'],
            'penduduk.agama' => ['nullable', 'string', 'max:50'], 'penduduk.golongan_darah' => ['nullable', 'in:A,B,AB,O'],
            'penduduk.status_perkawinan' => ['nullable', 'string', 'max:50'], 'penduduk.pendidikan_id' => ['nullable', 'exists:pendidikans,id'],
            'penduduk.pekerjaan_id' => ['nullable', 'exists:pekerjaans,id'], 'penduduk.kewarganegaraan' => ['nullable', 'string', 'max:50'],
            'penduduk.nama_ayah' => ['nullable', 'string', 'max:255'], 'penduduk.nama_ibu' => ['nullable', 'string', 'max:255'],
            'penduduk.telepon' => ['nullable', 'string', 'max:50'], 'penduduk.email' => ['nullable', 'email', 'max:255'],
            'penduduk.alamat' => ['required', 'string'], 'penduduk.dusun_id' => ['required', new \App\Rules\WilayahExists(\App\Models\Dusun::class)],
            'penduduk.rw_id' => ['required', new \App\Rules\WilayahExists(\App\Models\RW::class)], 'penduduk.rt_id' => ['required', new \App\Rules\WilayahExists(\App\Models\RT::class)],
        ]);
        $arrival = app(\App\Services\IndividualMigrationService::class)->arrival($validated, auth()->user());
        return to_route('penduduk-datang.show', $arrival)->with('success', 'Penduduk masuk berhasil ditambahkan.');

        /* Legacy family flow retained below only for historical reference. */
        $payload = $request->all();

        if (($payload['jenis_kedatangan'] ?? null) === null && $request->has('penduduk_id')) {
            $payload['jenis_kedatangan'] = 'individu';
            $payload['jenis_tujuan_kk'] = $payload['jenis_tujuan_kk'] ?? 'gabung_kk';
            $payload['penduduk_id'] = $request->input('penduduk_id');

            $pendingPenduduk = [];
            foreach (['nik', 'nama_lengkap', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama', 'status_perkawinan', 'kewarganegaraan', 'nama_ayah', 'nama_ibu', 'telepon'] as $field) {
                $value = $request->input($field);
                if (! blank($value)) {
                    $pendingPenduduk[$field] = $value;
                }
            }

            $payload['penduduk'] = $pendingPenduduk;
            $payload['hubungan_keluarga'] = $request->input('hubungan_keluarga', 'Anggota');
        }

        if (($payload['jenis_tujuan_kk'] ?? null) === null && ! empty($payload['kartu_keluarga_tujuan_id'])) {
            $payload['jenis_tujuan_kk'] = 'gabung_kk';
        }

        $request->merge($payload);

        $validated = $request->validate([
            'jenis_kedatangan' => ['required', 'in:individu,keluarga'],
            'tanggal_datang' => ['required', 'date'],
            'jenis_tujuan_kk' => ['required', 'in:gabung_kk,kk_baru'],
            'penduduk_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Penduduk::class)],
            'kartu_keluarga_tujuan_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\KartuKeluarga::class)],
            'nomor_surat_pindah' => ['nullable', 'string', 'max:255'],
            'alamat_sebelumnya' => ['nullable', 'string'],
            'desa_sebelumnya' => ['nullable', 'string', 'max:255'],
            'kecamatan_sebelumnya' => ['nullable', 'string', 'max:255'],
            'kabupaten_sebelumnya' => ['nullable', 'string', 'max:255'],
            'provinsi_sebelumnya' => ['nullable', 'string', 'max:255'],
            'alasan' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string'],
            'penduduk' => ['nullable', 'array'],
            'penduduk.nik' => ['nullable', 'string', 'max:16'],
            'penduduk.nama_lengkap' => ['nullable', 'string', 'max:255'],
            'penduduk.tempat_lahir' => ['nullable', 'string', 'max:255'],
            'penduduk.tanggal_lahir' => ['nullable', 'date'],
            'penduduk.jenis_kelamin' => ['nullable', 'in:Laki-laki,Perempuan'],
            'penduduk.agama' => ['nullable', 'string', 'max:50'],
            'penduduk.golongan_darah' => ['nullable', 'in:A,B,AB,O'],
            'penduduk.status_perkawinan' => ['nullable', 'string', 'max:50'],
            'penduduk.pendidikan_id' => ['nullable', 'exists:pendidikans,id'],
            'penduduk.pekerjaan_id' => ['nullable', 'exists:pekerjaans,id'],
            'penduduk.kewarganegaraan' => ['nullable', 'string', 'max:50'],
            'penduduk.nama_ayah' => ['nullable', 'string', 'max:255'],
            'penduduk.nama_ibu' => ['nullable', 'string', 'max:255'],
            'penduduk.telepon' => ['nullable', 'string', 'max:50'],
            'penduduk.email' => ['nullable', 'email', 'max:255'],
            'penduduk.alamat' => ['nullable', 'string'],
            'penduduk.dusun_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Dusun::class)],
            'penduduk.rw_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RW::class)],
            'penduduk.rt_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RT::class)],
            'penduduk.status_penduduk' => ['nullable', 'in:aktif,pindah,meninggal,sementara'],
            'penduduk.masih_hidup' => ['nullable', 'boolean'],
            'hubungan_keluarga' => ['nullable', 'string', 'max:50'],
            'anggota' => ['nullable', 'array'],
            'anggota.*.nik' => ['required_with:anggota', 'string', 'max:16'],
            'anggota.*.nama_lengkap' => ['required_with:anggota', 'string', 'max:255'],
            'anggota.*.tempat_lahir' => ['nullable', 'string', 'max:255'],
            'anggota.*.tanggal_lahir' => ['nullable', 'date'],
            'anggota.*.jenis_kelamin' => ['required_with:anggota', 'in:Laki-laki,Perempuan'],
            'anggota.*.agama' => ['nullable', 'string', 'max:50'],
            'anggota.*.golongan_darah' => ['nullable', 'in:A,B,AB,O'],
            'anggota.*.status_perkawinan' => ['nullable', 'string', 'max:50'],
            'anggota.*.pendidikan_id' => ['nullable', 'exists:pendidikans,id'],
            'anggota.*.pekerjaan_id' => ['nullable', 'exists:pekerjaans,id'],
            'anggota.*.kewarganegaraan' => ['nullable', 'string', 'max:50'],
            'anggota.*.nama_ayah' => ['nullable', 'string', 'max:255'],
            'anggota.*.nama_ibu' => ['nullable', 'string', 'max:255'],
            'anggota.*.telepon' => ['nullable', 'string', 'max:50'],
            'anggota.*.email' => ['nullable', 'email', 'max:255'],
            'anggota.*.alamat' => ['nullable', 'string'],
            'anggota.*.dusun_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Dusun::class)],
            'anggota.*.rw_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RW::class)],
            'anggota.*.rt_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RT::class)],
            'anggota.*.status_penduduk' => ['nullable', 'in:aktif,pindah,meninggal,sementara'],
            'anggota.*.masih_hidup' => ['nullable', 'boolean'],
            'anggota.*.hubungan_keluarga' => ['required_with:anggota', 'string', 'max:50'],
            'kk_baru' => ['nullable', 'array'],
            'kk_baru.nomor_kk' => ['nullable', 'string', 'max:16'],
            'kk_baru.dusun_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Dusun::class)],
            'kk_baru.rw_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RW::class)],
            'kk_baru.rt_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RT::class)],
            'kk_baru.alamat' => ['nullable', 'string'],
            'kk_baru.kode_pos' => ['nullable', 'string', 'max:10'],
            'kk_baru.tanggal_diterbitkan' => ['nullable', 'date'],
            'status' => ['nullable', 'in:draft,selesai,dibatalkan'],
        ]);

        $validated['status'] = $validated['status'] ?? 'selesai';

        $anggota = app(PendudukDatangService::class)->store($validated, auth()->user());

        return to_route('penduduk-datang.show', $anggota)->with('success', 'Penduduk datang berhasil ditambahkan.');
    }

    public function show(PendudukDatang $pendudukDatang): Response
    {
        return Inertia::render('penduduk-datang/show', [
            'pendudukDatang' => $pendudukDatang->load(['anggotaKedatangan.penduduk:id,nik,nama_lengkap,jenis_kelamin,status_penduduk', 'kartuKeluargaTujuan:id,nomor_kk,alamat,dusun_id,rw_id,rt_id']),
        ]);
    }

    public function edit(PendudukDatang $pendudukDatang): Response
    {
        return Inertia::render('penduduk-datang/form', [
            'pendudukDatang' => $pendudukDatang,
            'penduduk' => Penduduk::query()->orderBy('nama_lengkap')->get(['id', 'nik', 'nama_lengkap', 'jenis_kelamin', 'desa_id', 'dusun_id', 'rw_id', 'rt_id']),
            'kartuKeluargas' => KartuKeluarga::query()->with(['desa:id,nama_desa', 'dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'anggotaKeluarga' => fn ($query) => $query->where('aktif', true)->with('penduduk:id,nama_lengkap')])->orderBy('nomor_kk')->get(['id', 'nomor_kk', 'alamat', 'desa_id', 'dusun_id', 'rw_id', 'rt_id']),
            'desas' => \App\Models\Desa::query()->orderBy('nama_desa')->get(['id', 'nama_desa']),
            'dusuns' => \App\Models\Dusun::query()->orderBy('nama')->get(['id', 'desa_id', 'nama']),
            'rws' => \App\Models\RW::query()->orderBy('nomor')->get(['id', 'dusun_id', 'nomor']),
            'rts' => \App\Models\RT::query()->orderBy('nomor')->get(['id', 'rw_id', 'nomor']),
            'pendidikans' => Pendidikan::query()->orderBy('nama')->get(['id', 'nama']),
            'pekerjaans' => Pekerjaan::query()->orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function update(Request $request, PendudukDatang $pendudukDatang): RedirectResponse
    {
        $validated = $request->validate([
            'jenis_kedatangan' => ['nullable', 'in:individu,keluarga'],
            'tanggal_datang' => ['nullable', 'date'],
            'jenis_tujuan_kk' => ['nullable', 'in:gabung_kk,kk_baru'],
            'kartu_keluarga_tujuan_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\KartuKeluarga::class)],
            'nomor_surat_pindah' => ['nullable', 'string', 'max:255'],
            'alamat_sebelumnya' => ['nullable', 'string'],
            'desa_sebelumnya' => ['nullable', 'string', 'max:255'],
            'kecamatan_sebelumnya' => ['nullable', 'string', 'max:255'],
            'kabupaten_sebelumnya' => ['nullable', 'string', 'max:255'],
            'provinsi_sebelumnya' => ['nullable', 'string', 'max:255'],
            'alasan' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:draft,selesai,dibatalkan'],
            'catatan' => ['nullable', 'string'],
        ]);

        $validated['status'] = $validated['status'] ?? $pendudukDatang->status ?? 'selesai';
        $validated['dibuat_oleh'] = auth()->id() ?? $pendudukDatang->dibuat_oleh;

        $pendudukDatang->update($validated);

        return to_route('penduduk-datang.show', $pendudukDatang)->with('success', 'Penduduk datang berhasil diperbarui.');
    }

    public function destroy(PendudukDatang $pendudukDatang): RedirectResponse
    {
        $pendudukDatang->delete();

        return to_route('penduduk-datang.index')->with('success', 'Penduduk datang berhasil dihapus.');
    }
}
