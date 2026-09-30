<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('account_applications')
            ->where('status', 'pending')
            ->update([
                'status' => 'new',
            ]);

        Schema::table('account_applications', function (Blueprint $table) {
            $table->string('status', 30)
                ->default('new')
                ->change();
        });
    }

    public function down(): void
    {
        DB::table('account_applications')
            ->where('status', 'new')
            ->update([
                'status' => 'pending',
            ]);

        Schema::table('account_applications', function (Blueprint $table) {
            $table->string('status', 30)
                ->default('pending')
                ->change();
        });
    }
};
