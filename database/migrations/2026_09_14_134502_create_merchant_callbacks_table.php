<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_callbacks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('merchant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('merchant_bet_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('bet_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('event');
            // market.published
            // market.resolved

            $table->string('status')->default('pending');
            // pending
            // processing
            // delivered
            // failed

            $table->text('callback_url')->nullable();

            $table->unsignedSmallInteger('http_status')->nullable();

            $table->unsignedInteger('attempts_count')->default(0);

            $table->boolean('billable')->default(false);

            $table->timestamp('delivered_at')->nullable();

            $table->text('last_error')->nullable();

            $table->timestamps();

            $table->index([
                'merchant_id',
                'event',
                'status',
            ]);

            $table->index([
                'merchant_bet_id',
                'event',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_callbacks');
    }
};
