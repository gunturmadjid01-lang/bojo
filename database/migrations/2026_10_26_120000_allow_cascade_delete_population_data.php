<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') return;
        $this->rebuildCascade('peristiwa_kependudukans', 'peristiwa_kependudukans_penduduk_id_foreign', 'penduduk_id', 'penduduks');
        $this->rebuildCascade('penduduk_pindahs', 'penduduk_pindahs_penduduk_id_foreign', 'penduduk_id', 'penduduks');
        $this->rebuildCascade('anggota_perpindahan', 'anggota_perpindahan_penduduk_id_foreign', 'penduduk_id', 'penduduks');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') return;
        $this->rebuildRestrict('peristiwa_kependudukans', 'peristiwa_kependudukans_penduduk_id_foreign', 'penduduk_id', 'penduduks');
        $this->rebuildRestrict('penduduk_pindahs', 'penduduk_pindahs_penduduk_id_foreign', 'penduduk_id', 'penduduks');
        $this->rebuildRestrict('anggota_perpindahan', 'anggota_perpindahan_penduduk_id_foreign', 'penduduk_id', 'penduduks');
    }

    private function rebuildCascade(string $table, string $constraint, string $column, string $parent): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) return;
        Schema::table($table, function (Blueprint $blueprint) use ($constraint, $column, $parent): void {
            $blueprint->dropForeign($constraint);
            $blueprint->foreign($column, $constraint)->references('id')->on($parent)->cascadeOnDelete();
        });
    }

    private function rebuildRestrict(string $table, string $constraint, string $column, string $parent): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) return;
        Schema::table($table, function (Blueprint $blueprint) use ($constraint, $column, $parent): void {
            $blueprint->dropForeign($constraint);
            $blueprint->foreign($column, $constraint)->references('id')->on($parent)->restrictOnDelete();
        });
    }
};
