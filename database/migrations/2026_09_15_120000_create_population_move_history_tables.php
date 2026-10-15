<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perpindahan_penduduks', function (Blueprint $table): void {
            $table->id();
            $table->string('kode_perpindahan')->nullable()->unique();
            $table->string('jenis_pindah')->default('individu');
            $table->string('tujuan_pindah')->default('keluar_desa');
            $table->date('tanggal_pindah');
            $table->string('nomor_surat_pindah')->nullable();
            $table->text('alasan')->nullable();
            $table->foreignId('kartu_keluarga_asal_id')->nullable()->constrained('kartu_keluargas')->nullOnDelete();
            $table->string('jenis_tujuan_kk')->nullable();
            $table->foreignId('kartu_keluarga_tujuan_id')->nullable()->constrained('kartu_keluargas')->nullOnDelete();
            $table->text('alamat_tujuan')->nullable();
            $table->string('desa_tujuan')->nullable();
            $table->string('kecamatan_tujuan')->nullable();
            $table->string('kabupaten_tujuan')->nullable();
            $table->string('provinsi_tujuan')->nullable();
            $table->string('status')->default('selesai');
            $table->text('catatan')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tanggal_pindah', 'tujuan_pindah']);
        });

        Schema::create('anggota_perpindahan', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('perpindahan_penduduk_id')->constrained('perpindahan_penduduks')->cascadeOnDelete();
            $table->foreignId('penduduk_id')->constrained('penduduks')->restrictOnDelete();
            $table->foreignId('kartu_keluarga_asal_id')->nullable()->constrained('kartu_keluargas')->nullOnDelete();
            $table->foreignId('kartu_keluarga_tujuan_id')->nullable()->constrained('kartu_keluargas')->nullOnDelete();
            $table->string('hubungan_keluarga_tujuan')->nullable();
            $table->timestamps();
            $table->index(['penduduk_id', 'perpindahan_penduduk_id']);
        });

        Schema::create('riwayat_domisili_kartu_keluarga', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('kartu_keluarga_id')->constrained('kartu_keluargas')->cascadeOnDelete();
            $table->foreignId('desa_id')->constrained('desas')->restrictOnDelete();
            $table->foreignId('dusun_id')->nullable()->constrained('dusuns')->nullOnDelete();
            $table->foreignId('rw_id')->nullable()->constrained('r_w_s')->nullOnDelete();
            $table->foreignId('rt_id')->nullable()->constrained('r_t_s')->nullOnDelete();
            $table->text('alamat');
            $table->string('kode_pos')->nullable();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->string('alasan_perubahan')->nullable();
            $table->foreignId('perpindahan_penduduk_id')->nullable()->constrained('perpindahan_penduduks')->nullOnDelete();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['kartu_keluarga_id', 'tanggal_mulai', 'tanggal_selesai'], 'rdkk_kk_mulai_selesai_idx');
        });

        if (! Schema::hasColumn('kartu_keluargas', 'perlu_kepala_keluarga_baru')) {
            Schema::table('kartu_keluargas', function (Blueprint $table): void {
                $table->boolean('perlu_kepala_keluarga_baru')->default(false)->after('status');
            });
        }

        $this->migrateLegacyRows();
    }

    public function down(): void
    {
        Schema::table('kartu_keluargas', function (Blueprint $table): void {
            if (Schema::hasColumn('kartu_keluargas', 'perlu_kepala_keluarga_baru')) {
                $table->dropColumn('perlu_kepala_keluarga_baru');
            }
        });
        Schema::dropIfExists('riwayat_domisili_kartu_keluarga');
        Schema::dropIfExists('anggota_perpindahan');
        Schema::dropIfExists('perpindahan_penduduks');
    }

    private function migrateLegacyRows(): void
    {
        if (! Schema::hasTable('penduduk_pindahs') || DB::table('perpindahan_penduduks')->exists()) return;

        DB::table('penduduk_pindahs')->orderBy('id')->each(function ($row): void {
            $membership = DB::table('anggota_kartu_keluargas')->where('penduduk_id', $row->penduduk_id)->where('aktif', true)->latest('id')->first();
            $headerId = DB::table('perpindahan_penduduks')->insertGetId([
                'jenis_pindah' => $row->jenis_pindah ?: 'individu',
                'tujuan_pindah' => $row->jenis_tujuan_kk === 'gabung_kk' ? 'dalam_desa' : 'keluar_desa',
                'tanggal_pindah' => $row->tanggal_pindah,
                'nomor_surat_pindah' => $row->nomor_surat_pindah,
                'alasan' => $row->alasan,
                'kartu_keluarga_asal_id' => $membership?->kartu_keluarga_id,
                'jenis_tujuan_kk' => $row->jenis_tujuan_kk,
                'kartu_keluarga_tujuan_id' => $row->kartu_keluarga_tujuan_id,
                'alamat_tujuan' => $row->alamat_tujuan,
                'desa_tujuan' => $row->desa_tujuan,
                'kecamatan_tujuan' => $row->kecamatan_tujuan,
                'kabupaten_tujuan' => $row->kabupaten_tujuan,
                'provinsi_tujuan' => $row->provinsi_tujuan,
                'status' => $row->status,
                'catatan' => $row->catatan,
                'dibuat_oleh' => $row->dibuat_oleh,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
            DB::table('anggota_perpindahan')->insert([
                'perpindahan_penduduk_id' => $headerId,
                'penduduk_id' => $row->penduduk_id,
                'kartu_keluarga_asal_id' => $membership?->kartu_keluarga_id,
                'kartu_keluarga_tujuan_id' => $row->kartu_keluarga_tujuan_id,
                'hubungan_keluarga_tujuan' => $row->hubungan_keluarga,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        });
    }
};
