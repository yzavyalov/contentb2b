<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('merchant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('billing_plan_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('status')->default('active');
            // active | suspended | cancelled | expired

            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();

            // Individual pricing overrides.
            // NULL = use value from billing_plans.
            $table->decimal('custom_monthly_fee', 12, 2)->nullable();
            $table->decimal('custom_price_per_market', 12, 2)->nullable();
            $table->unsignedInteger('custom_included_markets')->nullable();
            $table->decimal('custom_overage_price', 12, 2)->nullable();

            $table->timestamps();

            $table->index(['merchant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_subscriptions');
    }
};
