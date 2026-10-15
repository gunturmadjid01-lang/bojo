<?php

namespace App\Services;

use App\Models\AnggotaKartuKeluarga;
use App\Models\AnggotaKedatangan;
use App\Models\Dusun;
use App\Models\KartuKeluarga;
use App\Models\KedatanganPenduduk;
use App\Models\Penduduk;
use App\Models\PeristiwaKependudukan;
use App\Models\RT;
use App\Models\RW;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PendudukDatangService
{
    public function store(array $payload, ?User $actor = null): KedatanganPenduduk
    {
        return DB::transaction(function () use ($payload, $actor) {
            $this->validateBasePayload($payload);

            $anggotaList = $this->normalizeAnggota($payload);
            $this->validateNoDuplicateNiks($anggotaList);
            $this->validateFamilyRules($anggotaList, $payload);

            $kkTujuan = $this->resolveKartuKeluargaTujuan($payload, $anggotaList);
            $resolvedMembers = [];
            foreach ($anggotaList as $member) {
                $existingPenduduk = $this->existingPendudukByNik($member['nik'] ?? null);
                $this->assertCitizenCanBeActivated($existingPenduduk, $member['nik']);
                $resolvedMembers[] = [$member, $this->resolvePendudukForArrival($member, $kkTujuan)];
            }

            $header = KedatanganPenduduk::create([
                'jenis_kedatangan' => $payload['jenis_kedatangan'],
                'tanggal_datang' => $payload['tanggal_datang'],
                'nomor_surat_pindah' => $payload['nomor_surat_pindah'] ?? null,
                'alamat_sebelumnya' => $payload['alamat_sebelumnya'] ?? null,
                'desa_sebelumnya' => $payload['desa_sebelumnya'] ?? null,
                'kecamatan_sebelumnya' => $payload['kecamatan_sebelumnya'] ?? null,
                'kabupaten_sebelumnya' => $payload['kabupaten_sebelumnya'] ?? null,
                'provinsi_sebelumnya' => $payload['provinsi_sebelumnya'] ?? null,
                'jenis_tujuan_kk' => $payload['jenis_tujuan_kk'] ?? 'gabung_kk',
                'kartu_keluarga_tujuan_id' => $kkTujuan?->id,
                'penduduk_id' => ($payload['jenis_kedatangan'] ?? null) === 'individu' ? $resolvedMembers[0][1]->id : null,
                'status' => $payload['status'] ?? 'selesai',
                'alasan' => $payload['alasan'] ?? null,
                'catatan' => $payload['catatan'] ?? null,
                'dibuat_oleh' => $actor?->id,
            ]);

            foreach ($resolvedMembers as [$member, $penduduk]) {
                $this->activateMembership($penduduk, $kkTujuan, $member['hubungan_keluarga'] ?? 'Famili Lain', $header->tanggal_datang);
                AnggotaKedatangan::create([
                    'kedatangan_penduduk_id' => $header->id,
                    'penduduk_id' => $penduduk->id,
                    'hubungan_keluarga' => $member['hubungan_keluarga'] ?? 'Famili Lain',
                ]);

                PeristiwaKependudukan::create([
                    'penduduk_id' => $penduduk->id,
                    'jenis' => 'datang',
                    'tanggal_peristiwa' => $header->tanggal_datang,
                    'nomor_referensi' => $header->nomor_surat_pindah,
                    'keterangan' => 'Penduduk datang ke desa tujuan.',
                    'dibuat_oleh' => $actor?->id,
                ]);
            }

            return $header->fresh(['anggotaKedatangan.penduduk', 'kartuKeluargaTujuan']);
        });
    }

    private function validateBasePayload(array $payload): void
    {
        if (! isset($payload['jenis_kedatangan'])) {
            throw ValidationException::withMessages(['jenis_kedatangan' => 'Jenis kedatangan wajib dipilih.']);
        }

        if (! isset($payload['tanggal_datang'])) {
            throw ValidationException::withMessages(['tanggal_datang' => 'Tanggal datang wajib diisi.']);
        }

        if (! isset($payload['jenis_tujuan_kk'])) {
            throw ValidationException::withMessages(['jenis_tujuan_kk' => 'Tujuan kartu keluarga wajib dipilih.']);
        }

        if (($payload['jenis_kedatangan'] ?? null) === 'keluarga') {
            $anggota = $payload['anggota'] ?? [];
            if (count($anggota) < 1) {
                throw ValidationException::withMessages(['anggota' => 'Minimal satu anggota keluarga wajib diisi.']);
            }
        }
    }

    private function normalizeAnggota(array $payload): array
    {
        $payload['jenis_kedatangan'] ??= ($payload['penduduk_id'] ?? null) ? 'individu' : 'keluarga';
        $payload['jenis_tujuan_kk'] ??= 'gabung_kk';

        if (($payload['jenis_kedatangan'] ?? null) === 'keluarga') {
            $result = [];
            foreach ($payload['anggota'] ?? [] as $item) {
                $result[] = [
                    'nik' => $item['nik'] ?? null,
                    'nama_lengkap' => $item['nama_lengkap'] ?? null,
                    'tempat_lahir' => $item['tempat_lahir'] ?? null,
                    'tanggal_lahir' => $item['tanggal_lahir'] ?? null,
                    'jenis_kelamin' => $item['jenis_kelamin'] ?? null,
                    'agama' => $item['agama'] ?? null,
                    'golongan_darah' => $item['golongan_darah'] ?? null,
                    'status_perkawinan' => $item['status_perkawinan'] ?? null,
                    'pendidikan_id' => $item['pendidikan_id'] ?? null,
                    'pekerjaan_id' => $item['pekerjaan_id'] ?? null,
                    'kewarganegaraan' => $item['kewarganegaraan'] ?? 'WNI',
                    'nama_ayah' => $item['nama_ayah'] ?? null,
                    'nama_ibu' => $item['nama_ibu'] ?? null,
                    'telepon' => $item['telepon'] ?? null,
                    'email' => $item['email'] ?? null,
                    'alamat' => $item['alamat'] ?? null,
                    'dusun_id' => $item['dusun_id'] ?? null,
                    'rw_id' => $item['rw_id'] ?? null,
                    'rt_id' => $item['rt_id'] ?? null,
                    'status_penduduk' => $item['status_penduduk'] ?? 'aktif',
                    'masih_hidup' => $item['masih_hidup'] ?? true,
                    'hubungan_keluarga' => $item['hubungan_keluarga'] ?? 'Famili Lain',
                ];
            }

            return $result;
        }

        if (isset($payload['penduduk_id'])) {
            $penduduk = Penduduk::query()->find($payload['penduduk_id']);
            if ($penduduk) {
                return [[
                    'nik' => $penduduk->nik,
                    'nama_lengkap' => $penduduk->nama_lengkap,
                    'tempat_lahir' => $penduduk->tempat_lahir,
                    'tanggal_lahir' => $penduduk->tanggal_lahir?->toDateString(),
                    'jenis_kelamin' => $penduduk->jenis_kelamin,
                    'agama' => $penduduk->agama,
                    'golongan_darah' => $penduduk->golongan_darah,
                    'status_perkawinan' => $penduduk->status_perkawinan,
                    'pendidikan_id' => $penduduk->pendidikan_id,
                    'pekerjaan_id' => $penduduk->pekerjaan_id,
                    'kewarganegaraan' => $penduduk->kewarganegaraan ?? 'WNI',
                    'nama_ayah' => $penduduk->nama_ayah,
                    'nama_ibu' => $penduduk->nama_ibu,
                    'telepon' => $penduduk->telepon,
                    'email' => $penduduk->email,
                    'alamat' => $penduduk->alamat,
                    'dusun_id' => $penduduk->dusun_id,
                    'rw_id' => $penduduk->rw_id,
                    'rt_id' => $penduduk->rt_id,
                    'status_penduduk' => $penduduk->status_penduduk,
                    'masih_hidup' => $penduduk->masih_hidup,
                    'hubungan_keluarga' => $payload['hubungan_keluarga'] ?? 'Anggota',
                ]];
            }
        }

        $item = $payload['penduduk'] ?? [];
        return [[
            'nik' => $item['nik'] ?? null,
            'nama_lengkap' => $item['nama_lengkap'] ?? null,
            'tempat_lahir' => $item['tempat_lahir'] ?? null,
            'tanggal_lahir' => $item['tanggal_lahir'] ?? null,
            'jenis_kelamin' => $item['jenis_kelamin'] ?? null,
            'agama' => $item['agama'] ?? null,
            'golongan_darah' => $item['golongan_darah'] ?? null,
            'status_perkawinan' => $item['status_perkawinan'] ?? null,
            'pendidikan_id' => $item['pendidikan_id'] ?? null,
            'pekerjaan_id' => $item['pekerjaan_id'] ?? null,
            'kewarganegaraan' => $item['kewarganegaraan'] ?? 'WNI',
            'nama_ayah' => $item['nama_ayah'] ?? null,
            'nama_ibu' => $item['nama_ibu'] ?? null,
            'telepon' => $item['telepon'] ?? null,
            'email' => $item['email'] ?? null,
            'alamat' => $item['alamat'] ?? null,
            'dusun_id' => $item['dusun_id'] ?? null,
            'rw_id' => $item['rw_id'] ?? null,
            'rt_id' => $item['rt_id'] ?? null,
            'status_penduduk' => $item['status_penduduk'] ?? 'aktif',
            'masih_hidup' => $item['masih_hidup'] ?? true,
            'hubungan_keluarga' => $payload['hubungan_keluarga'] ?? 'Famili Lain',
        ]];
    }

    private function validateNoDuplicateNiks(array $anggotaList): void
    {
        $niks = array_filter(array_map(fn ($item) => $item['nik'] ?? null, $anggotaList));
        if (count($niks) !== count(array_unique($niks))) {
            throw ValidationException::withMessages(['anggota' => 'NIK anggota tidak boleh duplikat dalam satu proses kedatangan.']);
        }
    }

    private function validateFamilyRules(array $anggotaList, array $payload): void
    {
        if (($payload['jenis_kedatangan'] ?? null) !== 'keluarga' || ($payload['jenis_tujuan_kk'] ?? null) !== 'kk_baru') {
            return;
        }

        $kepalaKeluargaCount = 0;
        foreach ($anggotaList as $member) {
            if (($member['hubungan_keluarga'] ?? '') === 'Kepala Keluarga') {
                $kepalaKeluargaCount++;
            }
        }

        if ($kepalaKeluargaCount !== 1) {
            throw ValidationException::withMessages(['anggota' => 'Keluarga baru wajib memiliki tepat satu Kepala Keluarga.']);
        }
    }

    private function resolveKartuKeluargaTujuan(array $payload, array $anggotaList): ?KartuKeluarga
    {
        if (($payload['jenis_tujuan_kk'] ?? null) === 'kk_baru') {
            $kkPayload = $payload['kk_baru'] ?? [];
            $nomorKk = $kkPayload['nomor_kk'] ?? null;
            $alamat = $kkPayload['alamat'] ?? null;
            $dusunId = $kkPayload['dusun_id'] ?? null;
            $rwId = $kkPayload['rw_id'] ?? null;
            $rtId = $kkPayload['rt_id'] ?? null;

            if (! $nomorKk || ! $alamat || ! $dusunId || ! $rwId || ! $rtId) {
                throw ValidationException::withMessages(['kk_baru' => 'Data kartu keluarga baru wajib lengkap.']);
            }

            if (KartuKeluarga::query()->where('nomor_kk', $nomorKk)->exists()) {
                throw ValidationException::withMessages(['kk_baru.nomor_kk' => 'Nomor KK sudah terdaftar.']);
            }

            $dusun = Dusun::query()->findOrFail($dusunId);
            $rw = RW::query()->findOrFail($rwId);
            $rt = RT::query()->findOrFail($rtId);

            if ((int) $rw->dusun_id !== (int) $dusunId || (int) $rt->rw_id !== (int) $rwId) {
                throw ValidationException::withMessages(['kk_baru' => 'Dusun, RW, dan RT yang dipilih tidak konsisten.']);
            }

            $head = collect($anggotaList)->first(fn ($item) => ($item['hubungan_keluarga'] ?? '') === 'Kepala Keluarga');
            if (! $head) {
                throw ValidationException::withMessages(['anggota' => 'Kepala keluarga wajib dipilih untuk KK baru.']);
            }

            return KartuKeluarga::query()->create([
                'desa_id' => $dusun->desa_id,
                'dusun_id' => $dusunId,
                'rw_id' => $rwId,
                'rt_id' => $rtId,
                'nomor_kk' => $nomorKk,
                'alamat' => $alamat,
                'kode_pos' => $kkPayload['kode_pos'] ?? null,
                'tanggal_diterbitkan' => $kkPayload['tanggal_diterbitkan'] ?? now()->toDateString(),
                'status' => 'aktif',
            ]);
        }

        $kkId = $payload['kartu_keluarga_tujuan_id'] ?? null;
        if (! $kkId) {
            throw ValidationException::withMessages(['kartu_keluarga_tujuan_id' => 'Kartu keluarga tujuan wajib dipilih.']);
        }

        return KartuKeluarga::query()->findOrFail($kkId);
    }

    private function existingPendudukByNik(?string $nik): ?Penduduk
    {
        $cleanNik = $nik ? preg_replace('/\s+/', '', trim($nik)) : null;
        if (! $cleanNik) {
            throw ValidationException::withMessages(['nik' => 'NIK wajib diisi.']);
        }

        return Penduduk::query()->where('nik', $cleanNik)->first();
    }

    private function resolvePendudukForArrival(array $member, ?KartuKeluarga $kkTujuan): Penduduk
    {
        $nik = $member['nik'] ?? null;
        $cleanNik = $nik ? preg_replace('/\s+/', '', trim($nik)) : null;
        if (! $cleanNik) {
            throw ValidationException::withMessages(['nik' => 'NIK wajib diisi.']);
        }

        $penduduk = Penduduk::query()->where('nik', $cleanNik)->first();
        if ($penduduk) {
            $penduduk->update([
                'nik' => $member['nik'] ?? $penduduk->nik,
                'nama_lengkap' => $member['nama_lengkap'] ?? $penduduk->nama_lengkap,
                'tempat_lahir' => $member['tempat_lahir'] ?? $penduduk->tempat_lahir,
                'tanggal_lahir' => $member['tanggal_lahir'] ?? $penduduk->tanggal_lahir,
                'jenis_kelamin' => $member['jenis_kelamin'] ?? $penduduk->jenis_kelamin,
                'agama' => $member['agama'] ?? $penduduk->agama,
                'golongan_darah' => $member['golongan_darah'] ?? $penduduk->golongan_darah,
                'status_perkawinan' => $member['status_perkawinan'] ?? $penduduk->status_perkawinan,
                'pendidikan_id' => $member['pendidikan_id'] ?? $penduduk->pendidikan_id,
                'pekerjaan_id' => $member['pekerjaan_id'] ?? $penduduk->pekerjaan_id,
                'kewarganegaraan' => $member['kewarganegaraan'] ?? $penduduk->kewarganegaraan,
                'nama_ayah' => $member['nama_ayah'] ?? $penduduk->nama_ayah,
                'nama_ibu' => $member['nama_ibu'] ?? $penduduk->nama_ibu,
                'telepon' => $member['telepon'] ?? $penduduk->telepon,
                'email' => $member['email'] ?? $penduduk->email,
                'alamat' => $member['alamat'] ?? ($kkTujuan?->alamat ?? $penduduk->alamat),
                'dusun_id' => $member['dusun_id'] ?? ($kkTujuan?->dusun_id ?? $penduduk->dusun_id),
                'rw_id' => $member['rw_id'] ?? ($kkTujuan?->rw_id ?? $penduduk->rw_id),
                'rt_id' => $member['rt_id'] ?? ($kkTujuan?->rt_id ?? $penduduk->rt_id),
                'status_penduduk' => $member['status_penduduk'] ?? 'aktif',
                'masih_hidup' => $member['masih_hidup'] ?? true,
            ]);

            return $penduduk->fresh();
        }

        $namaLengkap = $member['nama_lengkap'] ?? null;
        if (! $namaLengkap) {
            throw ValidationException::withMessages(['nama_lengkap' => 'Nama lengkap wajib diisi untuk penduduk baru.']);
        }

        return Penduduk::query()->create([
            'nik' => $cleanNik,
            'nama_lengkap' => $namaLengkap,
            'tempat_lahir' => $member['tempat_lahir'] ?? null,
            'tanggal_lahir' => $member['tanggal_lahir'] ?? null,
            'jenis_kelamin' => $member['jenis_kelamin'] ?? 'Laki-laki',
            'agama' => $member['agama'] ?? null,
            'golongan_darah' => $member['golongan_darah'] ?? null,
            'status_perkawinan' => $member['status_perkawinan'] ?? null,
            'pendidikan_id' => $member['pendidikan_id'] ?? null,
            'pekerjaan_id' => $member['pekerjaan_id'] ?? null,
            'kewarganegaraan' => $member['kewarganegaraan'] ?? 'WNI',
            'nama_ayah' => $member['nama_ayah'] ?? null,
            'nama_ibu' => $member['nama_ibu'] ?? null,
            'telepon' => $member['telepon'] ?? null,
            'email' => $member['email'] ?? null,
            'alamat' => $member['alamat'] ?? ($kkTujuan?->alamat ?? null),
            'dusun_id' => $member['dusun_id'] ?? ($kkTujuan?->dusun_id ?? null),
            'rw_id' => $member['rw_id'] ?? ($kkTujuan?->rw_id ?? null),
            'rt_id' => $member['rt_id'] ?? ($kkTujuan?->rt_id ?? null),
            'status_penduduk' => $member['status_penduduk'] ?? 'aktif',
            'masih_hidup' => $member['masih_hidup'] ?? true,
        ]);
    }

    private function assertCitizenCanBeActivated(?Penduduk $penduduk, string $nik): void
    {
        if (! $penduduk) {
            return;
        }

        if ($penduduk->status_penduduk === 'meninggal' || ! $penduduk->masih_hidup) {
            throw ValidationException::withMessages(['nik' => 'Penduduk dengan NIK tersebut tercatat telah meninggal.']);
        }
    }

    private function activateMembership(Penduduk $penduduk, ?KartuKeluarga $kartuKeluarga, string $hubungan, ?string $tanggalDatang): ?AnggotaKartuKeluarga
    {
        if (! $kartuKeluarga) {
            return null;
        }

        $existing = AnggotaKartuKeluarga::query()
            ->where('penduduk_id', $penduduk->id)
            ->where('aktif', true)
            ->latest('id')
            ->first();

        if ($existing && (int) $existing->kartu_keluarga_id === (int) $kartuKeluarga->id) {
            return $existing;
        }

        if ($existing) {
            $existing->update(['aktif' => false, 'tanggal_keluar' => $tanggalDatang, 'alasan_keluar' => 'pindah']);
        }

        return AnggotaKartuKeluarga::query()->create([
            'kartu_keluarga_id' => $kartuKeluarga->id,
            'penduduk_id' => $penduduk->id,
            'hubungan_keluarga' => $hubungan,
            'tanggal_masuk' => $tanggalDatang,
            'aktif' => true,
        ]);
    }
}
