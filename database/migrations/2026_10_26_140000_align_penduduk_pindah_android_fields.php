<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('penduduk_pindahs')) return;

        // Android tidak lagi mengirim jenis_tujuan_kk. Data lama tetap
        // dipertahankan, tetapi kolom tidak boleh memblokir data baru.
        if (Schema::hasColumn('penduduk_pindahs', 'jenis_tujuan_kk')) {
            Schema::table('penduduk_pindahs', function (Blueprint $table): void {
                $table->string('jenis_tujuan_kk')->nullable()->default(null)->change();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('penduduk_pindahs') || ! Schema::hasColumn('penduduk_pindahs', 'jenis_tujuan_kk')) return;

        // Baris lama yang kosong diberi nilai aman sebelum constraint lama
        // dipulihkan agar rollback tidak gagal.
        DB::table('penduduk_pindahs')->whereNull('jenis_tujuan_kk')->update(['jenis_tujuan_kk' => 'gabung_kk']);
        Schema::table('penduduk_pindahs', function (Blueprint $table): void {
            $table->string('jenis_tujuan_kk')->default('gabung_kk')->nullable(false)->change();
        });
    }
};
