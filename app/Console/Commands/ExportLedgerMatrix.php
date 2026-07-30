<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Account;
use App\Models\JournalEntry;

class ExportLedgerMatrix extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:export-matrix {--output=full_books_ledger_matrix.csv : Filepath to write the exported CSV}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export full double-entry ledger transactions to a matrix formatted CSV file.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $outputPath = $this->option('output');
        $this->info("Starting ledger matrix export to: {$outputPath}...");

        // 1. Get accounts sorted strictly by code
        $orderedAccounts = Account::orderBy('code', 'asc')->get();

        // 2. Prepare headers
        $csvHeaders = ['Date', 'Reference', 'Description'];
        foreach ($orderedAccounts as $acc) {
            $csvHeaders[] = "{$acc->code} - {$acc->name}";
        }

        // 3. Fetch Entries
        $entries = JournalEntry::with('items.account')->orderBy('entry_date', 'asc')->orderBy('id', 'asc')->get();

        $file = fopen($outputPath, 'w');
        if (!$file) {
            $this->error("Failed to open file for writing: {$outputPath}");
            return 1;
        }

        fputcsv($file, $csvHeaders);

        $rowCount = 0;
        foreach ($entries as $entry) {
            // Sum by account_id
            $accValues = [];
            foreach ($entry->items as $item) {
                $accId = $item->account_id;
                $net = floatval($item->debit) - floatval($item->credit);
                if (!isset($accValues[$accId])) {
                    $accValues[$accId] = 0.00;
                }
                $accValues[$accId] += $net;
            }

            $row = [
                $entry->entry_date->format('Y-m-d'),
                $entry->reference ?? '',
                $entry->description
            ];

            foreach ($orderedAccounts as $acc) {
                $accId = $acc->id;
                $val = isset($accValues[$accId]) ? $accValues[$accId] : 0.00;

                if ($val > 0.001) {
                    $row[] = '+' . number_format($val, 2, '.', '');
                } elseif ($val < -0.001) {
                    $row[] = number_format($val, 2, '.', '');
                } else {
                    $row[] = '';
                }
            }

            fputcsv($file, $row);
            $rowCount++;
        }

        fclose($file);
        $this->info("Successfully exported {$rowCount} transactions to {$outputPath}!");
        return 0;
    }
}
