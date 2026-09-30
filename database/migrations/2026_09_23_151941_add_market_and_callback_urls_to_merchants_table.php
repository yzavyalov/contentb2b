<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->string('market_url', 2048)
                ->nullable()
                ->after('name');

            $table->string('callback_url', 2048)
                ->nullable()
                ->after('market_url');
        });

        /*
         * Backward-compatible migration:
         *
         * The old webhook_url was used as the delivery endpoint.
         * Until the application is fully switched to the new fields,
         * copy it to both endpoints.
         */
        DB::table('merchants')
            ->whereNotNull('webhook_url')
            ->update([
                'market_url' => DB::raw('webhook_url'),
                'callback_url' => DB::raw('webhook_url'),
            ]);
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn([
                'market_url',
                'callback_url',
            ]);
        });
    }
};
