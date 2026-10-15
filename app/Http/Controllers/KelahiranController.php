<?php

namespace App\Http\Controllers;

use App\Models\KartuKeluarga;
use App\Models\Kelahiran;
use App\Models\Penduduk;
use App\Models\PenugasanPerangkat;
use App\Services\KelahiranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KelahiranController extends Controller
{
    public function __construct(private readonly KelahiranService $kelahiranService)
    {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('kelahiran/index', [
            'kelahirans' => Kelahiran::query()
                ->with(['penduduk:id,nik,nama_lengkap', 'kartuKeluarga:id,nomor_kk', 'ayah:id,nik,nama_lengkap', 'ibu:id,nik,nama_lengkap'])
                ->latest()
                ->get([
                    'id',
                    'penduduk_id',
                    'kartu_keluarga_id',
                    'ayah_id',
                    'ibu_id',
                    'nomor_akta_kelahiran',
                    'tempat_lahir',
                    'tanggal_lahir',
                    'anak_ke',
                    'berat_lahir',
                    'panjang_lahir',
                    'tanggal_dilaporkan',
                    'catatan',
                    'created_at',
                ]),
            ...app(\App\Services\StatistikKependudukanService::class)->indexPayload('kelahiran', $request),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('kelahiran/form', [
            'kelahiran' => null,
            'penduduk' => Penduduk::query()
                ->with(['anggotaKartuKeluarga' => fn ($query) => $query->where('aktif', true)->select(['id', 'penduduk_id', 'kartu_keluarga_id', 'hubungan_keluarga', 'aktif'])])
                ->orderBy('nama_lengkap')
                ->get(['id', 'nik', 'nama_lengkap', 'jenis_kelamin', 'status_penduduk', 'masih_hidup']),
            'kartuKeluargas' => KartuKeluarga::query()
                ->with(['desa:id,nama_desa', 'dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'anggotaKeluarga' => fn ($query) => $query->where('aktif', true)->with('penduduk:id,nama_lengkap')])
                ->orderBy('nomor_kk')
                ->get(['id', 'nomor_kk', 'desa_id', 'dusun_id', 'rw_id', 'rt_id', 'alamat', 'status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'penduduk_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Penduduk::class)],
            'kartu_keluarga_id' => ['required', new \App\Rules\WilayahExists(\App\Models\KartuKeluarga::class)],
            'ayah_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Penduduk::class)],
            'ibu_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Penduduk::class)],
            'nama_lengkap' => ['nullable', 'required_without:penduduk_id', 'string', 'max:255'],
            'nik' => ['nullable', 'string', 'max:16', 'unique:penduduks,nik'],
            'jenis_kelamin' => ['nullable', 'required_without:penduduk_id', 'in:Laki-laki,Perempuan'],
            'tempat_lahir' => ['nullable', 'required_without:penduduk_id', 'string', 'max:255'],
            'tanggal_lahir' => ['nullable', 'required_without:penduduk_id', 'date'],
            'waktu_lahir' => ['nullable', 'date_format:H:i:s'],
            'golongan_darah' => ['nullable', 'in:A,B,AB,O'],
            'status_perkawinan' => ['nullable', 'in:Belum Kawin,Kawin,Cerai Hidup,Cerai Mati'],
            'kewarganegaraan' => ['nullable', 'string', 'max:50'],
            'nomor_akta_kelahiran' => ['nullable', 'string', 'max:255'],
            'tanggal_akta_kelahiran' => ['nullable', 'date'],
            'anak_ke' => ['nullable', 'integer', 'min:1'],
            'berat_lahir' => ['nullable', 'numeric', 'min:0'],
            'panjang_lahir' => ['nullable', 'numeric', 'min:0'],
            'tanggal_dilaporkan' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string'],
        ], [
            'kartu_keluarga_id.required' => 'Kartu Keluarga wajib dipilih.',
            'nama_lengkap.required_without' => 'Nama lengkap bayi wajib diisi.',
            'jenis_kelamin.required_without' => 'Jenis kelamin bayi wajib dipilih.',
            'tempat_lahir.required_without' => 'Tempat lahir bayi wajib diisi.',
            'tanggal_lahir.required_without' => 'Tanggal lahir bayi wajib diisi.',
        ]);

        $this->kelahiranService->store($validated, auth()->user());

        return to_route('kelahiran.index')->with('success', 'Kelahiran berhasil ditambahkan.');
    }

    public function show(Kelahiran $kelahiran): Response
    {
        return Inertia::render('kelahiran/show', [
            'kelahiran' => $kelahiran->load(['penduduk:id,nik,nama_lengkap,jenis_kelamin,tempat_lahir,tanggal_lahir', 'kartuKeluarga:id,nomor_kk,alamat,dusun_id,rw_id,rt_id', 'kartuKeluarga.dusun:id,nama', 'kartuKeluarga.rw:id,nomor', 'kartuKeluarga.rt:id,nomor', 'ayah:id,nik,nama_lengkap', 'ibu:id,nik,nama_lengkap']),
        ]);
    }

    public function edit(Kelahiran $kelahiran): Response
    {
        return Inertia::render('kelahiran/form', [
            'kelahiran' => $kelahiran->load(['penduduk:id,nik,nama_lengkap,jenis_kelamin,tempat_lahir,tanggal_lahir', 'kartuKeluarga:id,nomor_kk,alamat,dusun_id,rw_id,rt_id', 'ayah:id,nik,nama_lengkap', 'ibu:id,nik,nama_lengkap']),
            'penduduk' => Penduduk::query()
                ->with(['anggotaKartuKeluarga' => fn ($query) => $query->where('aktif', true)->select(['id', 'penduduk_id', 'kartu_keluarga_id', 'hubungan_keluarga', 'aktif'])])
                ->orderBy('nama_lengkap')
                ->get(['id', 'nik', 'nama_lengkap', 'jenis_kelamin', 'status_penduduk', 'masih_hidup']),
            'kartuKeluargas' => KartuKeluarga::query()->with(['desa:id,nama_desa', 'dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'anggotaKeluarga' => fn ($query) => $query->where('aktif', true)->with('penduduk:id,nama_lengkap')])->orderBy('nomor_kk')->get(['id', 'nomor_kk', 'desa_id', 'dusun_id', 'rw_id', 'rt_id', 'alamat', 'status']),
        ]);
    }

    public function update(Request $request, Kelahiran $kelahiran): RedirectResponse
    {
        $validated = $request->validate([
            'kartu_keluarga_id' => ['required', new \App\Rules\WilayahExists(\App\Models\KartuKeluarga::class)],
            'ayah_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Penduduk::class)],
            'ibu_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Penduduk::class)],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'nik' => ['nullable', 'string', 'max:16', 'unique:penduduks,nik,' . $kelahiran->penduduk_id],
            'jenis_kelamin' => ['required', 'in:Laki-laki,Perempuan'],
            'tempat_lahir' => ['required', 'string', 'max:255'],
            'tanggal_lahir' => ['required', 'date'],
            'waktu_lahir' => ['nullable', 'date_format:H:i:s'],
            'golongan_darah' => ['nullable', 'in:A,B,AB,O'],
            'status_perkawinan' => ['nullable', 'in:Belum Kawin,Kawin,Cerai Hidup,Cerai Mati'],
            'kewarganegaraan' => ['nullable', 'string', 'max:50'],
            'nomor_akta_kelahiran' => ['nullable', 'string', 'max:255'],
            'tanggal_akta_kelahiran' => ['nullable', 'date'],
            'anak_ke' => ['nullable', 'integer', 'min:1'],
            'berat_lahir' => ['nullable', 'numeric', 'min:0'],
            'panjang_lahir' => ['nullable', 'numeric', 'min:0'],
            'tanggal_dilaporkan' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string'],
        ]);

        $penduduk = $kelahiran->penduduk;
        $penduduk->fill([
            'nama_lengkap' => $validated['nama_lengkap'],
            'nik' => $validated['nik'] ?? null,
            'jenis_kelamin' => $validated['jenis_kelamin'],
            'tempat_lahir' => $validated['tempat_lahir'],
            'tanggal_lahir' => $validated['tanggal_lahir'],
            'golongan_darah' => $validated['golongan_darah'] ?? null,
            'status_perkawinan' => $validated['status_perkawinan'] ?? $penduduk->status_perkawinan,
            'kewarganegaraan' => $validated['kewarganegaraan'] ?? $penduduk->kewarganegaraan,
            'nama_ayah' => $kelahiran->ayah?->nama_lengkap,
            'nama_ibu' => $kelahiran->ibu?->nama_lengkap,
            'alamat' => $kelahiran->kartuKeluarga?->alamat,
            'dusun_id' => $kelahiran->kartuKeluarga?->dusun_id,
            'rw_id' => $kelahiran->kartuKeluarga?->rw_id,
            'rt_id' => $kelahiran->kartuKeluarga?->rt_id,
        ]);
        $penduduk->save();

        $kelahiran->update([
            'kartu_keluarga_id' => $validated['kartu_keluarga_id'],
            'ayah_id' => $validated['ayah_id'] ?? null,
            'ibu_id' => $validated['ibu_id'] ?? null,
            'nomor_akta_kelahiran' => $validated['nomor_akta_kelahiran'] ?? null,
            'tempat_lahir' => $validated['tempat_lahir'],
            'tanggal_lahir' => $validated['tanggal_lahir'],
            'waktu_lahir' => $validated['waktu_lahir'] ?? null,
            'anak_ke' => $validated['anak_ke'] ?? null,
            'berat_lahir' => $validated['berat_lahir'] ?? null,
            'panjang_lahir' => $validated['panjang_lahir'] ?? null,
            'tanggal_akta_kelahiran' => $validated['tanggal_akta_kelahiran'] ?? null,
            'tanggal_dilaporkan' => $validated['tanggal_dilaporkan'] ?? now()->toDateString(),
            'catatan' => $validated['catatan'] ?? null,
            'dibuat_oleh' => auth()->id() ?? $kelahiran->dibuat_oleh,
        ]);

        return to_route('kelahiran.show', $kelahiran)->with('success', 'Kelahiran berhasil diperbarui.');
    }

    public function destroy(Kelahiran $kelahiran): RedirectResponse
    {
        $kelahiran->delete();

        return to_route('kelahiran.index')->with('success', 'Kelahiran berhasil dihapus.');
    }
}
