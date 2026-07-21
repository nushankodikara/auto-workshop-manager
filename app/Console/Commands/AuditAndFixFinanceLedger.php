<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\FinanceController;
use App\Models\Account;
use App\Models\Setting;
use App\Models\EmployeeAdvance;
use App\Models\PayrollSlip;
use App\Services\DoubleEntryService;
use Illuminate\Support\Facades\DB;

class AuditAndFixFinanceLedger extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:audit-fix {--db= : Optional path to a specific SQLite database file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit and automatically repair double-entry ledger discrepancies and unposted transactions.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dbPath = $this->option('db');

        if ($dbPath) {
            $this->info("Targeting external SQLite database: {$dbPath}");
            if (!file_exists($dbPath)) {
                $this->error("Database file not found: {$dbPath}");
                return 1;
            }
            config(['database.connections.sqlite.database' => $dbPath]);
            DB::purge('sqlite');
            DB::reconnect('sqlite');
        }

        $this->info("=========================================================");
        $this->info("       TDC FINANCE & DOUBLE-ENTRY LEDGER RECONCILE       ");
        $this->info("=========================================================");

        // 1. Ensure Accounts and Settings are seeded
        $this->info("\n[Step 1] Verifying Core Chart of Accounts & Mappings...");
        $coreAccounts = [
            ['code' => '1000', 'name' => 'Main Cashbook / Cash Drawer', 'type' => 'asset'],
            ['code' => '1100', 'name' => 'Main Commercial Bank Account', 'type' => 'asset'],
            ['code' => '1200', 'name' => 'Customer Accounts Receivable (AR)', 'type' => 'asset'],
            ['code' => '1220', 'name' => 'Employee Advances & Emergency Loans', 'type' => 'asset'],
            ['code' => '1300', 'name' => 'Inventory Parts Stock Asset', 'type' => 'asset'],
            ['code' => '2000', 'name' => 'Vendor Accounts Payable (AP)', 'type' => 'liability'],
            ['code' => '2100', 'name' => 'Sales Tax / VAT Payable', 'type' => 'liability'],
            ['code' => '3000', 'name' => 'Owner Capital & Retained Earnings', 'type' => 'equity'],
            ['code' => '4000', 'name' => 'Service & Maintenance Revenue', 'type' => 'revenue'],
            ['code' => '4100', 'name' => 'Parts & Materials Revenue', 'type' => 'revenue'],
            ['code' => '5000', 'name' => 'Cost of Goods Sold (COGS)', 'type' => 'expense'],
            ['code' => '5100', 'name' => 'Salaries & Technician Wages', 'type' => 'expense'],
            ['code' => '5150', 'name' => 'Employee Benefits & Welfare Expense', 'type' => 'expense'],
            ['code' => '5200', 'name' => 'Workshop Operating Expenses', 'type' => 'expense'],
        ];

        foreach ($coreAccounts as $acc) {
            Account::firstOrCreate(['code' => $acc['code']], ['name' => $acc['name'], 'type' => $acc['type']]);
        }

        $coreSettings = [
            'account_cashbook' => '1000',
            'account_ar' => '1200',
            'account_inventory' => '1300',
            'account_ap' => '2000',
            'account_revenue' => '4000',
            'account_cogs' => '5000',
            'account_salaries' => '5100',
            'account_operating_expense' => '5200',
            'account_employee_advances' => '1220',
            'account_employee_benefits' => '5150',
        ];

        foreach ($coreSettings as $key => $code) {
            Setting::set($key, $code);
        }
        $this->info("   ✓ Chart of accounts and settings verified.");

        // 2. Audit & Reconcile via FinanceController logic
        $this->info("\n[Step 2] Executing Automatic Reconciler...");

        $fc = new FinanceController();
        $fc->reconcile(request());
        $this->info("   ✓ Reconcile execution complete.");

        // 3. Output Trial Balance Summary
        $this->info("\n[Step 3] Verification & Trial Balance Summary:");
        $accounts = Account::orderBy('code', 'ASC')->get();
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($accounts as $acc) {
            $debit = (float) DB::table('journal_items')->where('account_id', $acc->id)->sum('debit');
            $credit = (float) DB::table('journal_items')->where('account_id', $acc->id)->sum('credit');
            $totalDebit += $debit;
            $totalCredit += $credit;

            $type = strtolower($acc->type);
            $net = in_array($type, ['asset', 'expense']) ? ($debit - $credit) : ($credit - $debit);

            $this->line(sprintf("   - Code: %-6s | Type: %-10s | Debit: %12.2f | Credit: %12.2f | Net: %12.2f | %s",
                $acc->code, $acc->type, $debit, $credit, $net, $acc->name));
        }

        $this->info("\n   TOTAL JOURNAL DEBITS : " . number_format($totalDebit, 2));
        $this->info("   TOTAL JOURNAL CREDITS: " . number_format($totalCredit, 2));
        $diff = abs($totalDebit - $totalCredit);

        if ($diff < 0.001) {
            $this->info(">> SUCCESS: GENERAL LEDGER IS 100% BALANCED & RECONCILED! <<");
        } else {
            $this->error("!! WARNING: Trial balance difference of " . number_format($diff, 2) . " !!");
        }

        return 0;
    }
}
