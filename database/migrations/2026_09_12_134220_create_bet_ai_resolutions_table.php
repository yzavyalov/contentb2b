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
        Schema::create('bet_ai_resolutions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bet_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('suggested_answer_id')
                ->nullable()
                ->constrained('bet_answers')
                ->nullOnDelete();

            $table->decimal('confidence', 5, 2)->nullable();

            $table->text('summary')->nullable();

            $table->string('status')
                ->default('pending');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->unique('bet_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bet_ai_resolutions');
    }
};
