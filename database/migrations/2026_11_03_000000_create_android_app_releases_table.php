<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('android_app_releases', function (Blueprint $table): void {
            $table->id();
            $table->string('version', 40);
            $table->unsignedInteger('version_code')->unique();
            $table->string('file_path');
            $table->unsignedBigInteger('file_size');
            $table->char('sha256', 64);
            $table->text('release_notes')->nullable();
            $table->boolean('is_latest')->default(false)->index();
            $table->timestamp('published_at');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('android_app_releases');
    }
};
