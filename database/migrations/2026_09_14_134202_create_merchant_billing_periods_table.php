<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_billing_periods', function (Blueprint $table) {
            $table->id();

            $table->foreignId('merchant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('merchant_subscription_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->dateTime('period_start');
            $table->dateTime('period_end');

            $table->unsignedInteger('included_markets')->nullable();

            $table->unsignedInteger('markets_used')->default(0);

            $table->decimal('monthly_fee', 12, 2)->default(0);

            $table->decimal('overage_amount', 12, 2)->default(0);

            $table->string('status')->default('open');
            // open | invoiced | paid | closed

            $table->timestamps();

            $table->index(
                ['merchant_id', 'period_start', 'period_end'],
                'mbp_merchant_period_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_billing_periods');
    }
};
