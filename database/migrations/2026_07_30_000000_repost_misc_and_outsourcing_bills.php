<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Bill;
use App\Services\DoubleEntryService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            // Retroactively find and re-post double entry ledger records for bills 
            // containing miscellaneous parts or outsourcing items
            $bills = Bill::whereHas('jobCard', function ($q) {
                $q->whereHas('miscParts')->orWhereHas('outsourcingItems');
            })->get();

            foreach ($bills as $bill) {
                DoubleEntryService::postBillTransaction($bill);
            }
        } catch (\Throwable $e) {
            logger()->error("Migration repost_misc_and_outsourcing_bills notice: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-posting is idempotent and safe; no rollback action required
    }
};
