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
        Schema::create('perangkat_desas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penduduk_id')
                ->nullable()
                ->constrained('penduduks')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('nik', 16)->nullable();
            $table->string('nama_lengkap');
            $table->string('telepon')->nullable();
            $table->text('alamat')->nullable();

            $table->boolean('aktif')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique('user_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perangkat_desas');
    }
};
