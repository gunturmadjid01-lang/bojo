<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offline_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('request_id', 100);
            $table->string('fingerprint', 64);
            $table->unsignedSmallInteger('response_status');
            $table->longText('response_body')->nullable();
            $table->string('response_content_type')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_requests');
    }
};
