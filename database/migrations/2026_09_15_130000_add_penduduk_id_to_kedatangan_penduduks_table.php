<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('kedatangan_penduduks', 'penduduk_id')) {
            Schema::table('kedatangan_penduduks', function (Blueprint $table): void {
                $table->foreignId('penduduk_id')->nullable()->after('kartu_keluarga_tujuan_id')->constrained('penduduks')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('kedatangan_penduduks', 'penduduk_id')) {
            Schema::table('kedatangan_penduduks', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('penduduk_id');
            });
        }
    }
};
