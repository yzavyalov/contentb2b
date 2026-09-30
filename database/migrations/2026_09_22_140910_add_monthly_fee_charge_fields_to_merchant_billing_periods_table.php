<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_billing_periods', function (Blueprint $table) {
            $table->timestamp('monthly_fee_charged_at')
                ->nullable()
                ->after('monthly_fee');

            $table->unsignedBigInteger('monthly_fee_transaction_id')
                ->nullable()
                ->after('monthly_fee_charged_at');

            $table->foreign('monthly_fee_transaction_id')
                ->references('id')
                ->on('merchant_balance_transactions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('merchant_billing_periods', function (Blueprint $table) {
            $table->dropForeign([
                'monthly_fee_transaction_id',
            ]);

            $table->dropColumn([
                'monthly_fee_charged_at',
                'monthly_fee_transaction_id',
            ]);
        });
    }
};
