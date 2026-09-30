<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE `merchant_subscriptions`
            MODIFY `starts_at` DATETIME NOT NULL,
            MODIFY `ends_at` DATETIME NULL DEFAULT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE `merchant_subscriptions`
            MODIFY `starts_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            MODIFY `ends_at` TIMESTAMP NULL DEFAULT NULL
        ");
    }
};
