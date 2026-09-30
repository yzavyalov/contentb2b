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
        Schema::create('country_country_group', function (Blueprint $table) {

            $table
                ->foreignId('country_group_id')
                ->constrained()
                ->cascadeOnDelete();

            $table
                ->foreignId('country_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->primary([
                'country_group_id',
                'country_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('country_country_group');
    }
};
