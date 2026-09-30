<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_applications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('type', 50);
            $table->string('status', 30)->default('pending');

            /*
             * Content Creator data.
             */
            $table->string('display_name')->nullable();
            $table->string('preferred_locale', 10)->nullable();

            /*
             * Merchant data.
             */
            $table->string('company_name')->nullable();

            /*
             * Contact data.
             */
            $table->string('contact_email')->nullable();
            $table->string('telegram')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();

            /*
             * Additional information from applicant.
             */
            $table->text('message')->nullable();

            /*
             * Review.
             */
            $table->foreignId('reviewed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();

            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->index(['status', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_applications');
    }
};
