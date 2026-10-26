<?php

namespace App\Http\Controllers\Api;

use App\Models\Penduduk;
use App\Rules\WilayahExists;
use App\Services\WilayahAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PendudukUpdateController extends ApiController
{
    public function __invoke(Request $request, string $id, WilayahAccess $access): JsonResponse
    {
        $this->authorizeModule($request, 'penduduk', 'edit');

        return $access->forUser($request->user(), function () use ($request, $id): JsonResponse {
            $penduduk = Penduduk::query()->find((int) $id);
            if (! $penduduk) {
                $existsOutsideScope = Penduduk::withoutGlobalScope('wilayah')->whereKey((int) $id)->exists();
                abort($existsOutsideScope ? 403 : 404, $existsOutsideScope
                    ? 'Penduduk berada di luar wilayah penugasan akun ini.'
                    : 'Data penduduk tidak ditemukan.');
            }

            if ($request->has('masih_hidup')) {
                $request->merge(['masih_hidup' => filter_var($request->input('masih_hidup'), FILTER_VALIDATE_BOOLEAN)]);
            }

            $data = $request->validate([
                'nik' => ['required', 'string', 'max:16', Rule::unique('penduduks', 'nik')->ignore($penduduk->id)],
                'nama_lengkap' => ['required', 'string', 'max:255'],
                'tempat_lahir' => ['nullable', 'string', 'max:255'],
                'tanggal_lahir' => ['nullable', 'date'],
                'jenis_kelamin' => ['required', Rule::in(['Laki-laki', 'Perempuan'])],
                'agama' => ['nullable', 'string', 'max:30'],
                'golongan_darah' => ['nullable', Rule::in(['A', 'B', 'AB', 'O'])],
                'status_perkawinan' => ['nullable', Rule::in(['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'])],
                'pendidikan_id' => ['nullable', 'exists:pendidikans,id'],
                'pekerjaan_id' => ['nullable', 'exists:pekerjaans,id'],
                'kewarganegaraan' => ['required', 'string', 'max:50'],
                'nama_ayah' => ['nullable', 'string', 'max:255'],
                'nama_ibu' => ['nullable', 'string', 'max:255'],
                'telepon' => ['nullable', 'string', 'max:50'],
                'alamat' => ['nullable', 'string'],
                'dusun_id' => ['required', new WilayahExists(\App\Models\Dusun::class)],
                'rw_id' => ['nullable', new WilayahExists(\App\Models\RW::class)],
                'rt_id' => ['nullable', new WilayahExists(\App\Models\RT::class)],
                'status_penduduk' => ['required', Rule::in(['aktif', 'pindah', 'meninggal', 'sementara'])],
                'masih_hidup' => ['required', 'boolean'],
            ]);

            $penduduk->update($data);
            $penduduk->load(['dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'pendidikan:id,nama', 'pekerjaan:id,nama']);

            return response()->json(['data' => $penduduk]);
        });
    }
}
