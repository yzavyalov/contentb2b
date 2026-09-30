<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_wallets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('merchant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('currency', 10)->default('USD');

            $table->decimal('balance', 18, 2)->default(0);

            $table->timestamps();

            $table->unique([
                'merchant_id',
                'currency',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_wallets');
    }
};
