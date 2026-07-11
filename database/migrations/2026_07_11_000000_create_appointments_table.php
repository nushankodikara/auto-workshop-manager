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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            // Client and vehicle (nullable to allow walk-in bookings)
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();

            // Scheduling
            $table->date('appointment_date');
            $table->time('appointment_time');

            // Service details
            $table->string('service_type');
            $table->smallInteger('estimated_duration')->default(60); // minutes
            $table->text('notes')->nullable();

            // Status lifecycle: pending → confirmed → completed | no-show | cancelled
            $table->string('status')->default('pending');

            // Link set when appointment is converted to a job card
            $table->foreignId('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();

            // Notification flags — prevents duplicate sends if button pressed twice same day
            $table->boolean('notified_on_create')->default(false);
            $table->boolean('notified_day_prior')->default(false);
            $table->boolean('notified_morning')->default(false);

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Useful indices for calendar queries
            $table->index('appointment_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
