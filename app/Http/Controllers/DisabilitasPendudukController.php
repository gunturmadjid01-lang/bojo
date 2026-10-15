<?php

namespace App\Http\Controllers;

use App\Models\Disabilitas;
use App\Models\DisabilitasPenduduk;
use App\Models\Penduduk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DisabilitasPendudukController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('disabilitas-penduduk/index', [
            ...app(\App\Services\StatistikKependudukanService::class)->indexPayload('disabilitas-penduduk', $request),
            'disabilitasPenduduks' => DisabilitasPenduduk::query()
                ->with(['penduduk:id,nik,nama_lengkap', 'jenisDisabilitas:id,nama'])
                ->latest()
                ->get([
                    'id',
                    'penduduk_id',
                    'jenis_disabilitas_id',
                    'tingkat',
                    'keterangan',
                    'created_at',
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('disabilitas-penduduk/form', [
            'disabilitasPenduduk' => null,
            'penduduks' => Penduduk::query()->orderBy('nama_lengkap')->get(['id', 'nik', 'nama_lengkap']),
            'disabilitas' => Disabilitas::query()->where('aktif', true)->orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'penduduk_id' => ['required', new \App\Rules\WilayahExists(\App\Models\Penduduk::class)],
            'jenis_disabilitas_id' => ['required', 'exists:disabilitas,id'],
            'tingkat' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
        ]);

        DisabilitasPenduduk::create($validated);

        return to_route('disabilitas-penduduk.index')->with('success', 'Data disabilitas penduduk berhasil ditambahkan.');
    }

    public function show(DisabilitasPenduduk $disabilitasPenduduk): Response
    {
        return Inertia::render('disabilitas-penduduk/show', [
            'disabilitasPenduduk' => $disabilitasPenduduk->load(['penduduk:id,nik,nama_lengkap', 'jenisDisabilitas:id,nama,keterangan']),
        ]);
    }

    public function edit(DisabilitasPenduduk $disabilitasPenduduk): Response
    {
        return Inertia::render('disabilitas-penduduk/form', [
            'disabilitasPenduduk' => $disabilitasPenduduk,
            'penduduks' => Penduduk::query()->orderBy('nama_lengkap')->get(['id', 'nik', 'nama_lengkap']),
            'disabilitas' => Disabilitas::query()->where('aktif', true)->orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function update(Request $request, DisabilitasPenduduk $disabilitasPenduduk): RedirectResponse
    {
        $validated = $request->validate([
            'penduduk_id' => ['required', new \App\Rules\WilayahExists(\App\Models\Penduduk::class)],
            'jenis_disabilitas_id' => ['required', 'exists:disabilitas,id'],
            'tingkat' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $disabilitasPenduduk->update($validated);

        return to_route('disabilitas-penduduk.show', $disabilitasPenduduk)->with('success', 'Data disabilitas penduduk berhasil diperbarui.');
    }

    public function destroy(DisabilitasPenduduk $disabilitasPenduduk): RedirectResponse
    {
        $disabilitasPenduduk->delete();

        return to_route('disabilitas-penduduk.index')->with('success', 'Data disabilitas penduduk berhasil dihapus.');
    }
}
