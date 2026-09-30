<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_bets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('merchant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('bet_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('status')->default('queued');
            // queued
            // delivered
            // resolved
            // callback_pending
            // callback_delivered
            // callback_failed

            $table->string('delivery_source')->default('manual');
            // manual | automatic

            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->string('callback_status')->nullable();
            $table->timestamp('callback_delivered_at')->nullable();

            $table->string('billing_mode')->nullable();
            // per_market | subscription | hybrid

            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('charged_amount', 12, 2)->default(0);

            $table->timestamp('charged_at')->nullable();

            $table->timestamps();

            $table->unique([
                'merchant_id',
                'bet_id',
            ]);

            $table->index([
                'merchant_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_bets');
    }
};
