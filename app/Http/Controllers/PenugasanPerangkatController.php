<?php

namespace App\Http\Controllers;

use App\Models\{Desa, Dusun, JabatanDesa, PenugasanPerangkat, User};
use App\Services\PenugasanPerangkatService;
use Illuminate\Http\{RedirectResponse, Request};
use Inertia\Inertia;
use Inertia\Response;

class PenugasanPerangkatController extends Controller
{
    public function __construct(private PenugasanPerangkatService $service) {}

    public function index(): Response
    {
        $desa = Desa::query()->where('aktif', true)->first();
        $assignments = PenugasanPerangkat::with(['perangkatDesa.user:id,name,username', 'jabatanDesa:id,kode,nama,tingkat', 'desa:id,nama_desa', 'dusun:id,nama'])->aktif()->when($desa, fn ($q) => $q->where('desa_id', $desa->id))->get();
        $dusuns = Dusun::query()->where('aktif', true)->when($desa, fn ($q) => $q->where('desa_id', $desa->id))->orderBy('nama')->get(['id', 'desa_id', 'nama']);
        return Inertia::render('penugasan-perangkat/index', compact('assignments', 'dusuns', 'desa'));
    }

    public function history(): Response
    {
        $assignments = PenugasanPerangkat::with(['perangkatDesa.user:id,name,username', 'jabatanDesa:id,nama,tingkat', 'desa:id,nama_desa', 'dusun:id,nama'])->latest()->paginate(15);
        return Inertia::render('penugasan-perangkat/history', ['assignments' => $assignments, 'jabatans' => JabatanDesa::where('aktif', true)->get(['id', 'nama']), 'dusuns' => Dusun::where('aktif', true)->get(['id', 'nama'])]);
    }

    public function create(): Response
    {
        return Inertia::render('penugasan-perangkat/form', ['jabatans' => JabatanDesa::where('aktif', true)->orderBy('nama')->get(['id', 'kode', 'nama', 'tingkat']), 'users' => User::orderBy('name')->get(['id', 'name', 'username']), 'desas' => Desa::where('aktif', true)->get(['id', 'nama_desa']), 'dusuns' => Dusun::where('aktif', true)->get(['id', 'desa_id', 'nama'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['user_id' => ['required', 'exists:users,id'], 'jabatan_desa_id' => ['required', 'exists:jabatan_desas,id'], 'desa_id' => ['required', new \App\Rules\WilayahExists(\App\Models\Desa::class)], 'dusun_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Dusun::class)], 'tanggal_mulai' => ['required', 'date'], 'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'], 'nomor_sk_pengangkatan' => ['nullable', 'string', 'max:255'], 'tanggal_sk_pengangkatan' => ['nullable', 'date'], 'catatan' => ['nullable', 'string']]);
        $this->service->create($data, $request->user());
        return to_route('penugasan-perangkat.index')->with('success', 'Penugasan perangkat desa berhasil disimpan.');
    }

    public function show(PenugasanPerangkat $penugasanPerangkat): Response
    {
        return Inertia::render('penugasan-perangkat/show', ['assignment' => $penugasanPerangkat->load(['perangkatDesa.user', 'jabatanDesa', 'desa', 'dusun'])]);
    }

    public function replaceForm(PenugasanPerangkat $penugasanPerangkat): Response
    {
        return Inertia::render('penugasan-perangkat/action', ['assignment' => $penugasanPerangkat->load(['perangkatDesa.user', 'jabatanDesa', 'desa', 'dusun']), 'mode' => 'replace', 'users' => User::orderBy('name')->get(['id', 'name', 'username'])]);
    }

    public function endForm(PenugasanPerangkat $penugasanPerangkat): Response
    {
        return Inertia::render('penugasan-perangkat/action', ['assignment' => $penugasanPerangkat->load(['perangkatDesa.user', 'jabatanDesa', 'desa', 'dusun']), 'mode' => 'end', 'users' => []]);
    }

    public function replace(Request $request, PenugasanPerangkat $penugasanPerangkat): RedirectResponse
    {
        $data = $request->validate(['user_id' => ['required', 'exists:users,id'], 'tanggal_selesai_lama' => ['required', 'date'], 'tanggal_mulai' => ['required', 'date', 'after:tanggal_selesai_lama'], 'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'], 'nomor_sk_pengangkatan' => ['nullable', 'string'], 'tanggal_sk_pengangkatan' => ['nullable', 'date'], 'nomor_sk_pemberhentian' => ['nullable', 'string'], 'tanggal_sk_pemberhentian' => ['nullable', 'date'], 'catatan' => ['nullable', 'string']]);
        $this->service->replace($penugasanPerangkat, $data, $request->user());
        return to_route('penugasan-perangkat.index')->with('success', 'Pejabat berhasil diganti dan histori lama disimpan.');
    }

    public function end(Request $request, PenugasanPerangkat $penugasanPerangkat): RedirectResponse
    {
        $data = $request->validate(['tanggal_selesai' => ['required', 'date'], 'nomor_sk_pemberhentian' => ['nullable', 'string'], 'tanggal_sk_pemberhentian' => ['nullable', 'date'], 'catatan' => ['nullable', 'string']]);
        $this->service->end($penugasanPerangkat, $data);
        return to_route('penugasan-perangkat.index')->with('success', 'Penugasan berhasil diakhiri.');
    }
}
