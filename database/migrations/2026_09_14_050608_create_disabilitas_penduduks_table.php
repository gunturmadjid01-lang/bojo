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
        Schema::create('disabilitas_penduduks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penduduk_id')
                ->constrained('penduduks')
                ->cascadeOnDelete();

            $table->foreignId('jenis_disabilitas_id')
                ->constrained('disabilitas')
                ->restrictOnDelete();

            $table->string('tingkat')->nullable();
            $table->text('keterangan')->nullable();

            $table->unique([
                'penduduk_id',
                'jenis_disabilitas_id'
            ]);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disabilitas_penduduks');
    }
};
