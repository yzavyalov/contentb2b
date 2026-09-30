<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bets', function (Blueprint $table) {
            $table->id();

            /*
             * Контент-менеджер, создавший bet.
             */
            $table
                ->foreignId('created_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            /*
             * Supervisor.
             * Пока bet не проверен — NULL.
             */
            $table
                ->foreignId('supervisor_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('status')->default('draft');

            /*
             * Храним относительный путь:
             *
             * bets/2026/09/image.webp
             *
             * а не https://wrangle.win/storage/...
             */
            $table->string('image_path')->nullable();

            /*
             * Дата и время окончания события / спора.
             */
            $table->timestamp('finish_at');

            /*
             * Добавим FK отдельной миграцией,
             * после создания bet_answers.
             */
            $table->unsignedBigInteger('winning_answer_id')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('finish_at');
            $table->index('created_by_user_id');
            $table->index('supervisor_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bets');
    }
};
