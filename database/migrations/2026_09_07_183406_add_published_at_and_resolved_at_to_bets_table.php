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
                ->timestamp('published_at')
                ->nullable()
                ->after('finish_at');

            $table
                ->timestamp('resolved_at')
                ->nullable()
                ->after('published_at');

        });
    }

    public function down(): void
    {
        Schema::table('bets', function (Blueprint $table) {

            $table->dropColumn([
                'published_at',
                'resolved_at',
            ]);

        });
    }
};
