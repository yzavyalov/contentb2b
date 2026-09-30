<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_plans', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            $table->string('billing_type');
            // per_market | subscription | hybrid

            $table->decimal('monthly_fee', 12, 2)->default(0);
            $table->decimal('price_per_market', 12, 2)->default(0);

            $table->unsignedInteger('included_markets')->nullable();

            $table->boolean('is_unlimited')->default(false);

            $table->decimal('overage_price', 12, 2)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_plans');
    }
};
