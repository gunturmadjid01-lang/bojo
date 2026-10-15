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
        Schema::create('kartu_keluargas', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_kk', 16)->unique();

            $table->foreignId('desa_id')
                ->constrained('desas')
                ->restrictOnDelete();

            $table->foreignId('dusun_id')
                ->nullable()
                ->constrained('dusuns')
                ->nullOnDelete();

            $table->foreignId('rw_id')
                ->nullable()
                ->constrained('r_w_s')
                ->nullOnDelete();

            $table->foreignId('rt_id')
                ->nullable()
                ->constrained('r_t_s')
                ->nullOnDelete();

            $table->text('alamat');
            $table->string('kode_pos', 10)->nullable();

            $table->date('tanggal_diterbitkan')->nullable();

            $table->string('status')->default('aktif');

            $table->timestamps();
            $table->softDeletes();

            $table->index('nomor_kk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kartu_keluargas');
    }
};
