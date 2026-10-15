<?php

namespace App\Http\Controllers;

use App\Models\PenerimaBantuan;
use App\Models\PenyaluranBantuan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PenyaluranBantuanController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('penyaluran-bantuan/index', ['penyalurans' => PenyaluranBantuan::with(['penerima.program:id,nama', 'penerima.penduduk:id,nama_lengkap'])->latest()->get(), ...app(\App\Services\StatistikKependudukanService::class)->indexPayload('penyaluran-bantuan', $request)]);
    }

    public function create(): Response
    {
        return Inertia::render('penyaluran-bantuan/form', ['penyaluran' => null, 'penerimas' => PenerimaBantuan::with(['program:id,nama', 'penduduk:id,nama_lengkap', 'kartuKeluarga:id,nomor_kk'])->where('status_verifikasi', 'terverifikasi')->where('status_kelayakan', 'layak')->get()]);
    }

    public function store(Request $r): RedirectResponse
    {
        $d = $r->validate(['penerima_bantuan_id' => ['required', new \App\Rules\WilayahExists(\App\Models\PenerimaBantuan::class)], 'periode' => ['required', 'string', 'max:50'], 'tanggal_penyaluran' => ['required', 'date'], 'jumlah' => ['required', 'numeric', 'min:0'], 'status' => ['required', 'in:belum_disalurkan,disalurkan,gagal'], 'diterima_oleh' => ['nullable', 'string', 'max:255'], 'catatan' => ['nullable', 'string']]);
        $eligible = PenerimaBantuan::whereKey($d['penerima_bantuan_id'])->where('status_verifikasi', 'terverifikasi')->where('status_kelayakan', 'layak')->exists();
        if (! $eligible) {
            throw ValidationException::withMessages(['penerima_bantuan_id' => 'Penyaluran hanya dapat dibuat untuk penerima yang dinyatakan layak.']);
        }$d['dibuat_oleh'] = $r->user()?->id;
        PenyaluranBantuan::create($d);

        return to_route('penyaluran-bantuan.index')->with('success', 'Penyaluran bantuan berhasil dicatat.');
    }

    public function show(PenyaluranBantuan $penyaluranBantuan): Response
    {
        return Inertia::render('penyaluran-bantuan/show', ['penyaluran' => $penyaluranBantuan->load(['penerima.program', 'penerima.penduduk', 'penerima.kartuKeluarga'])]);
    }
}
