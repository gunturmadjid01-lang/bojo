<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penerima_bantuans', function (Blueprint $table) {
            $table->foreignId('diverifikasi_oleh')->nullable()->after('status_verifikasi')->constrained('users')->nullOnDelete();
            $table->timestamp('tanggal_verifikasi')->nullable()->after('diverifikasi_oleh');
            $table->text('catatan_verifikasi')->nullable()->after('tanggal_verifikasi');
        });
    }

    public function down(): void
    {
        Schema::table('penerima_bantuans', function (Blueprint $table) {
            $table->dropForeign(['diverifikasi_oleh']);
            $table->dropColumn(['diverifikasi_oleh', 'tanggal_verifikasi', 'catatan_verifikasi']);
        });
    }
};
