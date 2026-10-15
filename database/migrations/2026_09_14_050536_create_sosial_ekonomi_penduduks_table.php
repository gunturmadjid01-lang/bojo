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
        Schema::create('sosial_ekonomi_penduduks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('penduduk_id')
                ->constrained('penduduks')
                ->cascadeOnDelete();

            $table->string('status_pekerjaan')->nullable();

            $table->decimal('penghasilan_bulanan', 18, 2)->nullable();

            $table->string('kategori_penghasilan')->nullable();

            $table->string('jaminan_kesehatan')->nullable();

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
        Schema::dropIfExists('sosial_ekonomi_penduduks');
    }
};
