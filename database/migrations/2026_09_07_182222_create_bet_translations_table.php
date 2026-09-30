<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bet_translations', function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId('bet_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
             * en, es, de, fr, ro и т.д.
             */
            $table->string('locale', 10);

            $table->string('title');

            $table->text('description')->nullable();

            $table->timestamps();

            /*
             * Один перевод каждого языка на Bet.
             */
            $table->unique([
                'bet_id',
                'locale',
            ]);

            $table->index('locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bet_translations');
    }
};
