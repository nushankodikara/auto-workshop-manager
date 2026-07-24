<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            // Update all job cards where mileage is null or 0 with their corresponding vehicle's mileage
            DB::statement("
                UPDATE job_cards 
                SET mileage = (
                    SELECT mileage 
                    FROM vehicles 
                    WHERE vehicles.id = job_cards.vehicle_id
                ) 
                WHERE mileage IS NULL OR mileage = 0
            ");
        } catch (\Throwable $e) {
            logger()->error("Migration backfill_job_card_mileage error: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse operation needed for a data backfill migration
    }
};
