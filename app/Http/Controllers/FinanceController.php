<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class FinanceController extends Controller
{
    private function checkAccess()
    {
        if (app()->runningInConsole()) {
            return;
        }

        if (!Auth::user() || !Auth::user()->hasModuleAccess('finance')) {
            abort(403, 'Unauthorized module access.');
        }
    }


    /**
     * Show financial dashboard, double ledger, chart of accounts, and investor log.
     */
    public function index(Request $request)
    {
        $this->checkAccess();

        $accounts = Account::orderBy('code')->get();
        $totalShares = (int)Setting::get('total_shares', 100000);

        // Calculate accounting totals
        $assetsTotal = 0.00;
        $liabilitiesTotal = 0.00;
        $equityTotal = 0.00;

        foreach ($accounts as $acc) {
            $bal = $acc->balance;
            if ($acc->type === 'asset') {
                $assetsTotal += $bal;
            } elseif ($acc->type === 'liability') {
                $liabilitiesTotal += $bal;
            } elseif ($acc->type === 'equity') {
                $equityTotal += $bal;
            }
        }

        // Net book equity (Assets - Liabilities)
        $netEquity = $assetsTotal - $liabilitiesTotal;
        $shareValue = $totalShares > 0 ? max(0, $netEquity / $totalShares) : 0.00;

        // Fetch paginated journal entries, optionally filtered by account_id
        $selectedAccountId = $request->input('account_id');
        $journalEntriesQuery = JournalEntry::with('items.account');
        if ($selectedAccountId) {
            $journalEntriesQuery->whereHas('items', function ($q) use ($selectedAccountId) {
                $q->where('account_id', $selectedAccountId);
            });
        }
        $journalEntries = $journalEntriesQuery
            ->latest('entry_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        // Fetch customer books grouped by mobile number
        $arAccount = Account::where('code', '1200')->first();
        $customerBalances = [];
        if ($arAccount) {
            $customerBalances = JournalItem::where('account_id', $arAccount->id)
                ->whereNotNull('customer_mobile')
                ->where('customer_mobile', '!=', '')
                ->select('customer_mobile', DB::raw('SUM(debit) - SUM(credit) as balance'))
                ->groupBy('customer_mobile')
                ->get();
        }

        // Fetch investor capital transactions (Account 3200)
        $investorAccount = Account::where('code', '3200')->first();
        $investorTransactions = [];
        if ($investorAccount) {
            $investorTransactions = JournalItem::where('account_id', $investorAccount->id)
                ->with('entry')
                ->latest()
                ->get();
        }

        // Run ledger audit
        $auditResults = $this->auditLedger();

        return view('finance.index', compact(
            'accounts',
            'assetsTotal',
            'liabilitiesTotal',
            'equityTotal',
            'netEquity',
            'totalShares',
            'shareValue',
            'journalEntries',
            'customerBalances',
            'investorTransactions',
            'selectedAccountId',
            'auditResults'
        ));
    }

    /**
     * Store a new account.
     */
    public function storeAccount(Request $request)
    {
        $this->checkAccess();

        $data = $request->validate([
            'code' => 'required|string|max:20|unique:accounts,code',
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'description' => 'nullable|string'
        ]);

        Account::create($data);

        return back()->with('success', 'Account created successfully.');
    }

    /**
     * Store a manual double entry transaction.
     */
    public function storeJournalEntry(Request $request)
    {
        $this->checkAccess();

        $data = $request->validate([
            'entry_date' => 'required|date',
            'reference' => 'nullable|string|max:50',
            'description' => 'required|string',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
            'lines.*.customer_mobile' => 'nullable|string|max:20',
        ]);

        // Validate debits equal credits
        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($data['lines'] as $line) {
            $totalDebit += floatval($line['debit']);
            $totalCredit += floatval($line['credit']);
        }

        if (abs($totalDebit - $totalCredit) > 0.001) {
            return back()->withErrors(['balance' => 'Transaction unbalanced! Total Debits must equal Total Credits. (Debits: ' . number_format($totalDebit, 2) . ', Credits: ' . number_format($totalCredit, 2) . ')'])->withInput();
        }

        DB::transaction(function () use ($data) {
            $entry = JournalEntry::create([
                'entry_date' => $data['entry_date'],
                'reference' => $data['reference'] ?? null,
                'description' => $data['description']
            ]);

            foreach ($data['lines'] as $line) {
                // Skip if both debit and credit are zero
                if (floatval($line['debit']) == 0 && floatval($line['credit']) == 0) {
                    continue;
                }

                $entry->items()->create([
                    'account_id' => $line['account_id'],
                    'debit' => floatval($line['debit']),
                    'credit' => floatval($line['credit']),
                    'customer_mobile' => $line['customer_mobile'] ?? null
                ]);
            }
        });

        return back()->with('success', 'Double-entry transaction logged successfully.');
    }

    /**
     * Update an existing manual double entry transaction.
     */
    public function updateJournalEntry(Request $request, JournalEntry $journalEntry)
    {
        $this->checkAccess();

        $data = $request->validate([
            'entry_date' => 'required|date',
            'reference' => 'nullable|string|max:50',
            'description' => 'required|string',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
            'lines.*.customer_mobile' => 'nullable|string|max:20',
        ]);

        // Validate debits equal credits
        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($data['lines'] as $line) {
            $totalDebit += floatval($line['debit']);
            $totalCredit += floatval($line['credit']);
        }

        if (abs($totalDebit - $totalCredit) > 0.001) {
            return back()->withErrors(['balance' => 'Transaction unbalanced! Total Debits must equal Total Credits. (Debits: ' . number_format($totalDebit, 2) . ', Credits: ' . number_format($totalCredit, 2) . ')'])->withInput();
        }

        DB::transaction(function () use ($data, $journalEntry) {
            // Delete old items
            $journalEntry->items()->delete();

            // Update entry headers
            $journalEntry->update([
                'entry_date' => $data['entry_date'],
                'reference' => $data['reference'] ?? null,
                'description' => $data['description']
            ]);

            // Re-create items
            foreach ($data['lines'] as $line) {
                // Skip if both debit and credit are zero
                if (floatval($line['debit']) == 0 && floatval($line['credit']) == 0) {
                    continue;
                }

                $journalEntry->items()->create([
                    'account_id' => $line['account_id'],
                    'debit' => floatval($line['debit']),
                    'credit' => floatval($line['credit']),
                    'customer_mobile' => $line['customer_mobile'] ?? null
                ]);
            }
        });

        return back()->with('success', 'Double-entry transaction updated successfully.');
    }

    /**
     * Delete an existing manual double entry transaction.
     */
    public function destroyJournalEntry(JournalEntry $journalEntry)
    {
        $this->checkAccess();

        $journalEntry->delete();

        return back()->with('success', 'Transaction deleted successfully.');
    }

    /**
     * Export chart of accounts to CSV.
     */
    public function exportAccountsCsv()
    {
        $this->checkAccess();

        $accounts = Account::orderBy('code')->get();
        $fileName = 'chart_of_accounts_' . date('Ymd_His') . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use($accounts) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Account Code', 'Account Name', 'Type', 'Description', 'Current Balance']);

            foreach ($accounts as $acc) {
                fputcsv($file, [
                    $acc->code,
                    $acc->name,
                    ucfirst($acc->type),
                    $acc->description ?? '',
                    number_format($acc->balance, 2)
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export general ledger to CSV.
     */
    public function exportLedgerCsv()
    {
        $this->checkAccess();

        $ledgerLines = JournalItem::with(['entry', 'account'])->latest()->get();
        $fileName = 'general_ledger_' . date('Ymd_His') . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use($ledgerLines) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Entry ID', 'Reference', 'Description', 'Account Code', 'Account Name', 'Debit', 'Credit', 'Customer Mobile']);

            foreach ($ledgerLines as $line) {
                fputcsv($file, [
                    $line->entry->entry_date->format('Y-m-d'),
                    $line->entry->id,
                    $line->entry->reference ?? '',
                    $line->entry->description,
                    $line->account->code,
                    $line->account->name,
                    number_format($line->debit, 2),
                    number_format($line->credit, 2),
                    $line->customer_mobile ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export customer balances to CSV.
     */
    public function exportCustomerBooksCsv()
    {
        $this->checkAccess();

        $arAccount = Account::where('code', '1200')->first();
        $fileName = 'customer_balances_' . date('Ymd_His') . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use($arAccount) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Customer Mobile', 'Outstanding Balance']);

            if ($arAccount) {
                $balances = JournalItem::where('account_id', $arAccount->id)
                    ->whereNotNull('customer_mobile')
                    ->where('customer_mobile', '!=', '')
                    ->select('customer_mobile', DB::raw('SUM(debit) - SUM(credit) as balance'))
                    ->groupBy('customer_mobile')
                    ->get();

                foreach ($balances as $bal) {
                    fputcsv($file, [
                        $bal->customer_mobile,
                        number_format($bal->balance, 2)
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Audit ledger against operational records to find discrepancies.
     */
    public function auditLedger()
    {
        $this->checkAccess();

        // 1. Check Bills
        $missingBills = [];
        $duplicateBills = [];
        $bills = \App\Models\Bill::with(['jobCard.vehicle.client', 'items'])->get();
        foreach ($bills as $bill) {
            $entries = JournalEntry::where('reference', $bill->bill_number)->get();
            $payEntries = JournalEntry::where('reference', $bill->bill_number . '-PAY')->get();
            $cogsEntries = JournalEntry::where('reference', $bill->bill_number . '-COGS')->get();

            // Total parts cost for COGS check
            $partsCostTotal = 0.00;
            foreach ($bill->items as $item) {
                if ($item->type === 'part') {
                    $partsCostTotal += floatval($item->cost_price) * floatval($item->quantity);
                }
            }

            $hasInvoiceEntry = $entries->count() > 0;
            $hasPayEntry = $payEntries->count() > 0;
            $needsPayEntry = $bill->status === 'paid';
            $hasCogsEntry = $cogsEntries->count() > 0;
            $needsCogsEntry = $partsCostTotal > 0;

            if (!$hasInvoiceEntry || ($needsPayEntry && !$hasPayEntry) || ($needsCogsEntry && !$hasCogsEntry)) {
                $reasons = [];
                if (!$hasInvoiceEntry) $reasons[] = 'Invoice entry missing';
                if ($needsPayEntry && !$hasPayEntry) $reasons[] = 'Payment entry missing';
                if ($needsCogsEntry && !$hasCogsEntry) $reasons[] = 'COGS entry missing';

                $missingBills[] = [
                    'id' => $bill->id,
                    'bill_number' => $bill->bill_number,
                    'client' => $bill->jobCard->vehicle->client->name ?? 'Unknown',
                    'date' => $bill->created_at->format('Y-m-d'),
                    'total' => $bill->total_amount,
                    'status' => $bill->status,
                    'reasons' => $reasons
                ];
            }

            if ($entries->count() > 1 || $payEntries->count() > 1 || $cogsEntries->count() > 1) {
                $duplicateBills[] = [
                    'id' => $bill->id,
                    'bill_number' => $bill->bill_number,
                    'client' => $bill->jobCard->vehicle->client->name ?? 'Unknown',
                    'count' => max($entries->count(), $payEntries->count(), $cogsEntries->count())
                ];
            }
        }

        // 2. Check Purchase Batches
        $missingBatches = [];
        $duplicateBatches = [];
        $batches = \App\Models\PurchaseBatch::with('inventory')->get();
        foreach ($batches as $batch) {
            $ref = "BATCH-{$batch->id}";
            $entries = JournalEntry::where('reference', $ref)->get();

            if ($entries->count() === 0) {
                $missingBatches[] = [
                    'id' => $batch->id,
                    'batch_code' => $batch->batch_code,
                    'part' => $batch->inventory->name ?? 'Unknown',
                    'date' => $batch->purchased_at,
                    'total' => $batch->quantity_received * $batch->cost_price
                ];
            } elseif ($entries->count() > 1) {
                $duplicateBatches[] = [
                    'id' => $batch->id,
                    'batch_code' => $batch->batch_code,
                    'count' => $entries->count()
                ];
            }
        }

        // 3. Check Payroll Slips
        $missingSlips = [];
        $duplicateSlips = [];
        $slips = \App\Models\PayrollSlip::with('user')->get();
        foreach ($slips as $slip) {
            $ref = "SLIP-{$slip->id}";
            $entries = JournalEntry::where('reference', $ref)->get();

            if ($entries->count() === 0) {
                $missingSlips[] = [
                    'id' => $slip->id,
                    'employee' => $slip->user->name ?? 'Unknown',
                    'period' => "{$slip->year}-{$slip->month}",
                    'total' => $slip->net_salary
                ];
            } elseif ($entries->count() > 1) {
                $duplicateSlips[] = [
                    'id' => $slip->id,
                    'employee' => $slip->user->name ?? 'Unknown',
                    'count' => $entries->count()
                ];
            }
        }

        // 5. Check Advanced Payments
        $missingPayments = [];
        $duplicatePayments = [];
        $payments = \App\Models\JobCardAdvancedPayment::all();
        foreach ($payments as $payment) {
            $ref = "ADV-PAY-{$payment->id}";
            $entries = JournalEntry::where('reference', $ref)->get();

            if ($entries->count() === 0) {
                $missingPayments[] = [
                    'id' => $payment->id,
                    'client' => $payment->jobCard->vehicle->client->name ?? 'Unknown',
                    'date' => $payment->paid_at->format('Y-m-d'),
                    'total' => $payment->amount
                ];
            } elseif ($entries->count() > 1) {
                $duplicatePayments[] = [
                    'id' => $payment->id,
                    'client' => $payment->jobCard->vehicle->client->name ?? 'Unknown',
                    'count' => $entries->count()
                ];
            }
        }

        // 6. Check Consumable Purchases
        $missingConsumables = [];
        $duplicateConsumables = [];
        $conPurchases = \App\Models\ConsumablePurchase::with('consumable')->get();
        foreach ($conPurchases as $cp) {
            $ref = "CONS-PURCH-{$cp->id}";
            $entries = JournalEntry::where('reference', $ref)->get();

            if ($entries->count() === 0) {
                $missingConsumables[] = [
                    'id' => $cp->id,
                    'item' => $cp->consumable->name ?? 'Unknown',
                    'date' => $cp->purchased_at->format('Y-m-d'),
                    'total' => $cp->cost_price
                ];
            } elseif ($entries->count() > 1) {
                $duplicateConsumables[] = [
                    'id' => $cp->id,
                    'item' => $cp->consumable->name ?? 'Unknown',
                    'count' => $entries->count()
                ];
            }
        }

        // 7. Check Employee Salary Advances
        $missingAdvances = [];
        $duplicateAdvances = [];
        $empAdvances = \App\Models\EmployeeAdvance::with('user')->get();
        foreach ($empAdvances as $adv) {
            if ($adv->status === 'cancelled') continue;
            $ref = "ADVANCE-{$adv->id}";
            $entries = JournalEntry::where('reference', $ref)->get();

            if ($entries->count() === 0) {
                $missingAdvances[] = [
                    'id' => $adv->id,
                    'employee' => $adv->user->name ?? 'Staff',
                    'date' => $adv->advance_date ? $adv->advance_date->format('Y-m-d') : date('Y-m-d'),
                    'total' => $adv->amount
                ];
            } elseif ($entries->count() > 1) {
                $duplicateAdvances[] = [
                    'id' => $adv->id,
                    'employee' => $adv->user->name ?? 'Staff',
                    'count' => $entries->count()
                ];
            }
        }

        // 4. Check Orphaned Entries
        $orphanedEntries = [];
        $allEntries = JournalEntry::all();
        foreach ($allEntries as $entry) {
            $ref = $entry->reference;
            $isOrphan = false;
            $type = 'Other';

            if (preg_match('/^INV-\d+$/', $ref)) {
                $type = 'Invoice';
                $exists = \App\Models\Bill::where('bill_number', $ref)->exists();
                if (!$exists) $isOrphan = true;
            } elseif (preg_match('/^INV-\d+-PAY$/', $ref)) {
                $type = 'Invoice Payment';
                $billNumber = str_replace('-PAY', '', $ref);
                $exists = \App\Models\Bill::where('bill_number', $billNumber)->exists();
                if (!$exists) $isOrphan = true;
            } elseif (preg_match('/^INV-\d+-COGS$/', $ref)) {
                $type = 'COGS';
                $billNumber = str_replace('-COGS', '', $ref);
                $exists = \App\Models\Bill::where('bill_number', $billNumber)->exists();
                if (!$exists) $isOrphan = true;
            } elseif (preg_match('/^BATCH-(\d+)$/', $ref, $matches)) {
                $type = 'Purchase Batch';
                $exists = \App\Models\PurchaseBatch::where('id', $matches[1])->exists();
                if (!$exists) $isOrphan = true;
            } elseif (preg_match('/^SLIP-(\d+)$/', $ref, $matches)) {
                $type = 'Payroll Slip';
                $exists = \App\Models\PayrollSlip::where('id', $matches[1])->exists();
                if (!$exists) $isOrphan = true;
            } elseif (preg_match('/^ADV-PAY-(\d+)$/', $ref, $matches)) {
                $type = 'Advanced Payment';
                $exists = \App\Models\JobCardAdvancedPayment::where('id', $matches[1])->exists();
                if (!$exists) $isOrphan = true;
            } elseif (preg_match('/^CONS-PURCH-(\d+)$/', $ref, $matches)) {
                $type = 'Consumable Purchase';
                $exists = \App\Models\ConsumablePurchase::where('id', $matches[1])->exists();
                if (!$exists) $isOrphan = true;
            } elseif (preg_match('/^ADVANCE-(\d+)$/', $ref, $matches)) {
                $type = 'Employee Advance';
                $exists = \App\Models\EmployeeAdvance::where('id', $matches[1])->exists();
                if (!$exists) $isOrphan = true;
            }

            if ($isOrphan) {
                $orphanedEntries[] = [
                    'id' => $entry->id,
                    'reference' => $ref,
                    'description' => $entry->description,
                    'date' => $entry->entry_date,
                    'type' => $type
                ];
            }
        }

        return [
            'missingBills' => $missingBills,
            'duplicateBills' => $duplicateBills,
            'missingBatches' => $missingBatches,
            'duplicateBatches' => $duplicateBatches,
            'missingSlips' => $missingSlips,
            'duplicateSlips' => $duplicateSlips,
            'missingPayments' => $missingPayments,
            'duplicatePayments' => $duplicatePayments,
            'missingConsumables' => $missingConsumables,
            'duplicateConsumables' => $duplicateConsumables,
            'missingAdvances' => $missingAdvances,
            'duplicateAdvances' => $duplicateAdvances,
            'orphanedEntries' => $orphanedEntries
        ];
    }

    /**
     * Reconcile all ledger discrepancies.
     */
    public function reconcile(Request $request)
    {
        $this->checkAccess();

        DB::transaction(function () {
            // 0. Auto-seed any missing core accounts or settings mappings
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

            $audit = $this->auditLedger();

            // 1. Re-sync missing/duplicate bills
            $affectedBillIds = array_unique(array_merge(
                array_column($audit['missingBills'], 'id'),
                array_column($audit['duplicateBills'], 'id')
            ));
            foreach ($affectedBillIds as $billId) {
                $bill = \App\Models\Bill::find($billId);
                if ($bill) {
                    \App\Services\DoubleEntryService::postBillTransaction($bill);
                }
            }

            // 2. Re-sync missing/duplicate batches
            $affectedBatchIds = array_unique(array_merge(
                array_column($audit['missingBatches'], 'id'),
                array_column($audit['duplicateBatches'], 'id')
            ));
            foreach ($affectedBatchIds as $batchId) {
                JournalEntry::where('reference', "BATCH-{$batchId}")->delete();
                $batch = \App\Models\PurchaseBatch::find($batchId);
                if ($batch) {
                    \App\Services\DoubleEntryService::postPurchaseBatchTransaction($batch);
                }
            }

            // 3. Re-sync missing/duplicate slips
            $affectedSlipIds = array_unique(array_merge(
                array_column($audit['missingSlips'], 'id'),
                array_column($audit['duplicateSlips'], 'id')
            ));
            foreach ($affectedSlipIds as $slipId) {
                JournalEntry::where('reference', "SLIP-{$slipId}")->delete();
                $slip = \App\Models\PayrollSlip::find($slipId);
                if ($slip) {
                    \App\Services\DoubleEntryService::postPayrollSlipTransaction($slip);
                }
            }

            // 4. Re-sync missing/duplicate advanced payments
            $affectedPaymentIds = array_unique(array_merge(
                array_column($audit['missingPayments'], 'id'),
                array_column($audit['duplicatePayments'], 'id')
            ));
            foreach ($affectedPaymentIds as $paymentId) {
                JournalEntry::where('reference', "ADV-PAY-{$paymentId}")->delete();
                $payment = \App\Models\JobCardAdvancedPayment::find($paymentId);
                if ($payment) {
                    \App\Services\DoubleEntryService::postAdvancedPayment($payment);
                }
            }

            // 5. Re-sync missing/duplicate consumable purchases
            $affectedCPIds = array_unique(array_merge(
                array_column($audit['missingConsumables'], 'id'),
                array_column($audit['duplicateConsumables'], 'id')
            ));
            foreach ($affectedCPIds as $cpId) {
                JournalEntry::where('reference', "CONS-PURCH-{$cpId}")->delete();
                $cp = \App\Models\ConsumablePurchase::find($cpId);
                if ($cp) {
                    \App\Services\DoubleEntryService::postConsumablePurchase($cp);
                }
            }

            // 6. Re-sync missing/duplicate employee advances
            $affectedAdvIds = array_unique(array_merge(
                array_column($audit['missingAdvances'], 'id'),
                array_column($audit['duplicateAdvances'], 'id')
            ));
            foreach ($affectedAdvIds as $advId) {
                JournalEntry::where('reference', "ADVANCE-{$advId}")->delete();
                $adv = \App\Models\EmployeeAdvance::find($advId);
                if ($adv) {
                    \App\Services\DoubleEntryService::postEmployeeAdvanceTransaction($adv);
                }
            }

            // 6. Delete orphaned entries
            foreach ($audit['orphanedEntries'] as $orphan) {
                JournalEntry::where('id', $orphan['id'])->delete();
            }
        });

        return back()->with('success', 'Ledger reconciliation completed successfully! All missing entries posted, duplicates resolved, and orphaned records cleaned.');
    }
}
