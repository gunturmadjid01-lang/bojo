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
        Schema::create('dokumen_penduduks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penduduk_id')
                ->nullable()
                ->constrained('penduduks')
                ->cascadeOnDelete();

            $table->foreignId('kartu_keluarga_id')
                ->nullable()
                ->constrained('kartu_keluargas')
                ->cascadeOnDelete();

            $table->string('jenis_dokumen');

            /*
    ktp
    kk
    akta_kelahiran
    akta_perkawinan
    akta_kematian
    surat_pindah
    ijazah
    lainnya
    */

            $table->string('nomor_dokumen')->nullable();

            $table->date('tanggal_dokumen')->nullable();

            $table->string('file');

            $table->text('keterangan')->nullable();

            $table->foreignId('diunggah_oleh')
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
        Schema::dropIfExists('dokumen_penduduks');
    }
};
