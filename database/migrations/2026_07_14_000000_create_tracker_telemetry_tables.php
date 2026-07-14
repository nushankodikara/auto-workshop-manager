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
        Schema::create('tracker_users', function (Blueprint $table) {
            $table->string('id')->primary(); // Tracker's user UUID
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->timestamps();
        });

        Schema::create('tracker_vehicles', function (Blueprint $table) {
            $table->string('id')->primary(); // Tracker's vehicle UUID
            $table->string('tracker_user_id'); // Tracker's user UUID
            $table->string('make');
            $table->string('model');
            $table->integer('year')->nullable();
            $table->string('plate_number');
            $table->string('default_fuel_type')->nullable();
            $table->integer('current_odometer')->default(0);
            $table->boolean('is_tdc_verified')->default(false);
            $table->unsignedBigInteger('tdc_vehicle_id')->nullable(); // linked Laravel vehicle ID
            $table->timestamps();
        });

        Schema::create('tracker_fuel_logs', function (Blueprint $table) {
            $table->string('id')->primary(); // Tracker's fuel log UUID
            $table->string('tracker_vehicle_id');
            $table->integer('odometer_km');
            $table->string('fuel_type');
            $table->double('liters')->nullable();
            $table->double('price_per_liter')->nullable();
            $table->double('total_cost')->nullable();
            $table->text('notes')->nullable();
            $table->integer('logged_at'); // unix timestamp
            $table->timestamps();
        });

        Schema::create('tracker_expense_logs', function (Blueprint $table) {
            $table->string('id')->primary(); // Tracker's expense log UUID
            $table->string('tracker_vehicle_id');
            $table->integer('odometer_km');
            $table->string('category');
            $table->double('amount');
            $table->text('notes')->nullable();
            $table->integer('logged_at'); // unix timestamp
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracker_expense_logs');
        Schema::dropIfExists('tracker_fuel_logs');
        Schema::dropIfExists('tracker_vehicles');
        Schema::dropIfExists('tracker_users');
    }
};
