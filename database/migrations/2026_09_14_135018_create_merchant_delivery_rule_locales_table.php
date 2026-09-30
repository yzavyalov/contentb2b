<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_delivery_rule_locales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('merchant_delivery_rule_id')
                ->constrained('merchant_delivery_rules')
                ->cascadeOnDelete();

            $table->string('locale', 10);

            $table->timestamps();

            $table->unique(
                ['merchant_delivery_rule_id', 'locale'],
                'mdrl_rule_locale_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_delivery_rule_locales');
    }
};
