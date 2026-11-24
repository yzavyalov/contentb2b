<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_market_imports', function (Blueprint $table) {
            $table->id();

            // ID маркета в prediction-content.
            $table->unsignedBigInteger('source_market_id');

            // Соответствующий Bet в wrangle.win.
            $table->foreignId('bet_id')
                ->constrained('bets')
                ->cascadeOnDelete();

            // Время последнего успешного импорта.
            $table->timestamp('last_imported_at')->nullable();

            $table->timestamps();

            // Один исходный маркет — одна запись импорта.
            $table->unique('source_market_id');

            // Один Bet не может соответствовать нескольким импортам.
            $table->unique('bet_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_market_imports');
    }
};
