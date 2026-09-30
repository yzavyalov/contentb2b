<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_balance_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('merchant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('type');
            // crypto_deposit
            // bank_deposit
            // subscription_charge
            // market_charge
            // refund
            // manual_credit
            // manual_debit

            $table->decimal('amount', 18, 2);

            $table->decimal('balance_before', 18, 2);
            $table->decimal('balance_after', 18, 2);

            $table->string('currency', 10)->default('USD');

            $table->nullableMorphs('reference');
            // reference_type
            // reference_id

            $table->text('description')->nullable();

            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index([
                'merchant_id',
                'type',
                'created_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_balance_transactions');
    }
};
