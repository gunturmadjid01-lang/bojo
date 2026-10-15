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
        Schema::create('riwayat_perkawinans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penduduk_id')
                ->constrained('penduduks')
                ->restrictOnDelete();

            $table->foreignId('pasangan_id')
                ->nullable()
                ->constrained('penduduks')
                ->nullOnDelete();

            $table->string('status');

            /*
    menikah
    cerai_hidup
    cerai_mati
    */

            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();

            $table->string('nomor_dokumen')->nullable();

            $table->text('catatan')->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_perkawinans');
    }
};
