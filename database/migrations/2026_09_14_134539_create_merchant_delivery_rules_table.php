<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_delivery_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('merchant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            $table->boolean('is_active')->default(true);

            // Минимальное количество часов до finish_at
            $table->unsignedInteger('min_finish_hours')->nullable();

            // Максимальное количество часов до finish_at
            $table->unsignedInteger('max_finish_hours')->nullable();

            $table->timestamps();

            $table->index([
                'merchant_id',
                'is_active',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_delivery_rules');
    }
};
