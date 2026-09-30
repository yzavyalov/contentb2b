<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE `merchant_subscriptions`
            CHANGE `starts_at` `starts_at`
            TIMESTAMP NOT NULL DEFAULT '2000-01-01 00:00:00'
        ");

        DB::statement("
            ALTER TABLE `merchant_subscriptions`
            ALTER `starts_at` DROP DEFAULT
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE `merchant_subscriptions`
            CHANGE `starts_at` `starts_at`
            TIMESTAMP NOT NULL
            DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP
        ");
    }
};
