<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_subscriptions', function (Blueprint $table) {
            $table
                ->foreignId('fallback_from_subscription_id')
                ->nullable()
                ->after('change_reason')
                ->constrained('merchant_subscriptions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('merchant_subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId(
                'fallback_from_subscription_id'
            );
        });
    }
};
