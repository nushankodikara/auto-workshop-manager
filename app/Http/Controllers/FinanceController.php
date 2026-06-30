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
        if (!Auth::user() || !Auth::user()->isSuperManager()) {
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

        // Fetch paginated journal entries
        $journalEntries = JournalEntry::with('items.account')
            ->latest('entry_date')
            ->latest('id')
            ->paginate(15);

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
            'investorTransactions'
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
}
