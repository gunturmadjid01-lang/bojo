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
        Schema::create('penduduk_datangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penduduk_id')
                ->constrained('penduduks')
                ->restrictOnDelete();

            $table->date('tanggal_datang');

            $table->text('alamat_sebelumnya')->nullable();
            $table->string('desa_sebelumnya')->nullable();
            $table->string('kecamatan_sebelumnya')->nullable();
            $table->string('kabupaten_sebelumnya')->nullable();
            $table->string('provinsi_sebelumnya')->nullable();

            $table->foreignId('kartu_keluarga_tujuan_id')
                ->nullable()
                ->constrained('kartu_keluargas')
                ->nullOnDelete();

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
        Schema::dropIfExists('penduduk_datangs');
    }
};
