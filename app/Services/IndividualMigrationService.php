<?php

namespace App\Services;

use App\Models\AnggotaKedatangan;
use App\Models\KedatanganPenduduk;
use App\Models\Penduduk;
use App\Models\PendudukPindah;
use App\Models\PeristiwaKependudukan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IndividualMigrationService
{
    public function arrival(array $data, ?User $actor = null): KedatanganPenduduk
    {
        return DB::transaction(function () use ($data, $actor) {
            $person = $data['penduduk'];
            $person['status_penduduk'] = 'aktif';
            $person['masih_hidup'] = true;
            $penduduk = Penduduk::create($person);
            $arrival = KedatanganPenduduk::create([
                'jenis_kedatangan' => 'individu', 'penduduk_id' => $penduduk->id,
                'tanggal_datang' => $data['tanggal_datang'], 'nomor_surat_pindah' => $data['nomor_surat_pindah'] ?? null,
                'alamat_sebelumnya' => $data['alamat_sebelumnya'] ?? null, 'desa_sebelumnya' => $data['desa_sebelumnya'] ?? null,
                'kecamatan_sebelumnya' => $data['kecamatan_sebelumnya'] ?? null, 'kabupaten_sebelumnya' => $data['kabupaten_sebelumnya'] ?? null,
                'provinsi_sebelumnya' => $data['provinsi_sebelumnya'] ?? null, 'jenis_tujuan_kk' => null,
                'kartu_keluarga_tujuan_id' => null, 'status' => 'selesai', 'alasan' => $data['alasan'] ?? null,
                'catatan' => $data['catatan'] ?? null, 'dibuat_oleh' => $actor?->id,
            ]);
            AnggotaKedatangan::create(['kedatangan_penduduk_id' => $arrival->id, 'penduduk_id' => $penduduk->id, 'hubungan_keluarga' => null]);
            PeristiwaKependudukan::create(['penduduk_id' => $penduduk->id, 'jenis' => 'datang', 'tanggal_peristiwa' => $arrival->tanggal_datang, 'nomor_referensi' => $arrival->nomor_surat_pindah, 'keterangan' => 'Penduduk masuk.', 'dibuat_oleh' => $actor?->id]);
            return $arrival->load('penduduk');
        });
    }

    public function departure(array $data, ?User $actor = null): PendudukPindah
    {
        return DB::transaction(function () use ($data, $actor) {
            $penduduk = Penduduk::query()->lockForUpdate()->findOrFail($data['penduduk_id']);
            if ($penduduk->status_penduduk === 'pindah' || ! $penduduk->masih_hidup) {
                throw ValidationException::withMessages(['penduduk_id' => 'Penduduk ini tidak dapat dicatat pindah lagi.']);
            }
            $move = PendudukPindah::create([
                'penduduk_id' => $penduduk->id, 'jenis_pindah' => 'individu', 'jenis_tujuan_kk' => null,
                'kartu_keluarga_tujuan_id' => null, 'hubungan_keluarga' => null, 'tanggal_pindah' => $data['tanggal_pindah'],
                'alamat_tujuan' => $data['alamat_tujuan'] ?? null, 'desa_tujuan' => $data['desa_tujuan'] ?? null,
                'kecamatan_tujuan' => $data['kecamatan_tujuan'] ?? null, 'kabupaten_tujuan' => $data['kabupaten_tujuan'] ?? null,
                'provinsi_tujuan' => $data['provinsi_tujuan'] ?? null, 'nomor_surat_pindah' => $data['nomor_surat_pindah'] ?? null,
                'alasan' => $data['alasan'] ?? null, 'status' => 'selesai', 'catatan' => $data['catatan'] ?? null, 'dibuat_oleh' => $actor?->id,
            ]);
            $penduduk->update(['status_penduduk' => 'pindah']);
            PeristiwaKependudukan::create(['penduduk_id' => $penduduk->id, 'jenis' => 'pindah', 'tanggal_peristiwa' => $move->tanggal_pindah, 'nomor_referensi' => $move->nomor_surat_pindah, 'keterangan' => 'Penduduk keluar.', 'dibuat_oleh' => $actor?->id]);
            return $move->load('penduduk');
        });
    }
}
