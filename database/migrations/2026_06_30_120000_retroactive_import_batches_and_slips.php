<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\PurchaseBatch;
use App\Models\PayrollSlip;
use App\Services\DoubleEntryService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Retroactively import all existing purchase batches to double-entry ledger
        $batches = PurchaseBatch::with('inventory')->get();
        foreach ($batches as $batch) {
            DoubleEntryService::postPurchaseBatchTransaction($batch);
        }

        // 2. Retroactively import all existing paid payroll slips to double-entry ledger
        $slips = PayrollSlip::where('status', 'paid')->with('user')->get();
        foreach ($slips as $slip) {
            DoubleEntryService::postPayrollSlipTransaction($slip);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete all ledger logs created for batches and slips
        \App\Models\JournalEntry::where('reference', 'like', 'BATCH-%')
            ->orWhere('reference', 'like', 'SLIP-%')
            ->delete();
    }
};
