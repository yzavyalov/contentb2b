<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bet_answer_translations', function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId('bet_answer_id')
                ->constrained('bet_answers')
                ->cascadeOnDelete();

            $table->string('locale', 10);

            $table->string('title');

            $table->timestamps();

            $table->unique([
                'bet_answer_id',
                'locale',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bet_answer_translations');
    }
};
