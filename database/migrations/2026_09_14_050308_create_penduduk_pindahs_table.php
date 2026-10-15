<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('penduduk_pindahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penduduk_id')
                ->constrained('penduduks')
                ->restrictOnDelete();

            $table->string('jenis_pindah')->default('individu');
            $table->string('jenis_tujuan_kk')->default('gabung_kk');
            $table->foreignId('kartu_keluarga_tujuan_id')->nullable()->constrained('kartu_keluargas')->nullOnDelete();
            $table->string('hubungan_keluarga')->nullable();

            $table->date('tanggal_pindah');

            $table->text('alamat_tujuan')->nullable();
            $table->string('desa_tujuan')->nullable();
            $table->string('kecamatan_tujuan')->nullable();
            $table->string('kabupaten_tujuan')->nullable();
            $table->string('provinsi_tujuan')->nullable();

            $table->string('nomor_surat_pindah')->nullable();

            $table->string('alasan')->nullable();

            $table->string('status')->default('selesai');

            $table->text('catatan')->nullable();

            $table->foreignId('dibuat_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penduduk_pindahs');
    }
};
