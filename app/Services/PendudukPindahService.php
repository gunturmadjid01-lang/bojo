<?php

namespace App\Services;

use App\Models\AnggotaKartuKeluarga;
use App\Models\Dusun;
use App\Models\KartuKeluarga;
use App\Models\Penduduk;
use App\Models\PendudukPindah;
use App\Models\PeristiwaKependudukan;
use App\Models\AnggotaPerpindahan;
use App\Models\PerpindahanPenduduk;
use App\Models\RiwayatDomisiliKartuKeluarga;
use App\Models\RT;
use App\Models\RW;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PendudukPindahService
{
    public function store(array $payload, ?User $actor = null): void
    {
        if (isset($payload['tujuan_pindah'])) {
            $this->storeStructured($payload, $actor);
            return;
        }

        DB::transaction(function () use ($payload, $actor): void {
            $this->validateBasePayload($payload);

            $jenisPindah = $payload['jenis_pindah'] ?? 'individu';
            $anggotaList = $this->normalizeAnggota($payload);
            $this->validateNoDuplicateNiks($anggotaList);
            $this->validateFamilyRules($anggotaList, $payload);

            $kkTujuan = $this->resolveKartuKeluargaTujuan($payload, $anggotaList);
            $targetKkId = $kkTujuan?->id;

            foreach ($anggotaList as $member) {
                $existingPenduduk = $this->existingPendudukByNik($member['nik'] ?? null);
                $this->assertCitizenCanBeActivated($existingPenduduk, $member['nik'] ?? null);

                $penduduk = $this->resolvePendudukForMove($member, $kkTujuan, $payload);
                $this->attachToKartuKeluarga($penduduk, $kkTujuan, $member['hubungan_keluarga'] ?? 'Anggota', $payload['tanggal_pindah']);

                PendudukPindah::query()->create([
                    'penduduk_id' => $penduduk->id,
                    'jenis_pindah' => $jenisPindah,
                    'jenis_tujuan_kk' => $payload['jenis_tujuan_kk'] ?? 'gabung_kk',
                    'kartu_keluarga_tujuan_id' => $targetKkId,
                    'hubungan_keluarga' => $member['hubungan_keluarga'] ?? 'Anggota',
                    'tanggal_pindah' => $payload['tanggal_pindah'],
                    'alamat_tujuan' => $payload['alamat_tujuan'] ?? $member['alamat'] ?? null,
                    'desa_tujuan' => $payload['desa_tujuan'] ?? null,
                    'kecamatan_tujuan' => $payload['kecamatan_tujuan'] ?? null,
                    'kabupaten_tujuan' => $payload['kabupaten_tujuan'] ?? null,
                    'provinsi_tujuan' => $payload['provinsi_tujuan'] ?? null,
                    'nomor_surat_pindah' => $payload['nomor_surat_pindah'] ?? null,
                    'alasan' => $payload['alasan'] ?? null,
                    'status' => $payload['status'] ?? 'selesai',
                    'catatan' => $payload['catatan'] ?? null,
                    'dibuat_oleh' => $actor?->id,
                ]);

                PeristiwaKependudukan::query()->create([
                    'penduduk_id' => $penduduk->id,
                    'jenis' => 'pindah',
                    'tanggal_peristiwa' => $payload['tanggal_pindah'],
                    'nomor_referensi' => $payload['nomor_surat_pindah'] ?? $payload['nomor_kk'] ?? null,
                    'keterangan' => 'Penduduk pindah ke tujuan yang dipilih.',
                    'dibuat_oleh' => $actor?->id,
                ]);
            }
        });
    }

    private function storeStructured(array $payload, ?User $actor): void
    {
        DB::transaction(function () use ($payload, $actor): void {
            $type = $payload['jenis_pindah'] ?? 'individu';
            $destination = $payload['tujuan_pindah'] ?? null;
            $date = $payload['tanggal_pindah'] ?? null;
            if (!in_array($type, ['individu', 'keluarga'], true) || !in_array($destination, ['dalam_desa', 'keluar_desa'], true)) {
                throw ValidationException::withMessages(['tujuan_pindah' => 'Jenis dan tujuan perpindahan tidak valid.']);
            }
            if (!$date) throw ValidationException::withMessages(['tanggal_pindah' => 'Tanggal pindah wajib diisi.']);

            [$members, $originKk] = $this->structuredOrigin($payload, $type);
            $this->assertDestinationScope($payload, $originKk, $destination);
            $targetKk = null;
            if ($destination === 'dalam_desa') {
                $targetKk = $type === 'keluarga'
                    ? $this->moveFamilyWithinVillage($payload, $originKk, $members, $date, $actor)
                    : $this->resolveStructuredTargetKk($payload, $originKk, $members, $date, $actor);
            }

            $header = PerpindahanPenduduk::create([
                'jenis_pindah' => $type,
                'tujuan_pindah' => $destination,
                'tanggal_pindah' => $date,
                'nomor_surat_pindah' => $payload['nomor_surat_pindah'] ?? null,
                'alasan' => $payload['alasan'] ?? null,
                'kartu_keluarga_asal_id' => $originKk?->id,
                'jenis_tujuan_kk' => $payload['jenis_tujuan_kk'] ?? null,
                'kartu_keluarga_tujuan_id' => $targetKk?->id,
                'alamat_tujuan' => $payload['alamat_tujuan'] ?? $payload['kk_baru']['alamat'] ?? null,
                'desa_tujuan' => $payload['desa_tujuan'] ?? null,
                'kecamatan_tujuan' => $payload['kecamatan_tujuan'] ?? null,
                'kabupaten_tujuan' => $payload['kabupaten_tujuan'] ?? null,
                'provinsi_tujuan' => $payload['provinsi_tujuan'] ?? null,
                'status' => $payload['status'] ?? 'selesai',
                'catatan' => $payload['catatan'] ?? null,
                'dibuat_oleh' => $actor?->id,
            ]);

            RiwayatDomisiliKartuKeluarga::query()
                ->where('kartu_keluarga_id', $originKk?->id)
                ->where('tanggal_mulai', $date)
                ->whereNull('perpindahan_penduduk_id')
                ->update(['perpindahan_penduduk_id' => $header->id]);

            if ($destination === 'keluar_desa') {
                foreach ($members as $member) {
                    $wasHead = $this->activeMembership($member)?->hubungan_keluarga === 'Kepala Keluarga';
                    $this->closeMembership($member, $date, 'pindah_keluar_desa');
                    $member->update(['status_penduduk' => 'pindah', 'masih_hidup' => true]);
                    if ($wasHead && $originKk && $originKk->anggotaKeluarga()->where('aktif', true)->exists()) {
                        $originKk->update(['perlu_kepala_keluarga_baru' => true]);
                    }
                }
                if ($type === 'keluarga' && $originKk) $originKk->update(['status' => 'pindah']);
            } elseif ($type === 'individu') {
                $this->moveIndividualWithinVillage($members[0], $targetKk, $payload, $date);
            }

            foreach ($members as $member) {
                AnggotaPerpindahan::create([
                    'perpindahan_penduduk_id' => $header->id,
                    'penduduk_id' => $member->id,
                    'kartu_keluarga_asal_id' => $originKk?->id,
                    'kartu_keluarga_tujuan_id' => $targetKk?->id,
                    'hubungan_keluarga_tujuan' => $payload['hubungan_keluarga'] ?? $this->activeMembership($member)?->hubungan_keluarga,
                ]);
                PeristiwaKependudukan::create([
                    'penduduk_id' => $member->id,
                    'jenis' => 'pindah',
                    'tanggal_peristiwa' => $date,
                    'nomor_referensi' => $payload['nomor_surat_pindah'] ?? null,
                    'keterangan' => $destination === 'dalam_desa' ? 'Perpindahan domisili dalam desa.' : 'Perpindahan keluar desa.',
                    'dibuat_oleh' => $actor?->id,
                ]);
                // Keep the old table populated as a backward-compatible event projection.
                PendudukPindah::create([
                    'penduduk_id' => $member->id,
                    'jenis_pindah' => $type,
                    'jenis_tujuan_kk' => $payload['jenis_tujuan_kk'] ?? null,
                    'kartu_keluarga_tujuan_id' => $targetKk?->id,
                    'hubungan_keluarga' => $payload['hubungan_keluarga'] ?? null,
                    'tanggal_pindah' => $date,
                    'alamat_tujuan' => $payload['alamat_tujuan'] ?? $payload['kk_baru']['alamat'] ?? null,
                    'desa_tujuan' => $payload['desa_tujuan'] ?? null,
                    'kecamatan_tujuan' => $payload['kecamatan_tujuan'] ?? null,
                    'kabupaten_tujuan' => $payload['kabupaten_tujuan'] ?? null,
                    'provinsi_tujuan' => $payload['provinsi_tujuan'] ?? null,
                    'nomor_surat_pindah' => $payload['nomor_surat_pindah'] ?? null,
                    'alasan' => $payload['alasan'] ?? null,
                    'status' => $payload['status'] ?? 'selesai',
                    'catatan' => $payload['catatan'] ?? null,
                    'dibuat_oleh' => $actor?->id,
                ]);
            }
        });
    }

    private function structuredOrigin(array $payload, string $type): array
    {
        if ($type === 'keluarga') {
            $kk = KartuKeluarga::query()->where('status', 'aktif')->lockForUpdate()->find($payload['kartu_keluarga_asal_id'] ?? null);
            if (!$kk) throw ValidationException::withMessages(['kartu_keluarga_asal_id' => 'Kartu keluarga asal tidak ditemukan atau tidak aktif.']);
            $members = $kk->anggotaKeluarga()->where('aktif', true)->whereHas('penduduk', fn ($q) => $q->where('masih_hidup', true)->where('status_penduduk', '!=', 'meninggal'))->with('penduduk')->get()->map->penduduk->filter()->values();
            if ($members->isEmpty()) throw ValidationException::withMessages(['kartu_keluarga_asal_id' => 'Kartu keluarga tidak memiliki anggota aktif.']);
            return [$members->all(), $kk];
        }

        $member = Penduduk::query()->where('status_penduduk', '!=', 'pindah')->where('masih_hidup', true)->find($payload['penduduk_id'] ?? null);
        if (!$member) throw ValidationException::withMessages(['penduduk_id' => 'Penduduk tidak ditemukan, sudah pindah, atau tidak dapat diproses.']);
        $kk = $this->activeMembership($member)?->kartuKeluarga;
        return [[$member], $kk];
    }

    private function activeMembership(Penduduk $member): ?AnggotaKartuKeluarga
    {
        return AnggotaKartuKeluarga::query()->where('penduduk_id', $member->id)->where('aktif', true)->with('kartuKeluarga')->latest('id')->first();
    }

    private function closeMembership(Penduduk $member, string $date, string $reason): void
    {
        $membership = $this->activeMembership($member);
        if ($membership) $membership->update(['aktif' => false, 'tanggal_keluar' => $date, 'alasan_keluar' => $reason]);
    }

    private function assertDestinationScope(array $payload, ?KartuKeluarga $originKk, string $destination): void
    {
        if (!$originKk) throw ValidationException::withMessages(['kartu_keluarga_asal_id' => 'Penduduk harus memiliki kartu keluarga aktif.']);
        if ($destination === 'keluar_desa') return;
        $targetDusunId = $payload['kk_baru']['dusun_id'] ?? null;
        if (!$targetDusunId && !empty($payload['kartu_keluarga_tujuan_id'])) $targetDusunId = KartuKeluarga::query()->find($payload['kartu_keluarga_tujuan_id'])?->dusun_id;
        if (!$targetDusunId) throw ValidationException::withMessages(['dusun_id' => 'Dusun tujuan wajib dipilih.']);
        $targetDusun = Dusun::withoutGlobalScope('wilayah')->find($targetDusunId);
        if (!$targetDusun || (int) $targetDusun->desa_id !== (int) $originKk->desa_id) throw ValidationException::withMessages(['dusun_id' => 'Perpindahan dalam desa harus tetap berada di desa asal.']);
        $assignments = app(\App\Services\WilayahAccess::class)->assignments();
        if (!app(\App\Services\WilayahAccess::class)->unrestricted() && $assignments->every(fn ($item) => $item->jabatanDesa?->tingkat === 'dusun') && !$assignments->pluck('dusun_id')->contains((int) $targetDusunId)) {
            throw ValidationException::withMessages(['dusun_id' => 'Perpindahan antar dusun hanya dapat diproses oleh pengguna dengan cakupan tingkat desa.']);
        }
    }

    private function resolveStructuredTargetKk(array $payload, ?KartuKeluarga $originKk, array $members, string $date, ?User $actor): KartuKeluarga
    {
        if (($payload['jenis_tujuan_kk'] ?? null) === 'kk_baru') {
            $data = $payload['kk_baru'] ?? [];
            foreach (['nomor_kk', 'dusun_id', 'rw_id', 'rt_id', 'alamat'] as $field) if (blank($data[$field] ?? null)) throw ValidationException::withMessages(["kk_baru.$field" => 'Field wajib diisi.']);
            $dusun = Dusun::withoutGlobalScope('wilayah')->findOrFail($data['dusun_id']);
            $kk = KartuKeluarga::create(['desa_id' => $dusun->desa_id, 'dusun_id' => $data['dusun_id'], 'rw_id' => $data['rw_id'], 'rt_id' => $data['rt_id'], 'nomor_kk' => $data['nomor_kk'], 'alamat' => $data['alamat'], 'kode_pos' => $data['kode_pos'] ?? null, 'tanggal_diterbitkan' => $data['tanggal_diterbitkan'] ?? $date, 'status' => 'aktif']);
            return $kk;
        }
        $kk = KartuKeluarga::query()->where('status', 'aktif')->find($payload['kartu_keluarga_tujuan_id'] ?? null);
        if (!$kk) throw ValidationException::withMessages(['kartu_keluarga_tujuan_id' => 'Kartu keluarga tujuan wajib dipilih.']);
        return $kk;
    }

    private function moveIndividualWithinVillage(Penduduk $member, KartuKeluarga $targetKk, array $payload, string $date): void
    {
        $this->closeMembership($member, $date, 'pindah_kk');
        AnggotaKartuKeluarga::create(['kartu_keluarga_id' => $targetKk->id, 'penduduk_id' => $member->id, 'hubungan_keluarga' => $payload['hubungan_keluarga'] ?? 'Anggota', 'tanggal_masuk' => $date, 'aktif' => true]);
        $member->update(['dusun_id' => $targetKk->dusun_id, 'rw_id' => $targetKk->rw_id, 'rt_id' => $targetKk->rt_id, 'alamat' => $targetKk->alamat, 'status_penduduk' => 'aktif', 'masih_hidup' => true]);
    }

    private function moveFamilyWithinVillage(array $payload, KartuKeluarga $kk, array $members, string $date, ?User $actor): KartuKeluarga
    {
        $data = $payload['domisili_tujuan'] ?? $payload['kk_baru'] ?? [];
        foreach (['dusun_id', 'rw_id', 'rt_id', 'alamat'] as $field) if (blank($data[$field] ?? null)) throw ValidationException::withMessages(["domisili_tujuan.$field" => 'Field wajib diisi.']);
        $activeHistory = RiwayatDomisiliKartuKeluarga::query()->where('kartu_keluarga_id', $kk->id)->whereNull('tanggal_selesai')->latest('id')->first();
        if ($activeHistory) $activeHistory->update(['tanggal_selesai' => date('Y-m-d', strtotime($date.' -1 day'))]);
        $kk->update(['dusun_id' => $data['dusun_id'], 'rw_id' => $data['rw_id'], 'rt_id' => $data['rt_id'], 'alamat' => $data['alamat'], 'kode_pos' => $data['kode_pos'] ?? $kk->kode_pos]);
        foreach ($members as $member) $member->update(['dusun_id' => $kk->dusun_id, 'rw_id' => $kk->rw_id, 'rt_id' => $kk->rt_id, 'alamat' => $kk->alamat, 'status_penduduk' => 'aktif', 'masih_hidup' => true]);
        RiwayatDomisiliKartuKeluarga::create(['kartu_keluarga_id' => $kk->id, 'desa_id' => $kk->desa_id, 'dusun_id' => $kk->dusun_id, 'rw_id' => $kk->rw_id, 'rt_id' => $kk->rt_id, 'alamat' => $kk->alamat, 'kode_pos' => $kk->kode_pos, 'tanggal_mulai' => $date, 'alasan_perubahan' => 'pindah_domisili', 'dibuat_oleh' => $actor?->id]);
        return $kk;
    }

    private function validateBasePayload(array $payload): void
    {
        if (! isset($payload['tanggal_pindah'])) {
            throw ValidationException::withMessages(['tanggal_pindah' => 'Tanggal pindah wajib diisi.']);
        }

        if (($payload['jenis_pindah'] ?? null) === 'keluarga') {
            $anggota = $payload['anggota'] ?? [];
            if (count($anggota) < 1) {
                throw ValidationException::withMessages(['anggota' => 'Minimal satu anggota keluarga wajib diisi.']);
            }
        }

        $tujuanKk = $payload['jenis_tujuan_kk'] ?? null;
        if ($tujuanKk === 'gabung_kk' && empty($payload['kartu_keluarga_tujuan_id']) && empty($payload['kartu_keluarga_tujuan_id'] ?? null)) {
            return;
        }

        if ($tujuanKk === 'kk_baru' && empty($payload['kk_baru'] ?? null)) {
            throw ValidationException::withMessages(['kk_baru' => 'Data KK baru wajib diisi bila memilih tujuan KK baru.']);
        }
    }

    private function normalizeAnggota(array $payload): array
    {
        if (($payload['jenis_pindah'] ?? null) === 'keluarga') {
            return array_map(fn ($item) => [
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
                'hubungan_keluarga' => $item['hubungan_keluarga'] ?? 'Anggota',
            ], $payload['anggota'] ?? []);
        }

        $pendudukPayload = $payload['penduduk'] ?? [
            'nik' => $payload['nik'] ?? null,
            'nama_lengkap' => $payload['nama_lengkap'] ?? null,
            'tempat_lahir' => $payload['tempat_lahir'] ?? null,
            'tanggal_lahir' => $payload['tanggal_lahir'] ?? null,
            'jenis_kelamin' => $payload['jenis_kelamin'] ?? null,
            'agama' => $payload['agama'] ?? null,
            'golongan_darah' => $payload['golongan_darah'] ?? null,
            'status_perkawinan' => $payload['status_perkawinan'] ?? null,
            'pendidikan_id' => $payload['pendidikan_id'] ?? null,
            'pekerjaan_id' => $payload['pekerjaan_id'] ?? null,
            'kewarganegaraan' => $payload['kewarganegaraan'] ?? 'WNI',
            'nama_ayah' => $payload['nama_ayah'] ?? null,
            'nama_ibu' => $payload['nama_ibu'] ?? null,
            'telepon' => $payload['telepon'] ?? null,
            'email' => $payload['email'] ?? null,
            'alamat' => $payload['alamat'] ?? null,
            'dusun_id' => $payload['dusun_id'] ?? null,
            'rw_id' => $payload['rw_id'] ?? null,
            'rt_id' => $payload['rt_id'] ?? null,
            'status_penduduk' => $payload['status_penduduk'] ?? 'aktif',
            'masih_hidup' => $payload['masih_hidup'] ?? true,
        ];

        if (! empty($payload['penduduk_id']) && empty($pendudukPayload['nik'])) {
            $penduduk = Penduduk::query()->find($payload['penduduk_id']);
            if ($penduduk) {
                $pendudukPayload = [
                    'nik' => $penduduk->nik,
                    'nama_lengkap' => $penduduk->nama_lengkap,
                    'tempat_lahir' => $penduduk->tempat_lahir,
                    'tanggal_lahir' => $penduduk->tanggal_lahir,
                    'jenis_kelamin' => $penduduk->jenis_kelamin,
                    'agama' => $penduduk->agama,
                    'golongan_darah' => $penduduk->golongan_darah,
                    'status_perkawinan' => $penduduk->status_perkawinan,
                    'pendidikan_id' => $penduduk->pendidikan_id,
                    'pekerjaan_id' => $penduduk->pekerjaan_id,
                    'kewarganegaraan' => $penduduk->kewarganegaraan,
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
                ];
            }
        }

        return [[
            'nik' => $pendudukPayload['nik'] ?? null,
            'nama_lengkap' => $pendudukPayload['nama_lengkap'] ?? null,
            'tempat_lahir' => $pendudukPayload['tempat_lahir'] ?? null,
            'tanggal_lahir' => $pendudukPayload['tanggal_lahir'] ?? null,
            'jenis_kelamin' => $pendudukPayload['jenis_kelamin'] ?? null,
            'agama' => $pendudukPayload['agama'] ?? null,
            'golongan_darah' => $pendudukPayload['golongan_darah'] ?? null,
            'status_perkawinan' => $pendudukPayload['status_perkawinan'] ?? null,
            'pendidikan_id' => $pendudukPayload['pendidikan_id'] ?? null,
            'pekerjaan_id' => $pendudukPayload['pekerjaan_id'] ?? null,
            'kewarganegaraan' => $pendudukPayload['kewarganegaraan'] ?? 'WNI',
            'nama_ayah' => $pendudukPayload['nama_ayah'] ?? null,
            'nama_ibu' => $pendudukPayload['nama_ibu'] ?? null,
            'telepon' => $pendudukPayload['telepon'] ?? null,
            'email' => $pendudukPayload['email'] ?? null,
            'alamat' => $pendudukPayload['alamat'] ?? null,
            'dusun_id' => $pendudukPayload['dusun_id'] ?? null,
            'rw_id' => $pendudukPayload['rw_id'] ?? null,
            'rt_id' => $pendudukPayload['rt_id'] ?? null,
            'status_penduduk' => $pendudukPayload['status_penduduk'] ?? 'aktif',
            'masih_hidup' => $pendudukPayload['masih_hidup'] ?? true,
            'hubungan_keluarga' => $payload['hubungan_keluarga'] ?? 'Anggota',
        ]];
    }

    private function validateNoDuplicateNiks(array $anggotaList): void
    {
        $niks = array_filter(array_map(fn ($item) => $item['nik'] ?? null, $anggotaList));
        if (count($niks) !== count(array_unique($niks))) {
            throw ValidationException::withMessages(['anggota' => 'NIK anggota tidak boleh duplikat dalam satu proses pindah.']);
        }
    }

    private function validateFamilyRules(array $anggotaList, array $payload): void
    {
        if (($payload['jenis_pindah'] ?? null) !== 'keluarga') {
            return;
        }

        $kepalaKeluargaCount = 0;
        foreach ($anggotaList as $member) {
            if (($member['hubungan_keluarga'] ?? '') === 'Kepala Keluarga') {
                $kepalaKeluargaCount++;
            }
        }

        if ($kepalaKeluargaCount !== 1) {
            throw ValidationException::withMessages(['anggota' => 'Keluarga yang pindah wajib memiliki tepat satu Kepala Keluarga.']);
        }
    }

    private function resolveKartuKeluargaTujuan(array $payload, array $anggotaList): ?KartuKeluarga
    {
        $tujuanKk = $payload['jenis_tujuan_kk'] ?? null;

        if ($tujuanKk === 'kk_baru') {
            $kkPayload = $payload['kk_baru'] ?? [];
            $nomorKk = $kkPayload['nomor_kk'] ?? null;
            $alamat = $kkPayload['alamat'] ?? null;
            $dusunId = $kkPayload['dusun_id'] ?? null;
            $rwId = $kkPayload['rw_id'] ?? null;
            $rtId = $kkPayload['rt_id'] ?? null;

            if (! $nomorKk || ! $alamat || ! $dusunId || ! $rwId || ! $rtId) {
                throw ValidationException::withMessages(['kk_baru' => 'Data KK baru wajib lengkap.']);
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
                throw ValidationException::withMessages(['anggota' => 'Kepala keluarga wajib dipilih ketika membuat KK baru.']);
            }

            return KartuKeluarga::query()->create([
                'desa_id' => $kkPayload['desa_id'] ?? $dusun->desa_id,
                'dusun_id' => $dusunId,
                'rw_id' => $rwId,
                'rt_id' => $rtId,
                'nomor_kk' => $nomorKk,
                'alamat' => $alamat,
                'kode_pos' => $kkPayload['kode_pos'] ?? null,
                'tanggal_diterbitkan' => now()->toDateString(),
                'status' => 'aktif',
            ]);
        }

        $kkId = $payload['kartu_keluarga_tujuan_id'] ?? null;
        if (! $kkId) {
            return null;
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

    private function resolvePendudukForMove(array $member, ?KartuKeluarga $kkTujuan, array $payload): Penduduk
    {
        $nik = $member['nik'] ?? null;
        $cleanNik = $nik ? preg_replace('/\s+/', '', trim($nik)) : null;
        if (! $cleanNik) {
            throw ValidationException::withMessages(['nik' => 'NIK wajib diisi.']);
        }

        $penduduk = Penduduk::query()->where('nik', $cleanNik)->first();
        if ($penduduk) {
            $penduduk->update([
                'nik' => $cleanNik,
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
                'alamat' => $member['alamat'] ?? ($kkTujuan?->alamat ?? $payload['alamat_tujuan'] ?? $penduduk->alamat),
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
            'alamat' => $member['alamat'] ?? ($kkTujuan?->alamat ?? $payload['alamat_tujuan'] ?? null),
            'dusun_id' => $member['dusun_id'] ?? ($kkTujuan?->dusun_id ?? null),
            'rw_id' => $member['rw_id'] ?? ($kkTujuan?->rw_id ?? null),
            'rt_id' => $member['rt_id'] ?? ($kkTujuan?->rt_id ?? null),
            'status_penduduk' => $member['status_penduduk'] ?? 'aktif',
            'masih_hidup' => $member['masih_hidup'] ?? true,
        ]);
    }

    private function attachToKartuKeluarga(Penduduk $penduduk, ?KartuKeluarga $kartuKeluarga, string $hubungan, ?string $tanggalPindah): void
    {
        if (! $kartuKeluarga) {
            return;
        }

        $existing = AnggotaKartuKeluarga::query()
            ->where('penduduk_id', $penduduk->id)
            ->where('aktif', true)
            ->latest('id')
            ->first();

        if ($existing && (int) $existing->kartu_keluarga_id === (int) $kartuKeluarga->id) {
            return;
        }

        if ($existing) {
            $existing->update([
                'aktif' => false,
                'tanggal_keluar' => $tanggalPindah,
                'alasan_keluar' => 'pindah',
            ]);
        }

        AnggotaKartuKeluarga::query()->create([
            'kartu_keluarga_id' => $kartuKeluarga->id,
            'penduduk_id' => $penduduk->id,
            'hubungan_keluarga' => $hubungan,
            'tanggal_masuk' => $tanggalPindah,
            'aktif' => true,
        ]);
    }

    private function assertCitizenCanBeActivated(?Penduduk $penduduk, ?string $nik): void
    {
        if (! $penduduk) {
            return;
        }

        if ($penduduk->status_penduduk === 'meninggal' || ! $penduduk->masih_hidup) {
            throw ValidationException::withMessages(['nik' => 'Penduduk dengan NIK tersebut sudah meninggal atau tidak aktif.']);
        }
    }
}
