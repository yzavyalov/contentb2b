<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_delivery_rule_categories', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('merchant_delivery_rule_id');

            $table->foreign('merchant_delivery_rule_id', 'mdrc_rule_fk')
                ->references('id')
                ->on('merchant_delivery_rules')
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['merchant_delivery_rule_id', 'category_id'],
                'mdrc_rule_category_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_delivery_rule_categories');
    }
};
