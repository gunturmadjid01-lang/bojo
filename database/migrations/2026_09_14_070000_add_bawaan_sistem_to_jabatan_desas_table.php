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
        Schema::table('jabatan_desas', function (Blueprint $table) {
            if (!Schema::hasColumn('jabatan_desas', 'bawaan_sistem')) {
                $table->boolean('bawaan_sistem')->default(false)->after('aktif');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jabatan_desas', function (Blueprint $table) {
            if (Schema::hasColumn('jabatan_desas', 'bawaan_sistem')) {
                $table->dropColumn('bawaan_sistem');
            }
        });
    }
};
