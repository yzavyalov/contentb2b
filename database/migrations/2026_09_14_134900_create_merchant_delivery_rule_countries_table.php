<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_delivery_rule_countries', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('merchant_delivery_rule_id');

            $table->foreign('merchant_delivery_rule_id', 'mdrco_rule_fk')
                ->references('id')
                ->on('merchant_delivery_rules')
                ->cascadeOnDelete();

            $table->foreignId('country_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['merchant_delivery_rule_id', 'country_id'],
                'mdrco_rule_country_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_delivery_rule_countries');
    }
};
