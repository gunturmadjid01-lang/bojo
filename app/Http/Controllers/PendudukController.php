<?php

namespace App\Http\Controllers;

use App\Models\Dusun;
use App\Models\Pekerjaan;
use App\Models\Penduduk;
use App\Models\Pendidikan;
use App\Models\RT;
use App\Models\RW;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PendudukController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('penduduk/index', [
            'penduduks' => Penduduk::query()
                ->with(['dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'pendidikan:id,nama', 'pekerjaan:id,nama'])
                ->latest()
                ->get([
                    'id',
                    'nik',
                    'nama_lengkap',
                    'jenis_kelamin',
                    'tempat_lahir',
                    'tanggal_lahir',
                    'status_perkawinan',
                    'pendidikan_id',
                    'pekerjaan_id',
                    'dusun_id',
                    'rw_id',
                    'rt_id',
                    'status_penduduk',
                    'masih_hidup',
                    'created_at',
                ]),
            ...app(\App\Services\StatistikKependudukanService::class)->indexPayload('penduduk', $request),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('penduduk/form', [
            'penduduk' => null,
            'dusuns' => Dusun::query()->orderBy('nama')->get(['id', 'desa_id', 'nama']),
            'rws' => RW::query()->with('dusun:id,nama')->orderBy('nomor')->get(['id', 'dusun_id', 'nomor']),
            'rts' => RT::query()->with('rw:id,nomor')->orderBy('nomor')->get(['id', 'rw_id', 'nomor']),
            'pendidikans' => Pendidikan::query()->orderBy('nama')->get(['id', 'nama']),
            'pekerjaans' => Pekerjaan::query()->orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nik' => ['required', 'string', 'max:16', 'unique:penduduks,nik'],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'tempat_lahir' => ['nullable', 'string', 'max:255'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['required', 'in:Laki-laki,Perempuan'],
            'agama' => ['nullable', 'string', 'max:30'],
            'golongan_darah' => ['nullable', 'in:A,B,AB,O'],
            'status_perkawinan' => ['nullable', 'in:Belum Kawin,Kawin,Cerai Hidup,Cerai Mati'],
            'pendidikan_id' => ['nullable', 'exists:pendidikans,id'],
            'pekerjaan_id' => ['nullable', 'exists:pekerjaans,id'],
            'kewarganegaraan' => ['required', 'string', 'max:50'],
            'nama_ayah' => ['nullable', 'string', 'max:255'],
            'nama_ibu' => ['nullable', 'string', 'max:255'],
            'telepon' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'alamat' => ['nullable', 'string'],
            'dusun_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Dusun::class)],
            'rw_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RW::class)],
            'rt_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RT::class)],
            'status_penduduk' => ['required', 'in:aktif,pindah,meninggal,sementara'],
            'masih_hidup' => ['boolean'],
            'foto' => ['nullable', 'string', 'max:255'],
        ]);

        Penduduk::create($validated);

        return to_route('penduduk.index')->with('success', 'Penduduk berhasil ditambahkan.');
    }

    public function show(Penduduk $penduduk): Response
    {
        return Inertia::render('penduduk/show', [
            'penduduk' => $penduduk->load(['dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'pendidikan:id,nama', 'pekerjaan:id,nama']),
        ]);
    }

    public function edit(Penduduk $penduduk): Response
    {
        return Inertia::render('penduduk/form', [
            'penduduk' => $penduduk->load(['dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'pendidikan:id,nama', 'pekerjaan:id,nama']),
            'dusuns' => Dusun::query()->orderBy('nama')->get(['id', 'desa_id', 'nama']),
            'rws' => RW::query()->with('dusun:id,nama')->orderBy('nomor')->get(['id', 'dusun_id', 'nomor']),
            'rts' => RT::query()->with('rw:id,nomor')->orderBy('nomor')->get(['id', 'rw_id', 'nomor']),
            'pendidikans' => Pendidikan::query()->orderBy('nama')->get(['id', 'nama']),
            'pekerjaans' => Pekerjaan::query()->orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function update(Request $request, Penduduk $penduduk): RedirectResponse
    {
        $validated = $request->validate([
            'nik' => ['required', 'string', 'max:16', 'unique:penduduks,nik,' . $penduduk->id],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'tempat_lahir' => ['nullable', 'string', 'max:255'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['required', 'in:Laki-laki,Perempuan'],
            'agama' => ['nullable', 'string', 'max:30'],
            'golongan_darah' => ['nullable', 'in:A,B,AB,O'],
            'status_perkawinan' => ['nullable', 'in:Belum Kawin,Kawin,Cerai Hidup,Cerai Mati'],
            'pendidikan_id' => ['nullable', 'exists:pendidikans,id'],
            'pekerjaan_id' => ['nullable', 'exists:pekerjaans,id'],
            'kewarganegaraan' => ['required', 'string', 'max:50'],
            'nama_ayah' => ['nullable', 'string', 'max:255'],
            'nama_ibu' => ['nullable', 'string', 'max:255'],
            'telepon' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'alamat' => ['nullable', 'string'],
            'dusun_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Dusun::class)],
            'rw_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RW::class)],
            'rt_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RT::class)],
            'status_penduduk' => ['required', 'in:aktif,pindah,meninggal,sementara'],
            'masih_hidup' => ['boolean'],
            'foto' => ['nullable', 'string', 'max:255'],
        ]);

        $penduduk->update($validated);

        return to_route('penduduk.show', $penduduk)->with('success', 'Penduduk berhasil diperbarui.');
    }

    public function destroy(Penduduk $penduduk): RedirectResponse
    {
        $penduduk->delete();

        return to_route('penduduk.index')->with('success', 'Penduduk berhasil dihapus.');
    }
}
