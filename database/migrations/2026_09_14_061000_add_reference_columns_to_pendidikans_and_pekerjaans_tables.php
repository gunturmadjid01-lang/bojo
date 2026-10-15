<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['pendidikans', 'pekerjaans'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::hasColumn($tableName, 'kode')) {
                    $table->string('kode')->nullable()->unique();
                }

                if (! Schema::hasColumn($tableName, 'nama')) {
                    $table->string('nama')->default('Lainnya');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['pendidikans', 'pekerjaans'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (Schema::hasColumn($tableName, 'kode')) {
                    $table->dropColumn('kode');
                }

                if (Schema::hasColumn($tableName, 'nama')) {
                    $table->dropColumn('nama');
                }
            });
        }
    }
};
