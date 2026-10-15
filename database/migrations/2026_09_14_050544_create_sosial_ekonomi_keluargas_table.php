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
        Schema::create('sosial_ekonomi_keluargas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('kartu_keluarga_id')
                ->constrained('kartu_keluargas')
                ->cascadeOnDelete();

            $table->string('status_kepemilikan_rumah')->nullable();
            $table->string('kondisi_rumah')->nullable();

            $table->string('jenis_lantai')->nullable();
            $table->string('jenis_dinding')->nullable();
            $table->string('jenis_atap')->nullable();

            $table->string('sumber_air')->nullable();
            $table->string('sumber_listrik')->nullable();
            $table->string('daya_listrik')->nullable();

            $table->string('fasilitas_toilet')->nullable();
            $table->string('bahan_bakar_memasak')->nullable();

            $table->boolean('memiliki_kendaraan')->nullable();
            $table->boolean('akses_internet')->nullable();

            $table->string('kategori_ekonomi')->nullable();

            $table->date('tanggal_pendataan')->nullable();

            $table->foreignId('didata_oleh')
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
        Schema::dropIfExists('sosial_ekonomi_keluargas');
    }
};
