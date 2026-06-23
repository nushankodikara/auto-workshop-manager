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
        // 1. Create Settings table
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // 2. Add columns to job_cards table
        Schema::table('job_cards', function (Blueprint $table) {
            $table->string('card_number')->nullable()->unique();
            $table->text('last_email')->nullable();
            $table->text('last_sms')->nullable();
        });

        // 3. Create Job Card Employee Assignments table
        Schema::create('job_card_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('assigned_at');
            $table->timestamp('unassigned_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_card_assignments');

        Schema::table('job_cards', function (Blueprint $table) {
            $table->dropColumn(['card_number', 'last_email', 'last_sms']);
        });

        Schema::dropIfExists('settings');
    }
};
