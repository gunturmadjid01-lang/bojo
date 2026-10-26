<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['penduduk_pindahs', 'perpindahan_penduduks'] as $table) {
            if (Schema::hasColumn($table, 'jenis_pindah')) {
                Schema::table($table, fn ($blueprint) => $blueprint->dropColumn('jenis_pindah'));
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('penduduk_pindahs') && ! Schema::hasColumn('penduduk_pindahs', 'jenis_pindah')) {
            Schema::table('penduduk_pindahs', fn ($blueprint) => $blueprint->string('jenis_pindah')->default('individu'));
        }
        if (Schema::hasTable('perpindahan_penduduks') && ! Schema::hasColumn('perpindahan_penduduks', 'jenis_pindah')) {
            Schema::table('perpindahan_penduduks', fn ($blueprint) => $blueprint->string('jenis_pindah')->default('individu'));
        }
    }
};
