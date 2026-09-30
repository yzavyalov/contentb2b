<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bet_ai_source_checks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bet_id')
                ->constrained('bets')
                ->cascadeOnDelete();

            $table->foreignId('bet_ai_resolution_id')
                ->constrained('bet_ai_resolutions')
                ->cascadeOnDelete();

            $table->foreignId('suggested_answer_id')
                ->nullable()
                ->constrained('bet_answers')
                ->nullOnDelete();

            // provided = ссылка из bet_sources
            // google   = источник, найденный AI через Google Search
            $table->string('source_type', 32);

            $table->text('url');

            $table->string('title')
                ->nullable();

            // 0.00 - 100.00
            $table->decimal('confidence', 5, 2)
                ->default(0);

            // Как AI интерпретировал источник
            $table->text('interpretation')
                ->nullable();

            // Конкретное найденное доказательство результата
            $table->text('evidence')
                ->nullable();

            $table->boolean('is_success')
                ->default(false);

            // Если страницу не удалось прочитать,
            // результат не найден и т.п.
            $table->text('error')
                ->nullable();

            $table->timestamp('checked_at')
                ->nullable();

            $table->timestamps();

            $table->index([
                'bet_id',
                'source_type',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bet_ai_source_checks');
    }
};
