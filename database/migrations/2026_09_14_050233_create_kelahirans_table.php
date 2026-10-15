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
        Schema::create('kelahirans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penduduk_id')
                ->constrained('penduduks')
                ->restrictOnDelete();

            $table->foreignId('kartu_keluarga_id')
                ->nullable()
                ->constrained('kartu_keluargas')
                ->nullOnDelete();

            $table->foreignId('ayah_id')
                ->nullable()
                ->constrained('penduduks')
                ->nullOnDelete();

            $table->foreignId('ibu_id')
                ->nullable()
                ->constrained('penduduks')
                ->nullOnDelete();

            $table->string('nomor_akta_kelahiran')->nullable();

            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->time('waktu_lahir')->nullable();

            $table->integer('anak_ke')->nullable();

            $table->decimal('berat_lahir', 5, 2)->nullable();
            $table->decimal('panjang_lahir', 5, 2)->nullable();

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
        Schema::dropIfExists('kelahirans');
    }
};
