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
        Schema::create('penyaluran_bantuans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('penerima_bantuan_id')
                ->constrained('penerima_bantuans')
                ->restrictOnDelete();

            $table->string('periode')->nullable();

            $table->date('tanggal_penyaluran')->nullable();

            $table->decimal('jumlah', 18, 2)->default(0);

            $table->string('status')->default('belum_disalurkan');

            $table->string('diterima_oleh')->nullable();

            $table->string('bukti')->nullable();

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
        Schema::dropIfExists('penyaluran_bantuans');
    }
};
