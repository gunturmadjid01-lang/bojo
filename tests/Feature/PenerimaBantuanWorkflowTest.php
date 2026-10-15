<?php

namespace Tests\Feature;

use App\Models\Penduduk;
use App\Models\PenerimaBantuan;
use App\Models\ProgramBantuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenerimaBantuanWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_pendaftaran_massal_menyimpan_data_per_penerima_dengan_status_menunggu(): void
    {
        $program = ProgramBantuan::create(['nama' => 'BLT Desa', 'status' => 'aktif']);
        $first = $this->createPenduduk('7301000000000001', 'Andi');
        $second = $this->createPenduduk('7301000000000002', 'Budi');

        $response = $this->post(route('penerima-bantuan.store'), [
            'program_bantuan_id' => $program->id,
            'jenis_penerima' => 'penduduk',
            'penerimas' => [
                ['penerima_id' => $first->id, 'tanggal_terdaftar' => '2026-09-14', 'catatan' => 'Prioritas pertama'],
                ['penerima_id' => $second->id, 'tanggal_terdaftar' => '2026-09-15', 'catatan' => 'Prioritas kedua'],
            ],
        ]);

        $response->assertRedirect(route('penerima-bantuan.index'));
        $this->assertDatabaseHas('penerima_bantuans', [
            'penduduk_id' => $first->id,
            'tanggal_terdaftar' => '2026-09-14 00:00:00',
            'catatan' => 'Prioritas pertama',
            'status_kelayakan' => 'menunggu',
            'status_verifikasi' => 'belum_diverifikasi',
        ]);
        $this->assertDatabaseHas('penerima_bantuans', [
            'penduduk_id' => $second->id,
            'tanggal_terdaftar' => '2026-09-15 00:00:00',
            'catatan' => 'Prioritas kedua',
        ]);
    }

    public function test_verifikasi_harus_dimulai_sebelum_hasil_ditetapkan(): void
    {
        $user = User::factory()->create();
        $penerima = $this->createPenerima();

        $this->actingAs($user)
            ->post(route('penerima-bantuan.verification.finish', $penerima), [
                'status_kelayakan' => 'layak',
            ])
            ->assertSessionHasErrors('status_verifikasi');

        $this->actingAs($user)
            ->post(route('penerima-bantuan.verification.start', $penerima))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('penerima_bantuans', [
            'id' => $penerima->id,
            'status_verifikasi' => 'proses_verifikasi',
            'status_kelayakan' => 'menunggu',
        ]);

        $this->actingAs($user)
            ->post(route('penerima-bantuan.verification.finish', $penerima), [
                'status_kelayakan' => 'layak',
                'catatan_verifikasi' => 'Dokumen lengkap dan sesuai.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('penerima_bantuans', [
            'id' => $penerima->id,
            'status_verifikasi' => 'terverifikasi',
            'status_kelayakan' => 'layak',
            'diverifikasi_oleh' => $user->id,
            'catatan_verifikasi' => 'Dokumen lengkap dan sesuai.',
        ]);
    }

    public function test_alasan_wajib_diisi_untuk_hasil_tidak_layak(): void
    {
        $penerima = $this->createPenerima();
        $penerima->update(['status_verifikasi' => 'proses_verifikasi']);

        $this->post(route('penerima-bantuan.verification.finish', $penerima), [
            'status_kelayakan' => 'tidak_layak',
            'catatan_verifikasi' => '',
        ])->assertSessionHasErrors('catatan_verifikasi');
    }

    public function test_hanya_penerima_layak_yang_dapat_menerima_penyaluran(): void
    {
        $penerima = $this->createPenerima();
        $payload = [
            'penerima_bantuan_id' => $penerima->id,
            'periode' => 'September 2026',
            'tanggal_penyaluran' => '2026-09-20',
            'jumlah' => 500000,
            'status' => 'belum_disalurkan',
            'diterima_oleh' => 'Penerima Uji',
        ];

        $this->post(route('penyaluran-bantuan.store'), $payload)
            ->assertSessionHasErrors('penerima_bantuan_id');

        $penerima->update([
            'status_verifikasi' => 'terverifikasi',
            'status_kelayakan' => 'layak',
        ]);

        $this->post(route('penyaluran-bantuan.store'), $payload)
            ->assertRedirect(route('penyaluran-bantuan.index'));

        $this->assertDatabaseHas('penyaluran_bantuans', [
            'penerima_bantuan_id' => $penerima->id,
            'periode' => 'September 2026',
        ]);
    }

    public function test_halaman_detail_workflow_dapat_dibuka(): void
    {
        $penerima = $this->createPenerima();

        $this->get(route('penerima-bantuan.show', $penerima))->assertOk();
    }

    private function createPenerima(): PenerimaBantuan
    {
        $program = ProgramBantuan::create(['nama' => 'BLT Desa', 'status' => 'aktif']);
        $penduduk = $this->createPenduduk('7301000000000099', 'Penerima Uji');

        return PenerimaBantuan::create([
            'program_bantuan_id' => $program->id,
            'penduduk_id' => $penduduk->id,
            'tanggal_terdaftar' => '2026-09-14',
            'status_kelayakan' => 'menunggu',
            'status_verifikasi' => 'belum_diverifikasi',
        ]);
    }

    private function createPenduduk(string $nik, string $nama): Penduduk
    {
        return Penduduk::create([
            'nik' => $nik,
            'nama_lengkap' => $nama,
            'jenis_kelamin' => 'Laki-laki',
        ]);
    }
}
