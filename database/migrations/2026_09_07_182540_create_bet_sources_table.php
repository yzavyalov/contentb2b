<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bet_sources', function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId('bet_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('url', 2048);

            $table->unsignedTinyInteger('sort_order')->default(1);

            $table->timestamps();

            $table->index([
                'bet_id',
                'sort_order',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bet_sources');
    }
};
