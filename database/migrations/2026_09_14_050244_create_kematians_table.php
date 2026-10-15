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
        Schema::create('kematians', function (Blueprint $table) {
            $table->id();

            $table->foreignId('penduduk_id')
                ->constrained('penduduks')
                ->restrictOnDelete();

            $table->date('tanggal_meninggal');
            $table->time('waktu_meninggal')->nullable();

            $table->string('tempat_meninggal')->nullable();
            $table->string('penyebab_meninggal')->nullable();

            $table->string('nomor_surat_kematian')->nullable();

            $table->date('tanggal_dilaporkan')->nullable();

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
        Schema::dropIfExists('kematians');
    }
};
