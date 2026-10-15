<?php

namespace App\Http\Controllers;

use App\Models\KartuKeluarga;
use App\Models\Penduduk;
use App\Models\PenerimaBantuan;
use App\Models\ProgramBantuan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PenerimaBantuanController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('penerima-bantuan/index', [
            'penerimas' => PenerimaBantuan::with([
                'program:id,nama',
                'penduduk:id,nama_lengkap,nik',
                'kartuKeluarga:id,nomor_kk',
            ])->latest()->get(),
            ...app(\App\Services\StatistikKependudukanService::class)->indexPayload('penerima-bantuan', $request),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('penerima-bantuan/form', [
            'programs' => ProgramBantuan::where('status', 'aktif')->orderBy('nama')->get(['id', 'nama']),
            'penduduks' => Penduduk::orderBy('nama_lengkap')->get(['id', 'nama_lengkap', 'nik', 'alamat']),
            'kartuKeluargas' => KartuKeluarga::orderBy('nomor_kk')->get(['id', 'nomor_kk', 'alamat']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRegistration($request);

        DB::transaction(function () use ($data): void {
            foreach ($data['penerimas'] as $item) {
                PenerimaBantuan::create([
                    'program_bantuan_id' => $data['program_bantuan_id'],
                    'penduduk_id' => $data['jenis_penerima'] === 'penduduk' ? $item['penerima_id'] : null,
                    'kartu_keluarga_id' => $data['jenis_penerima'] === 'kartu_keluarga' ? $item['penerima_id'] : null,
                    'tanggal_terdaftar' => $item['tanggal_terdaftar'],
                    'status_kelayakan' => 'menunggu',
                    'status_verifikasi' => 'belum_diverifikasi',
                    'catatan' => $item['catatan'] ?? null,
                ]);
            }
        });

        return to_route('penerima-bantuan.index')
            ->with('success', count($data['penerimas']).' penerima berhasil didaftarkan dan menunggu verifikasi.');
    }

    public function show(PenerimaBantuan $penerimaBantuan): Response
    {
        return Inertia::render('penerima-bantuan/show', [
            'penerima' => $penerimaBantuan->load([
                'program',
                'penduduk',
                'kartuKeluarga',
                'penyaluran',
                'verifikator:id,name',
            ]),
        ]);
    }

    public function startVerification(PenerimaBantuan $penerimaBantuan): RedirectResponse
    {
        if ($penerimaBantuan->status_verifikasi !== 'belum_diverifikasi') {
            throw ValidationException::withMessages([
                'status_verifikasi' => 'Hanya penerima yang menunggu verifikasi yang dapat mulai diperiksa.',
            ]);
        }

        $penerimaBantuan->update([
            'status_verifikasi' => 'proses_verifikasi',
            'status_kelayakan' => 'menunggu',
        ]);

        return back()->with('success', 'Proses verifikasi penerima telah dimulai.');
    }

    public function finishVerification(Request $request, PenerimaBantuan $penerimaBantuan): RedirectResponse
    {
        if ($penerimaBantuan->status_verifikasi !== 'proses_verifikasi') {
            throw ValidationException::withMessages([
                'status_verifikasi' => 'Hasil hanya dapat ditetapkan setelah proses verifikasi dimulai.',
            ]);
        }

        $data = $request->validate([
            'status_kelayakan' => ['required', Rule::in(['layak', 'tidak_layak'])],
            'catatan_verifikasi' => [
                Rule::requiredIf($request->input('status_kelayakan') === 'tidak_layak'),
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $penerimaBantuan->update([
            'status_kelayakan' => $data['status_kelayakan'],
            'status_verifikasi' => 'terverifikasi',
            'catatan_verifikasi' => $data['catatan_verifikasi'] ?? null,
            'diverifikasi_oleh' => $request->user()?->id,
            'tanggal_verifikasi' => now(),
        ]);

        return back()->with('success', 'Hasil verifikasi penerima berhasil disimpan.');
    }

    public function destroy(PenerimaBantuan $penerimaBantuan): RedirectResponse
    {
        $penerimaBantuan->delete();

        return to_route('penerima-bantuan.index')->with('success', 'Penerima bantuan berhasil dihapus.');
    }

    private function validateRegistration(Request $request): array
    {
        $recipientTable = $request->input('jenis_penerima') === 'kartu_keluarga'
            ? 'kartu_keluargas'
            : 'penduduks';

        return $request->validate([
            'program_bantuan_id' => ['required', 'exists:program_bantuans,id'],
            'jenis_penerima' => ['required', Rule::in(['penduduk', 'kartu_keluarga'])],
            'penerimas' => ['required', 'array', 'min:1'],
            'penerimas.*.penerima_id' => ['required', 'integer', 'distinct', new \App\Rules\WilayahExists($request->input('jenis_penerima') === 'kartu_keluarga' ? KartuKeluarga::class : Penduduk::class)],
            'penerimas.*.tanggal_terdaftar' => ['required', 'date'],
            'penerimas.*.catatan' => ['nullable', 'string', 'max:2000'],
        ], [
            'penerimas.required' => 'Pilih minimal satu penerima bantuan.',
            'penerimas.min' => 'Pilih minimal satu penerima bantuan.',
        ]);
    }
}
