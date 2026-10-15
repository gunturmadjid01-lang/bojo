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
        Schema::create('peristiwa_kependudukans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penduduk_id')
                ->constrained('penduduks')
                ->restrictOnDelete();

            $table->string('jenis');

            /*
    kelahiran
    kematian
    datang
    pindah
    perkawinan
    perceraian
    perubahan_kk
    perubahan_data
    */

            $table->date('tanggal_peristiwa');

            $table->string('nomor_referensi')->nullable();
            $table->text('keterangan')->nullable();

            $table->foreignId('dibuat_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index([
                'penduduk_id',
                'jenis'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peristiwa_kependudukans');
    }
};
