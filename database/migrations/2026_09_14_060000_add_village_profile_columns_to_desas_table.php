<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('desas', function (Blueprint $table): void {
            if (! Schema::hasColumn('desas', 'kode_desa')) {
                $table->string('kode_desa')->nullable()->unique();
            }
            if (! Schema::hasColumn('desas', 'nama_desa')) {
                $table->string('nama_desa')->default('Desa Bojo');
            }
            if (! Schema::hasColumn('desas', 'kecamatan')) {
                $table->string('kecamatan')->nullable();
            }
            if (! Schema::hasColumn('desas', 'kabupaten')) {
                $table->string('kabupaten')->nullable();
            }
            if (! Schema::hasColumn('desas', 'provinsi')) {
                $table->string('provinsi')->nullable();
            }
            if (! Schema::hasColumn('desas', 'kode_pos')) {
                $table->string('kode_pos', 10)->nullable();
            }
            if (! Schema::hasColumn('desas', 'alamat_kantor')) {
                $table->text('alamat_kantor')->nullable();
            }
            if (! Schema::hasColumn('desas', 'telepon')) {
                $table->string('telepon')->nullable();
            }
            if (! Schema::hasColumn('desas', 'email')) {
                $table->string('email')->nullable();
            }
            if (! Schema::hasColumn('desas', 'logo')) {
                $table->string('logo')->nullable();
            }
            if (! Schema::hasColumn('desas', 'aktif')) {
                $table->boolean('aktif')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('desas', function (Blueprint $table): void {
            foreach (['kode_desa', 'nama_desa', 'kecamatan', 'kabupaten', 'provinsi', 'kode_pos', 'alamat_kantor', 'telepon', 'email', 'logo', 'aktif'] as $column) {
                if (Schema::hasColumn('desas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
