<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\JobCardMiscPart;
use App\Models\JobCardOutsourcing;
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
            // 1. Post all misc parts as individual ledger transactions
            $parts = JobCardMiscPart::all();
            foreach ($parts as $part) {
                DoubleEntryService::postMiscPartTransaction($part);
            }

            // 2. Post all outsourcing items as individual ledger transactions
            $items = JobCardOutsourcing::all();
            foreach ($items as $item) {
                DoubleEntryService::postOutsourcingTransaction($item);
            }

            // 3. Re-post all bills to update their entries
            $bills = Bill::all();
            foreach ($bills as $bill) {
                DoubleEntryService::postBillTransaction($bill);
            }
        } catch (\Throwable $e) {
            logger()->error("Migration migrate_misc_and_outsourcing_ledger error: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-posting is safe and idempotent; no rollback required.
    }
};
