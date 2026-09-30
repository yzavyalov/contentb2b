<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('merchants', function (Blueprint $table) {

            $table->id();

            $table
                ->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            $table->string('webhook_url')->nullable();

            $table->boolean('is_paid')->default(false);

            $table->timestamps();

            $table->index('user_id');
            $table->index('is_paid');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
