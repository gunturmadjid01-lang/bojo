<?php

namespace App\Http\Controllers;

use App\Models\AnggotaKartuKeluarga;
use App\Models\Kematian;
use App\Models\Penduduk;
use App\Services\KematianService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KematianController extends Controller
{
    public function __construct(private readonly KematianService $kematianService)
    {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('kematian/index', [
            'kematians' => Kematian::query()
                ->with(['penduduk:id,nik,nama_lengkap'])
                ->latest()
                ->get([
                    'id',
                    'penduduk_id',
                    'tanggal_meninggal',
                    'waktu_meninggal',
                    'tempat_meninggal',
                    'penyebab_meninggal',
                    'nomor_surat_kematian',
                    'tanggal_dilaporkan',
                    'catatan',
                    'created_at',
                ]),
            ...app(\App\Services\StatistikKependudukanService::class)->indexPayload('kematian', $request),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('kematian/form', [
            'kematian' => null,
            'penduduk' => Penduduk::query()
                ->with(['dusun:id,nama,desa_id', 'rw:id,nomor', 'rt:id,nomor', 'anggotaKartuKeluarga' => fn ($query) => $query->where('aktif', true)->latest('id')])
                ->where('status_penduduk', 'aktif')
                ->where('masih_hidup', true)
                ->orderBy('nama_lengkap')
                ->get(['id', 'nik', 'nama_lengkap', 'jenis_kelamin', 'status_penduduk', 'masih_hidup', 'tanggal_lahir', 'dusun_id', 'rw_id', 'rt_id', 'alamat']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'penduduk_id' => ['required', new \App\Rules\WilayahExists(\App\Models\Penduduk::class)],
            'tanggal_meninggal' => ['required', 'date'],
            'waktu_meninggal' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'tempat_meninggal' => ['nullable', 'string', 'max:255'],
            'penyebab_meninggal' => ['nullable', 'string', 'max:255'],
            'nomor_surat_kematian' => ['nullable', 'string', 'max:255'],
            'tanggal_dilaporkan' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string'],
        ], [
            'penduduk_id.required' => 'Penduduk wajib dipilih.',
            'tanggal_meninggal.required' => 'Tanggal meninggal wajib diisi.',
            'tanggal_meninggal.date' => 'Format tanggal meninggal tidak valid.',
            'waktu_meninggal.regex' => 'Format waktu meninggal harus HH:MM atau HH:MM:SS.',
            'penduduk_id.exists' => 'Penduduk yang dipilih tidak ditemukan.',
        ]);

        $this->kematianService->store($validated, $request->user());

        return to_route('kematian.index')->with('success', 'Kematian berhasil ditambahkan.');
    }

    public function show(Kematian $kematian): Response
    {
        return Inertia::render('kematian/show', [
            'kematian' => $kematian->load(['penduduk:id,nik,nama_lengkap']),
        ]);
    }

    public function edit(Kematian $kematian): Response
    {
        return Inertia::render('kematian/form', [
            'kematian' => $kematian,
            'penduduk' => Penduduk::query()
                ->with(['dusun:id,nama,desa_id', 'rw:id,nomor', 'rt:id,nomor', 'anggotaKartuKeluarga' => fn ($query) => $query->where('aktif', true)->latest('id')])
                ->where('status_penduduk', 'aktif')
                ->where('masih_hidup', true)
                ->orderBy('nama_lengkap')
                ->get(['id', 'nik', 'nama_lengkap', 'jenis_kelamin', 'status_penduduk', 'masih_hidup', 'tanggal_lahir', 'dusun_id', 'rw_id', 'rt_id', 'alamat']),
        ]);
    }

    public function update(Request $request, Kematian $kematian): RedirectResponse
    {
        $validated = $request->validate([
            'penduduk_id' => ['required', new \App\Rules\WilayahExists(\App\Models\Penduduk::class)],
            'tanggal_meninggal' => ['required', 'date'],
            'waktu_meninggal' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'tempat_meninggal' => ['nullable', 'string', 'max:255'],
            'penyebab_meninggal' => ['nullable', 'string', 'max:255'],
            'nomor_surat_kematian' => ['nullable', 'string', 'max:255'],
            'tanggal_dilaporkan' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string'],
        ]);

        $this->kematianService->update($kematian, $validated, $request->user());

        return to_route('kematian.show', $kematian)->with('success', 'Kematian berhasil diperbarui.');
    }

    public function destroy(Kematian $kematian): RedirectResponse
    {
        $kematian->delete();

        return to_route('kematian.index')->with('success', 'Kematian berhasil dihapus.');
    }
}
