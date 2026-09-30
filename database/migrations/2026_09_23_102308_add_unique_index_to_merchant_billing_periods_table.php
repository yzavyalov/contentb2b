<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_billing_periods', function (Blueprint $table) {
            $table->unique(
                ['merchant_subscription_id', 'period_start'],
                'merchant_billing_periods_subscription_start_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('merchant_billing_periods', function (Blueprint $table) {
            $table->dropUnique(
                'merchant_billing_periods_subscription_start_unique'
            );
        });
    }
};
