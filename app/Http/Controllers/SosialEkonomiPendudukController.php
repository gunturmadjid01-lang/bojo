<?php

namespace App\Http\Controllers;

use App\Models\Penduduk;
use App\Models\SosialEkonomiPenduduk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SosialEkonomiPendudukController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('sosial-ekonomi-penduduk/index', [
            'sosialEkonomiPenduduks' => SosialEkonomiPenduduk::query()
                ->with(['penduduk:id,nik,nama_lengkap', 'didataOleh:id,name'])
                ->latest()
                ->get([
                    'id',
                    'penduduk_id',
                    'status_pekerjaan',
                    'penghasilan_bulanan',
                    'kategori_penghasilan',
                    'jaminan_kesehatan',
                    'tanggal_pendataan',
                    'didata_oleh',
                    'created_at',
                ]),
            ...app(\App\Services\StatistikKependudukanService::class)->indexPayload('sosial-ekonomi-penduduk', $request),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('sosial-ekonomi-penduduk/form', [
            'sosialEkonomiPenduduk' => null,
            'penduduks' => Penduduk::query()->orderBy('nama_lengkap')->get(['id', 'nik', 'nama_lengkap']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'penduduk_id' => ['required', new \App\Rules\WilayahExists(\App\Models\Penduduk::class)],
            'status_pekerjaan' => ['nullable', 'string', 'max:255'],
            'penghasilan_bulanan' => ['nullable', 'numeric', 'min:0'],
            'kategori_penghasilan' => ['nullable', 'string', 'max:255'],
            'jaminan_kesehatan' => ['nullable', 'string', 'max:255'],
            'tanggal_pendataan' => ['nullable', 'date'],
        ]);

        $validated['didata_oleh'] = auth()->id();

        SosialEkonomiPenduduk::create($validated);

        return to_route('sosial-ekonomi-penduduk.index')->with('success', 'Sosial ekonomi penduduk berhasil ditambahkan.');
    }

    public function show(SosialEkonomiPenduduk $sosialEkonomiPenduduk): Response
    {
        return Inertia::render('sosial-ekonomi-penduduk/show', [
            'sosialEkonomiPenduduk' => $sosialEkonomiPenduduk->load(['penduduk:id,nik,nama_lengkap', 'didataOleh:id,name']),
        ]);
    }

    public function edit(SosialEkonomiPenduduk $sosialEkonomiPenduduk): Response
    {
        return Inertia::render('sosial-ekonomi-penduduk/form', [
            'sosialEkonomiPenduduk' => $sosialEkonomiPenduduk,
            'penduduks' => Penduduk::query()->orderBy('nama_lengkap')->get(['id', 'nik', 'nama_lengkap']),
        ]);
    }

    public function update(Request $request, SosialEkonomiPenduduk $sosialEkonomiPenduduk): RedirectResponse
    {
        $validated = $request->validate([
            'penduduk_id' => ['required', new \App\Rules\WilayahExists(\App\Models\Penduduk::class)],
            'status_pekerjaan' => ['nullable', 'string', 'max:255'],
            'penghasilan_bulanan' => ['nullable', 'numeric', 'min:0'],
            'kategori_penghasilan' => ['nullable', 'string', 'max:255'],
            'jaminan_kesehatan' => ['nullable', 'string', 'max:255'],
            'tanggal_pendataan' => ['nullable', 'date'],
        ]);

        $validated['didata_oleh'] = auth()->id() ?? $sosialEkonomiPenduduk->didata_oleh;

        $sosialEkonomiPenduduk->update($validated);

        return to_route('sosial-ekonomi-penduduk.show', $sosialEkonomiPenduduk)->with('success', 'Sosial ekonomi penduduk berhasil diperbarui.');
    }

    public function destroy(SosialEkonomiPenduduk $sosialEkonomiPenduduk): RedirectResponse
    {
        $sosialEkonomiPenduduk->delete();

        return to_route('sosial-ekonomi-penduduk.index')->with('success', 'Sosial ekonomi penduduk berhasil dihapus.');
    }
}
