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
            $arCode = \App\Models\Setting::get('account_receivable', '1200');
            $partsRevCode = \App\Models\Setting::get('account_parts_revenue', '4105');
            $serviceRevCode = \App\Models\Setting::get('account_service_revenue', '4000');
            $cashCode = \App\Models\Setting::get('account_cashbook', '1000');
            $cogsCode = \App\Models\Setting::get('account_cogs', '5000');
            $inventoryCode = \App\Models\Setting::get('account_inventory', '1300');
            $transCode = \App\Models\Setting::get('account_transportation', '1030');
            $transRevCode = \App\Models\Setting::get('account_transportation_revenue', '4200');
            $transHireCode = \App\Models\Setting::get('account_transportation_hire_expense', '5500');

            $arAccount = Account::where('code', $arCode)->first();
            $partsRevAccount = Account::where('code', $partsRevCode)->first();
            $serviceRevAccount = Account::where('code', $serviceRevCode)->first();
            $cashAccount = Account::where('code', $cashCode)->first();
            $cogsAccount = Account::where('code', $cogsCode)->first();
            $inventoryAccount = Account::where('code', $inventoryCode)->first();
            $transAccount = Account::where('code', $transCode)->first();
            $transRevAccount = Account::where('code', $transRevCode)->first();
            $transHireAccount = Account::where('code', $transHireCode)->first();

            if (!$arAccount || !$partsRevAccount || !$serviceRevAccount || !$cashAccount || !$cogsAccount || !$inventoryAccount || !$transAccount || !$transRevAccount || !$transHireAccount) {
                Log::warning("DoubleEntryService: Standard or Transportation accounts not seeded. Skipping post.");
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

            // Calculate transportation total
            $transportationTotal = floatval($jobCard->transportation_fee);

            // Apply discount proportionally if any
            if ($bill->discount_percent > 0) {
                $partsTotal -= $partsTotal * (floatval($bill->discount_percent) / 100);
                $serviceTotal -= $serviceTotal * (floatval($bill->discount_percent) / 100);
                $transportationTotal -= $transportationTotal * (floatval($bill->discount_percent) / 100);
            }

            // Apply tax proportionally if any
            if ($bill->tax > 0) {
                $partsTotal += $partsTotal * (floatval($bill->tax) / 100);
                $serviceTotal += $serviceTotal * (floatval($bill->tax) / 100);
                $transportationTotal += $transportationTotal * (floatval($bill->tax) / 100);
            }

            // Total invoiced
            $invoiceTotal = $partsTotal + $serviceTotal + $transportationTotal;

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

            if ($transportationTotal > 0) {
                $invoiceEntry->items()->create([
                    'account_id' => $transRevAccount->id,
                    'debit' => 0.00,
                    'credit' => $transportationTotal,
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
                $advancedPaymentsTotal = (double)$jobCard->advancedPayments()->sum('amount');
                $finalPaymentAmount = $invoiceTotal - $advancedPaymentsTotal;

                if ($finalPaymentAmount > 0) {
                    $paymentEntry = JournalEntry::create([
                        'entry_date' => date('Y-m-d'),
                        'reference' => $bill->bill_number . '-PAY',
                        'description' => "Final payment received for Bill {$bill->bill_number} (Client: {$client->name})"
                    ]);

                    // Determine transportation payment portion
                    $transPaymentPortion = 0.00;
                    if ($transportationTotal > 0) {
                        $transPaymentPortion = min($transportationTotal, $finalPaymentAmount);
                    }
                    $cashPaymentPortion = $finalPaymentAmount - $transPaymentPortion;

                    // Debit Transportation Account if any
                    if ($transPaymentPortion > 0) {
                        $paymentEntry->items()->create([
                            'account_id' => $transAccount->id,
                            'debit' => $transPaymentPortion,
                            'credit' => 0.00,
                            'customer_mobile' => $customerMobile
                        ]);
                    }

                    // Debit Cash/Bank for the remaining portion
                    if ($cashPaymentPortion > 0) {
                        $paymentEntry->items()->create([
                            'account_id' => $cashAccount->id,
                            'debit' => $cashPaymentPortion,
                            'credit' => 0.00,
                            'customer_mobile' => $customerMobile
                        ]);
                    }

                    // Credit Accounts Receivable
                    $paymentEntry->items()->create([
                        'account_id' => $arAccount->id,
                        'debit' => 0.00,
                        'credit' => $finalPaymentAmount,
                        'customer_mobile' => $customerMobile
                    ]);
                }

                // 4. Hired Transportation Expense payout (Debit Expense, Credit Transportation Account)
                if ($jobCard->transportation_type === 'hire' && $transportationTotal > 0) {
                    $hireEntry = JournalEntry::create([
                        'entry_date' => date('Y-m-d'),
                        'reference' => $bill->bill_number . '-HIRE',
                        'description' => "Transportation hire third-party payout for Job Card {$jobCard->card_number}"
                    ]);

                    // Debit Transportation Hire Expense
                    $hireEntry->items()->create([
                        'account_id' => $transHireAccount->id,
                        'debit' => $transportationTotal,
                        'credit' => 0.00,
                        'customer_mobile' => $customerMobile
                    ]);

                    // Credit Transportation Account
                    $hireEntry->items()->create([
                        'account_id' => $transAccount->id,
                        'debit' => 0.00,
                        'credit' => $transportationTotal,
                        'customer_mobile' => $customerMobile
                    ]);
                }
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

            $inventoryCode = \App\Models\Setting::get('account_inventory', '1300');
            $cashCode = \App\Models\Setting::get('account_cashbook', '1000');

            $inventoryAccount = Account::where('code', $inventoryCode)->first();
            $cashAccount = Account::where('code', $cashCode)->first();

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

            $salariesCode = \App\Models\Setting::get('account_salaries', '5100');
            $cashCode = \App\Models\Setting::get('account_cashbook', '1000');
            $payableCode = \App\Models\Setting::get('account_payable', '2000');

            $salariesExpenseAcc = Account::where('code', $salariesCode)->first();
            if (!$salariesExpenseAcc) {
                return;
            }

            // If paid: Credit Cash Drawer. If draft: Credit Accounts Payable.
            if ($payrollSlip->status === 'paid') {
                $creditAccount = Account::where('code', $cashCode)->first();
            } else {
                $creditAccount = Account::where('code', $payableCode)->first();
            }

            if (!$creditAccount) {
                return;
            }

            $proratedSalary = floatval($payrollSlip->prorated_salary);
            $overtimeAmount = floatval($payrollSlip->overtime_amount);
            $allowance = floatval($payrollSlip->allowance);
            $deductions = floatval($payrollSlip->deductions);
            $netSalary = floatval($payrollSlip->net_salary);
            
            $grossSalary = $proratedSalary + $overtimeAmount + $allowance;
            if ($grossSalary <= 0) {
                return;
            }

            $entry = JournalEntry::create([
                'entry_date' => date('Y-m-d'),
                'reference' => 'SLIP-' . $payrollSlip->id,
                'description' => "Salary slip generated for " . ($payrollSlip->user->name ?? 'Employee') . " (Month: {$payrollSlip->month}/{$payrollSlip->year}, Status: " . strtoupper($payrollSlip->status) . ")"
            ]);

            // Debit Salaries Expense (5100)
            $entry->items()->create([
                'account_id' => $salariesExpenseAcc->id,
                'debit' => $grossSalary,
                'credit' => 0.00
            ]);

            // Credit Cash Drawer (1000) or Accounts Payable (2000)
            $entry->items()->create([
                'account_id' => $creditAccount->id,
                'debit' => 0.00,
                'credit' => $netSalary
            ]);

            // Credit Accounts Payable (2000) for deductions
            if ($deductions > 0) {
                $apAccount = Account::where('code', '2000')->first();
                if ($apAccount && $apAccount->id !== $creditAccount->id) {
                    $entry->items()->create([
                        'account_id' => $apAccount->id,
                        'debit' => 0.00,
                        'credit' => $deductions
                    ]);
                } elseif ($apAccount && $apAccount->id === $creditAccount->id) {
                    // If both net salary and deductions credit to Accounts Payable (2000), merge them or add separate line.
                    // Separate line is cleaner for audit trail
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

    /**
     * Automatically log an advanced payment on a Job Card to the ledger (Debit Cash Drawer, Credit Accounts Receivable).
     */
    public static function postAdvancedPayment($payment)
    {
        try {
            $reference = 'ADV-PAY-' . $payment->id;
            
            // Delete existing journal entries for this advanced payment
            $oldEntry = JournalEntry::where('reference', $reference)->first();
            if ($oldEntry) {
                $oldEntry->delete();
            }

            $cashCode = \App\Models\Setting::get('account_cashbook', '1000');
            $arCode = \App\Models\Setting::get('account_receivable', '1200');

            $cashAccount = Account::where('code', $cashCode)->first();
            $arAccount = Account::where('code', $arCode)->first();

            if (!$cashAccount || !$arAccount) {
                return;
            }

            $jobCard = $payment->jobCard;
            $client = $jobCard->vehicle->client;
            $customerMobile = $client->phone ?? '0000000000';

            $entry = JournalEntry::create([
                'entry_date' => $payment->paid_at ? $payment->paid_at->format('Y-m-d') : date('Y-m-d'),
                'reference' => $reference,
                'description' => "Advanced payment received for Job Card {$jobCard->card_number} (Client: {$client->name})"
            ]);

            // Debit Cash Drawer (1000)
            $entry->items()->create([
                'account_id' => $cashAccount->id,
                'debit' => floatval($payment->amount),
                'credit' => 0.00,
                'customer_mobile' => $customerMobile
            ]);

            // Credit Accounts Receivable (1200)
            $entry->items()->create([
                'account_id' => $arAccount->id,
                'debit' => 0.00,
                'credit' => floatval($payment->amount),
                'customer_mobile' => $customerMobile
            ]);

            // Save the journal entry link quiet to prevent boot event loops
            $payment->updateQuietly(['journal_entry_id' => $entry->id]);

        } catch (\Exception $e) {
            Log::error("DoubleEntryService postAdvancedPayment Error: " . $e->getMessage());
        }
    }

    /**
     * Automatically log a consumable purchase to the ledger (Debit Tools & Consumables, Credit Cash Drawer).
     */
    public static function postConsumablePurchase($purchase)
    {
        try {
            $reference = 'CONS-PURCH-' . $purchase->id;
            
            // Delete existing journal entries for this purchase
            $oldEntry = JournalEntry::where('reference', $reference)->first();
            if ($oldEntry) {
                $oldEntry->delete();
            }

            $consumablesCode = \App\Models\Setting::get('account_consumables', '5400');
            $cashCode = \App\Models\Setting::get('account_cashbook', '1000');

            $consumablesAccount = Account::where('code', $consumablesCode)->first();
            $cashAccount = Account::where('code', $cashCode)->first();

            if (!$consumablesAccount || !$cashAccount) {
                Log::warning("DoubleEntryService: Consumables or Cash accounts not found. Skipping post.");
                return;
            }

            $entry = JournalEntry::create([
                'entry_date' => $purchase->purchased_at ? $purchase->purchased_at->format('Y-m-d') : date('Y-m-d'),
                'reference' => $reference,
                'description' => "Consumable purchase batch {$purchase->batch_code} (Item: " . ($purchase->consumable->name ?? 'Unknown') . ")"
            ]);

            // Debit Tools & Consumables Expense (5400)
            $entry->items()->create([
                'account_id' => $consumablesAccount->id,
                'debit' => floatval($purchase->cost_price),
                'credit' => 0.00
            ]);

            // Credit Cash Drawer (1000)
            $entry->items()->create([
                'account_id' => $cashAccount->id,
                'debit' => 0.00,
                'credit' => floatval($purchase->cost_price)
            ]);

            // Save the journal entry link quiet
            $purchase->updateQuietly(['journal_entry_id' => $entry->id]);

        } catch (\Exception $e) {
            Log::error("DoubleEntryService postConsumablePurchase Error: " . $e->getMessage());
        }
    }
}
