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
        Schema::create('bet_country', function (Blueprint $table) {

            $table
                ->foreignId('bet_id')
                ->constrained()
                ->cascadeOnDelete();

            $table
                ->foreignId('country_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->primary([
                'bet_id',
                'country_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bet_country');
    }
};
