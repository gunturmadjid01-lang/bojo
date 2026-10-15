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
        Schema::create('penugasan_perangkats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perangkat_desa_id')
                ->constrained('perangkat_desas')
                ->restrictOnDelete();

            $table->foreignId('jabatan_desa_id')
                ->constrained('jabatan_desas')
                ->restrictOnDelete();

            $table->foreignId('desa_id')
                ->constrained('desas')
                ->restrictOnDelete();

            $table->foreignId('dusun_id')
                ->nullable()
                ->constrained('dusuns')
                ->restrictOnDelete();

            $table->date('tanggal_mulai');

            $table->date('tanggal_selesai')->nullable();

            $table->string('nomor_sk_pengangkatan')->nullable();
            $table->date('tanggal_sk_pengangkatan')->nullable();

            $table->string('nomor_sk_pemberhentian')->nullable();
            $table->date('tanggal_sk_pemberhentian')->nullable();

            $table->string('status')->default('aktif');

            /*
    aktif
    selesai
    dibatalkan
    */

            $table->text('catatan')->nullable();

            $table->foreignId('dibuat_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index([
                'jabatan_desa_id',
                'dusun_id',
                'status'
            ]);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penugasan_perangkats');
    }
};
