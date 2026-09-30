<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_tokens', function (Blueprint $table) {

            $table->id();

            $table
                ->foreignId('merchant_id')
                ->constrained('merchants')
                ->cascadeOnDelete();

            $table->string('name')->nullable();

            // Первые символы токена, например:
            // wr_live_a8f3c2
            $table->string('token_prefix', 32);

            // SHA-256 = 64 hex-символа
            $table->string('token_hash', 64)->unique();

            $table->boolean('is_active')->default(true);

            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            $table->index('merchant_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_tokens');
    }
};
