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
        Schema::create('dusuns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desa_id')
                ->constrained('desas')
                ->restrictOnDelete();

            $table->string('kode')->nullable();
            $table->string('nama');
            $table->boolean('aktif')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['desa_id', 'nama']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dusuns');
    }
};
