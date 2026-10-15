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
        Schema::create('anggota_kartu_keluargas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('kartu_keluarga_id')
                ->constrained('kartu_keluargas')
                ->restrictOnDelete();

            $table->foreignId('penduduk_id')
                ->constrained('penduduks')
                ->restrictOnDelete();

            $table->string('hubungan_keluarga', 50);

            /*
    Kepala Keluarga
    Suami
    Istri
    Anak
    Menantu
    Cucu
    Orang Tua
    Mertua
    Famili Lain
    Lainnya
    */

            $table->date('tanggal_masuk')->nullable();
            $table->date('tanggal_keluar')->nullable();

            $table->boolean('aktif')->default(true);

            $table->string('alasan_keluar')->nullable();

            $table->timestamps();

            $table->index(['penduduk_id', 'aktif']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anggota_kartu_keluargas');
    }
};
