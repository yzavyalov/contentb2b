<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            $table
                ->foreign('winning_answer_id')
                ->references('id')
                ->on('bet_answers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            $table->dropForeign([
                'winning_answer_id',
            ]);
        });
    }
};
