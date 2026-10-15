<?php

namespace App\Http\Controllers;

use App\Models\ProgramBantuan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProgramBantuanController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('program-bantuan/index', [
            'programBantuan' => ProgramBantuan::query()->latest()->get([
                'id', 'kode', 'nama', 'sumber', 'tahun', 'tanggal_mulai', 'tanggal_selesai', 'anggaran', 'status', 'keterangan', 'created_at'
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('program-bantuan/form', [
            'programBantuan' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'sumber' => ['nullable', 'string', 'max:255'],
            'tahun' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'anggaran' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:aktif,nonaktif,selesai,draft'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $validated['kode'] = $this->generateKode();

        ProgramBantuan::create($validated);

        return to_route('program-bantuan.index')->with('success', 'Program Bantuan berhasil ditambahkan.');
    }

    public function show(ProgramBantuan $programBantuan): Response
    {
        return Inertia::render('program-bantuan/show', [
            'programBantuan' => $programBantuan,
        ]);
    }

    public function edit(ProgramBantuan $programBantuan): Response
    {
        return Inertia::render('program-bantuan/form', [
            'programBantuan' => $programBantuan,
        ]);
    }

    public function update(Request $request, ProgramBantuan $programBantuan): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'sumber' => ['nullable', 'string', 'max:255'],
            'tahun' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'anggaran' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:aktif,nonaktif,selesai,draft'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $programBantuan->update($validated);

        return to_route('program-bantuan.show', $programBantuan)->with('success', 'Program Bantuan berhasil diperbarui.');
    }

    public function destroy(ProgramBantuan $programBantuan): RedirectResponse
    {
        $programBantuan->delete();

        return to_route('program-bantuan.index')->with('success', 'Program Bantuan berhasil dihapus.');
    }

    private function generateKode(): string
    {
        $lastCode = ProgramBantuan::query()->max('kode');
        $sequence = 1;

        if (is_string($lastCode) && preg_match('/(\d+)$/', $lastCode, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return 'PB-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
