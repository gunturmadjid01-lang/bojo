<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kedatangan_penduduks')) {
            Schema::create('kedatangan_penduduks', function (Blueprint $table) {
                $table->id();
                $table->string('jenis_kedatangan')->default('individu');
                $table->date('tanggal_datang');
                $table->string('nomor_surat_pindah')->nullable();
                $table->text('alamat_sebelumnya')->nullable();
                $table->string('desa_sebelumnya')->nullable();
                $table->string('kecamatan_sebelumnya')->nullable();
                $table->string('kabupaten_sebelumnya')->nullable();
                $table->string('provinsi_sebelumnya')->nullable();
                $table->string('jenis_tujuan_kk')->nullable();
                $table->foreignId('kartu_keluarga_tujuan_id')->nullable()->constrained('kartu_keluargas')->nullOnDelete();
                $table->string('status')->default('selesai');
                $table->string('alasan')->nullable();
                $table->text('catatan')->nullable();
                $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('anggota_kedatangan')) {
            Schema::create('anggota_kedatangan', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kedatangan_penduduk_id')->constrained('kedatangan_penduduks')->cascadeOnDelete();
                $table->foreignId('penduduk_id')->constrained('penduduks')->restrictOnDelete();
                $table->string('hubungan_keluarga')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('penduduk_datangs')) {
            $this->migrateLegacyRows();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('anggota_kedatangan');
        Schema::dropIfExists('kedatangan_penduduks');
    }

    protected function migrateLegacyRows(): void
    {
        $legacyRows = DB::table('penduduk_datangs')->get();
        foreach ($legacyRows as $row) {
            $header = DB::table('kedatangan_penduduks')->insertGetId([
                'jenis_kedatangan' => 'individu',
                'tanggal_datang' => $row->tanggal_datang ?? now()->toDateString(),
                'nomor_surat_pindah' => $row->nomor_surat_pindah,
                'alamat_sebelumnya' => $row->alamat_sebelumnya,
                'desa_sebelumnya' => $row->desa_sebelumnya,
                'kecamatan_sebelumnya' => $row->kecamatan_sebelumnya,
                'kabupaten_sebelumnya' => $row->kabupaten_sebelumnya,
                'provinsi_sebelumnya' => $row->provinsi_sebelumnya,
                'jenis_tujuan_kk' => 'gabung_kk',
                'kartu_keluarga_tujuan_id' => $row->kartu_keluarga_tujuan_id,
                'status' => $row->status ?? 'selesai',
                'alasan' => $row->alasan,
                'catatan' => $row->catatan,
                'dibuat_oleh' => $row->dibuat_oleh,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);

            DB::table('anggota_kedatangan')->insert([
                'kedatangan_penduduk_id' => $header,
                'penduduk_id' => $row->penduduk_id,
                'hubungan_keluarga' => 'Anggota',
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }
    }
};
