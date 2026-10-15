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
        Schema::create('penerima_bantuans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_bantuan_id')
                ->constrained('program_bantuans')
                ->restrictOnDelete();

            $table->foreignId('penduduk_id')
                ->nullable()
                ->constrained('penduduks')
                ->restrictOnDelete();

            $table->foreignId('kartu_keluarga_id')
                ->nullable()
                ->constrained('kartu_keluargas')
                ->restrictOnDelete();

            $table->date('tanggal_terdaftar')->nullable();

            $table->string('status_kelayakan')->default('layak');
            $table->string('status_verifikasi')->default('belum_diverifikasi');

            $table->text('catatan')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penerima_bantuans');
    }
};
