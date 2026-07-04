<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use Illuminate\Support\Facades\Log;

class DoubleEntryService
{
    /**
     * Automatically log a bill invoice to the double-entry accounting system.
     */
    public static function postBillTransaction($bill)
    {
        try {
            // 1. Delete existing entries for this bill to avoid duplication on edit/re-save
            $oldEntries = JournalEntry::where('reference', 'like', $bill->bill_number . '%')->get();
            foreach ($oldEntries as $old) {
                $old->delete();
            }

            // Get standard accounts
            $arAccount = Account::where('code', '1200')->first();
            $partsRevAccount = Account::where('code', '4105')->orWhere('code', '4100')->first();
            $serviceRevAccount = Account::where('code', '4000')->first();
            $cashAccount = Account::where('code', '1000')->first();
            $cogsAccount = Account::where('code', '5000')->first();
            $inventoryAccount = Account::where('code', '1300')->first();

            if (!$arAccount || !$partsRevAccount || !$serviceRevAccount || !$cashAccount || !$cogsAccount || !$inventoryAccount) {
                Log::warning("DoubleEntryService: Standard accounts not seeded. Skipping post.");
                return;
            }

            $jobCard = $bill->jobCard;
            $client = $jobCard->vehicle->client;
            $customerMobile = $client->phone ?? '0000000000';

            // Calculate parts and service totals
            $partsTotal = 0.00;
            $partsCostTotal = 0.00;
            $serviceTotal = 0.00; // labor + outsourcing

            foreach ($bill->items as $item) {
                if ($item->type === 'part') {
                    $partsTotal += floatval($item->total_price);
                    $partsCostTotal += floatval($item->cost_price) * floatval($item->quantity);
                } else {
                    $serviceTotal += floatval($item->total_price);
                }
            }

            // Apply discount proportionally if any
            if ($bill->discount_percent > 0) {
                $partsTotal -= $partsTotal * (floatval($bill->discount_percent) / 100);
                $serviceTotal -= $serviceTotal * (floatval($bill->discount_percent) / 100);
            }

            // Apply tax proportionally if any
            if ($bill->tax > 0) {
                $partsTotal += $partsTotal * (floatval($bill->tax) / 100);
                $serviceTotal += $serviceTotal * (floatval($bill->tax) / 100);
            }

            // Total invoiced
            $invoiceTotal = $partsTotal + $serviceTotal;

            // Adjust rounding discrepancy to service revenue
            $diff = floatval($bill->total_amount) - $invoiceTotal;
            if (abs($diff) > 0.001) {
                $serviceTotal += $diff;
                $invoiceTotal = floatval($bill->total_amount);
            }

            if ($invoiceTotal <= 0) {
                return;
            }

            // 1. Invoicing Entry (Accounts Receivable debit, Revenues credit)
            $invoiceEntry = JournalEntry::create([
                'entry_date' => date('Y-m-d'),
                'reference' => $bill->bill_number,
                'description' => "Invoice generated for Job Card {$jobCard->card_number} (Client: {$client->name})"
            ]);

            // Debit Accounts Receivable
            $invoiceEntry->items()->create([
                'account_id' => $arAccount->id,
                'debit' => $invoiceTotal,
                'credit' => 0.00,
                'customer_mobile' => $customerMobile
            ]);

            // Credit Revenues
            if ($partsTotal > 0) {
                $invoiceEntry->items()->create([
                    'account_id' => $partsRevAccount->id,
                    'debit' => 0.00,
                    'credit' => $partsTotal,
                    'customer_mobile' => $customerMobile
                ]);
            }

            if ($serviceTotal > 0) {
                $invoiceEntry->items()->create([
                    'account_id' => $serviceRevAccount->id,
                    'debit' => 0.00,
                    'credit' => $serviceTotal,
                    'customer_mobile' => $customerMobile
                ]);
            }

            // 2. Cost of Goods Sold Entry (COGS debit, Inventory credit)
            if ($partsCostTotal > 0) {
                $cogsEntry = JournalEntry::create([
                    'entry_date' => date('Y-m-d'),
                    'reference' => $bill->bill_number . '-COGS',
                    'description' => "Cost of Goods Sold for Bill {$bill->bill_number} (Client: {$client->name})"
                ]);

                // Debit COGS
                $cogsEntry->items()->create([
                    'account_id' => $cogsAccount->id,
                    'debit' => $partsCostTotal,
                    'credit' => 0.00,
                    'customer_mobile' => $customerMobile
                ]);

                // Credit Parts Inventory
                $cogsEntry->items()->create([
                    'account_id' => $inventoryAccount->id,
                    'debit' => 0.00,
                    'credit' => $partsCostTotal,
                    'customer_mobile' => $customerMobile
                ]);
            }

            // 3. Payment Entry (Cash/Bank debit, Accounts Receivable credit)
            if ($bill->status === 'paid') {
                $paymentEntry = JournalEntry::create([
                    'entry_date' => date('Y-m-d'),
                    'reference' => $bill->bill_number . '-PAY',
                    'description' => "Payment received for Bill {$bill->bill_number} (Client: {$client->name})"
                ]);

                // Debit Cash/Bank
                $paymentEntry->items()->create([
                    'account_id' => $cashAccount->id,
                    'debit' => $invoiceTotal,
                    'credit' => 0.00,
                    'customer_mobile' => $customerMobile
                ]);

                // Credit Accounts Receivable
                $paymentEntry->items()->create([
                    'account_id' => $arAccount->id,
                    'debit' => 0.00,
                    'credit' => $invoiceTotal,
                    'customer_mobile' => $customerMobile
                ]);
            }
        } catch (\Exception $e) {
            Log::error("DoubleEntryService Error: " . $e->getMessage());
        }
    }

    /**
     * Automatically log a stock purchase batch to the ledger (Debit Parts Inventory, Credit Cash Drawer).
     */
    public static function postPurchaseBatchTransaction($batch)
    {
        try {
            // Delete existing entries for this batch to avoid duplication
            $oldEntry = JournalEntry::where('reference', 'BATCH-' . $batch->id)->first();
            if ($oldEntry) {
                $oldEntry->delete();
            }

            $inventoryAccount = Account::where('code', '1300')->first();
            $cashAccount = Account::where('code', '1000')->first();

            if (!$inventoryAccount || !$cashAccount) {
                return;
            }

            $totalCost = floatval($batch->quantity_received) * floatval($batch->cost_price);
            if ($totalCost <= 0) {
                return;
            }

            $entry = JournalEntry::create([
                'entry_date' => $batch->purchased_at ?? date('Y-m-d'),
                'reference' => 'BATCH-' . $batch->id,
                'description' => "Stock purchase batch {$batch->batch_code} (Item: " . ($batch->inventory->name ?? 'Unknown') . ")"
            ]);

            // Debit Parts Inventory (1300)
            $entry->items()->create([
                'account_id' => $inventoryAccount->id,
                'debit' => $totalCost,
                'credit' => 0.00
            ]);

            // Credit Cash Drawer (1000)
            $entry->items()->create([
                'account_id' => $cashAccount->id,
                'debit' => 0.00,
                'credit' => $totalCost
            ]);
        } catch (\Exception $e) {
            Log::error("DoubleEntryService postPurchaseBatchTransaction Error: " . $e->getMessage());
        }
    }

    /**
     * Automatically log employee payroll salary slip payout to the ledger (Debit Salaries Expense, Credit Cash Drawer).
     */
    public static function postPayrollSlipTransaction($payrollSlip)
    {
        try {
            // Delete existing entries for this slip to avoid duplication
            $oldEntry = JournalEntry::where('reference', 'SLIP-' . $payrollSlip->id)->first();
            if ($oldEntry) {
                $oldEntry->delete();
            }

            if ($payrollSlip->status !== 'paid') {
                return;
            }

            $salariesExpenseAcc = Account::where('code', '5100')->first();
            $cashAccount = Account::where('code', '1000')->first();

            if (!$salariesExpenseAcc || !$cashAccount) {
                return;
            }

            $basic = floatval($payrollSlip->basic_salary);
            $allowance = floatval($payrollSlip->allowance);
            $deductions = floatval($payrollSlip->deductions);
            $netSalary = floatval($payrollSlip->net_salary);
            
            $grossSalary = $basic + $allowance;
            if ($grossSalary <= 0) {
                return;
            }

            $entry = JournalEntry::create([
                'entry_date' => date('Y-m-d'),
                'reference' => 'SLIP-' . $payrollSlip->id,
                'description' => "Salary payout for " . ($payrollSlip->user->name ?? 'Employee') . " (Month: {$payrollSlip->month}/{$payrollSlip->year})"
            ]);

            // Debit Salaries Expense (5100)
            $entry->items()->create([
                'account_id' => $salariesExpenseAcc->id,
                'debit' => $grossSalary,
                'credit' => 0.00
            ]);

            // Credit Cash Drawer (1000)
            $entry->items()->create([
                'account_id' => $cashAccount->id,
                'debit' => 0.00,
                'credit' => $netSalary
            ]);

            // Credit Accounts Payable (2000) for deductions
            if ($deductions > 0) {
                $apAccount = Account::where('code', '2000')->first();
                if ($apAccount) {
                    $entry->items()->create([
                        'account_id' => $apAccount->id,
                        'debit' => 0.00,
                        'credit' => $deductions
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error("DoubleEntryService postPayrollSlipTransaction Error: " . $e->getMessage());
        }
    }
}
