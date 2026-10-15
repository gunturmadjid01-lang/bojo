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
        Schema::create('penduduks', function (Blueprint $table) {
            $table->id();

            $table->string('nik', 16)->nullable()->unique();
            $table->string('nama_lengkap');

            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();

            $table->string('jenis_kelamin', 20);
            $table->string('agama', 30)->nullable();
            $table->string('golongan_darah', 5)->nullable();

            $table->string('status_perkawinan', 30)->nullable();

            $table->foreignId('pendidikan_id')
                ->nullable()
                ->constrained('pendidikans')
                ->nullOnDelete();

            $table->foreignId('pekerjaan_id')
                ->nullable()
                ->constrained('pekerjaans')
                ->nullOnDelete();

            $table->string('kewarganegaraan')->default('WNI');

            $table->string('nama_ayah')->nullable();
            $table->string('nama_ibu')->nullable();

            $table->string('telepon')->nullable();
            $table->string('email')->nullable();

            $table->text('alamat')->nullable();

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

            $table->string('status_penduduk')->default('aktif');

            /*
    aktif
    pindah
    meninggal
    sementara
    */

            $table->boolean('masih_hidup')->default(true);

            $table->string('foto')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('nama_lengkap');
            $table->index('status_penduduk');
            $table->index('tanggal_lahir');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penduduks');
    }
};
