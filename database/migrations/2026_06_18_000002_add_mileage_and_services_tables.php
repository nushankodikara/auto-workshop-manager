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
        // Add mileage to vehicles
        Schema::table('vehicles', function (Blueprint $table) {
            $table->integer('mileage')->nullable()->after('vin');
        });

        // Add mileage to job_cards
        Schema::table('job_cards', function (Blueprint $table) {
            $table->integer('mileage')->nullable()->after('estimated_cost');
        });

        // Create job_card_services table
        Schema::create('job_card_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->constrained('job_cards')->onDelete('cascade');
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_card_services');

        Schema::table('job_cards', function (Blueprint $table) {
            $table->dropColumn('mileage');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('mileage');
        });
    }
};
