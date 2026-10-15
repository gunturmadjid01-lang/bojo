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
        Schema::create('jabatan_desas', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');

            /*
    kepala-desa
    sekretaris-desa
    kepala-dusun
    */

            $table->string('tingkat')->default('desa');

            /*
    desa
    dusun
    */

            $table->boolean('aktif')->default(true);
            $table->boolean('bawaan_sistem')->default(false);
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jabatan_desas');
    }
};
