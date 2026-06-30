<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Account;
use App\Models\Bill;
use App\Services\DoubleEntryService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rename Code 1000 from "Cash & Bank" to "Cash Drawer" if it exists
        DB::table('accounts')
            ->where('code', '1000')
            ->update([
                'name' => 'Cash Drawer',
                'description' => 'Primary physical cash drawer in the workshop',
                'updated_at' => now()
            ]);

        // 2. Insert Bank Account (1010) if it doesn't exist
        $exists = DB::table('accounts')->where('code', '1010')->exists();
        if (!$exists) {
            DB::table('accounts')->insert([
                'code' => '1010',
                'name' => 'Bank Account',
                'type' => 'asset',
                'description' => 'Company primary bank account balance',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // 3. Historical Bills Migration to Bookkeeping Ledger
        // We load all bills with items and sync them to double entry ledger
        $bills = Bill::with(['items', 'jobCard.vehicle.client'])->get();
        foreach ($bills as $bill) {
            DoubleEntryService::postBillTransaction($bill);
        }

        // 4. Historical Purchase Batches Migration
        $batches = \App\Models\PurchaseBatch::with('inventory')->get();
        foreach ($batches as $batch) {
            DoubleEntryService::postPurchaseBatchTransaction($batch);
        }

        // 5. Historical Paid Payroll Slips Migration
        $slips = \App\Models\PayrollSlip::where('status', 'paid')->with('user')->get();
        foreach ($slips as $slip) {
            DoubleEntryService::postPayrollSlipTransaction($slip);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert Code 1000 name
        DB::table('accounts')
            ->where('code', '1000')
            ->update([
                'name' => 'Cash & Bank',
                'description' => 'Main company bank account and cash registry',
                'updated_at' => now()
            ]);

        // Delete Bank Account (1010)
        DB::table('accounts')->where('code', '1010')->delete();
    }
};
