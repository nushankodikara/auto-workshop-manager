<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Setting;
use App\Models\JobCard;
use App\Models\Client;
use App\Models\Vehicle;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FinanceBookkeepingTest extends TestCase
{
    use RefreshDatabase;

    protected User $superManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superManager = User::create([
            'name' => 'Finance Controller',
            'email' => 'finance@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'super-manager'
        ]);
    }

    /**
     * Test accounts seeded on database migration.
     */
    public function test_default_accounts_seeded()
    {
        $this->assertDatabaseHas('accounts', ['code' => '1000', 'name' => 'Cash Drawer']);
        $this->assertDatabaseHas('accounts', ['code' => '1010', 'name' => 'Bank Account']);
        $this->assertDatabaseHas('accounts', ['code' => '1200', 'name' => 'Accounts Receivable']);
        $this->assertDatabaseHas('accounts', ['code' => '3200', 'name' => 'Investor Capital']);
    }

    /**
     * Test manual journal entry submission.
     */
    public function test_manual_journal_entry_submission()
    {
        $cashAcc = Account::where('code', '1000')->first();
        $investAcc = Account::where('code', '3200')->first();

        // 1. Submit balanced transaction: Debit Cash $5000, Credit Investor Capital $5000
        $response = $this->actingAs($this->superManager)->post(route('finance.ledger.store'), [
            'entry_date' => '2026-06-30',
            'reference' => 'TEST-001',
            'description' => 'Test capital injection',
            'lines' => [
                [
                    'account_id' => $cashAcc->id,
                    'debit' => 5000.00,
                    'credit' => 0.00,
                    'customer_mobile' => '94770001111'
                ],
                [
                    'account_id' => $investAcc->id,
                    'debit' => 0.00,
                    'credit' => 5000.00,
                    'customer_mobile' => null
                ]
            ]
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('journal_entries', ['reference' => 'TEST-001']);
        
        $entry = JournalEntry::where('reference', 'TEST-001')->first();
        $this->assertEquals(2, $entry->items()->count());

        // 2. Test unbalanced transaction: Debit Cash $5000, Credit Capital $4000 (fails)
        $response = $this->actingAs($this->superManager)->post(route('finance.ledger.store'), [
            'entry_date' => '2026-06-30',
            'reference' => 'TEST-FAIL',
            'description' => 'Test unbalanced entry',
            'lines' => [
                [
                    'account_id' => $cashAcc->id,
                    'debit' => 5000.00,
                    'credit' => 0.00,
                ],
                [
                    'account_id' => $investAcc->id,
                    'debit' => 0.00,
                    'credit' => 4000.00,
                ]
            ]
        ]);

        $response->assertSessionHasErrors(['balance']);
        $this->assertDatabaseMissing('journal_entries', ['reference' => 'TEST-FAIL']);
    }

    /**
     * Test total shares setting and book share value calculations.
     */
    public function test_shares_and_share_value_calculation()
    {
        $cashAcc = Account::where('code', '1000')->first();
        $investAcc = Account::where('code', '3200')->first();

        // Inject $20,000 Cash and $20,000 Equity
        JournalEntry::create([
            'entry_date' => '2026-06-30',
            'reference' => 'SH-01',
            'description' => 'Initial capital injection'
        ]);

        $entry = JournalEntry::where('reference', 'SH-01')->first();
        $entry->items()->create(['account_id' => $cashAcc->id, 'debit' => 20000.00, 'credit' => 0.00]);
        $entry->items()->create(['account_id' => $investAcc->id, 'debit' => 0.00, 'credit' => 20000.00]);

        // Set total shares setting to 1000
        Setting::updateOrCreate(['key' => 'total_shares'], ['value' => '1000']);

        $response = $this->actingAs($this->superManager)->get(route('finance.index'));
        $response->assertStatus(200);

        // Assets = $20,000, Liabilities = $0, Equity = $20,000. Share Value = 20000/1000 = $20.00
        $response->assertViewHas('assetsTotal', 20000.00);
        $response->assertViewHas('netEquity', 20000.00);
        $response->assertViewHas('totalShares', 1000);
        $response->assertViewHas('shareValue', 20.00);
    }

    /**
     * Test CSV export endpoints download responses.
     */
    public function test_csv_exports_streams()
    {
        $response = $this->actingAs($this->superManager)->get(route('finance.export.accounts'));
        $response->assertStatus(200);
        $this->assertStringContainsString('chart_of_accounts_', $response->headers->get('Content-Disposition'));

        $response = $this->actingAs($this->superManager)->get(route('finance.export.ledger'));
        $response->assertStatus(200);
        $this->assertStringContainsString('general_ledger_', $response->headers->get('Content-Disposition'));
    }

    /**
     * Test stock purchase batches and payroll slips automatically post to bookkeeping books.
     */
    public function test_stock_purchase_and_payroll_slip_sync()
    {
        // 1. Verify stock purchase posting
        $inventoryItem = \App\Models\Inventory::create([
            'name' => 'Synthetic Engine Oil 5W-30',
            'sku' => 'OIL-5W30',
            'quantity' => 0,
            'cost_price' => 25.00,
            'selling_price' => 45.00,
            'unit' => 'liters'
        ]);

        $batch = \App\Models\PurchaseBatch::create([
            'inventory_id' => $inventoryItem->id,
            'batch_code' => 'BAT-OIL-01',
            'quantity_received' => 10,
            'quantity_remaining' => 10,
            'cost_price' => 25.00,
            'selling_price' => 45.00,
            'purchased_at' => '2026-06-30'
        ]);

        // Manually trigger or verify controller trigger
        \App\Services\DoubleEntryService::postPurchaseBatchTransaction($batch);

        $this->assertDatabaseHas('journal_entries', ['reference' => 'BATCH-' . $batch->id]);
        $entry = JournalEntry::where('reference', 'BATCH-' . $batch->id)->first();
        $this->assertEquals(250.00, $entry->items()->where('account_id', Account::where('code', '1300')->first()->id)->first()->debit);
        $this->assertEquals(250.00, $entry->items()->where('account_id', Account::where('code', '1000')->first()->id)->first()->credit);

        // 2. Verify payroll slip payout posting
        $worker = User::create([
            'name' => 'Test Worker',
            'email' => 'worker2@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'worker',
            'basic_salary' => 3000.00
        ]);

        $slip = \App\Models\PayrollSlip::create([
            'user_id' => $worker->id,
            'month' => 6,
            'year' => 2026,
            'basic_salary' => 3000.00,
            'required_days' => 20,
            'attended_days' => 20,
            'overtime_hours' => 0,
            'overtime_rate' => 0,
            'overtime_amount' => 0,
            'prorated_salary' => 3000.00,
            'net_salary' => 3000.00,
            'status' => 'draft'
        ]);

        // Put to paid
        $response = $this->actingAs($this->superManager)->patch(route('payroll.update-status', $slip->id), [
            'status' => 'paid'
        ]);

        $response->assertRedirect();
        $slip->refresh();
        $this->assertEquals('paid', $slip->status);

        $this->assertDatabaseHas('journal_entries', ['reference' => 'SLIP-' . $slip->id]);
        $slipEntry = JournalEntry::where('reference', 'SLIP-' . $slip->id)->first();
        $this->assertEquals(3000.00, $slipEntry->items()->where('account_id', Account::where('code', '5100')->first()->id)->first()->debit);
        $this->assertEquals(3000.00, $slipEntry->items()->where('account_id', Account::where('code', '1000')->first()->id)->first()->credit);
    }

    /**
     * Test statistics dashboard calculates profits directly from double entry books.
     */
    public function test_dashboard_statistics_coherent_unification()
    {
        $revAcc = Account::where('code', '4000')->first(); // Service Revenue
        $expAcc = Account::where('code', '5300')->first(); // General Expense

        // Add 5000 Revenue and 1500 Expense in bookkeeping
        $entry1 = JournalEntry::create(['entry_date' => '2026-06-30', 'description' => 'Service performed']);
        $entry1->items()->create(['account_id' => Account::where('code', '1000')->first()->id, 'debit' => 5000.00, 'credit' => 0.00]);
        $entry1->items()->create(['account_id' => $revAcc->id, 'debit' => 0.00, 'credit' => 5000.00]);

        $entry2 = JournalEntry::create(['entry_date' => '2026-06-30', 'description' => 'General Expense logged']);
        $entry2->items()->create(['account_id' => $expAcc->id, 'debit' => 1500.00, 'credit' => 0.00]);
        $entry2->items()->create(['account_id' => Account::where('code', '1000')->first()->id, 'debit' => 0.00, 'credit' => 1500.00]);

        $response = $this->actingAs($this->superManager)->get(route('dashboard.statistics', [
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30'
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('totalIncome', 5000.00);
        $response->assertViewHas('totalExpenditure', 1500.00);
        $response->assertViewHas('netProfit', 3500.00);
    }

    /**
     * Test editing and deleting manual journal entries.
     */
    public function test_journal_entry_editing_and_deletion()
    {
        $cashAcc = Account::where('code', '1000')->first();
        $investAcc = Account::where('code', '3200')->first();

        // 1. Create a balanced journal entry
        $entry = JournalEntry::create([
            'entry_date' => '2026-06-30',
            'reference' => 'EDIT-01',
            'description' => 'Original Entry Description'
        ]);
        $entry->items()->create(['account_id' => $cashAcc->id, 'debit' => 1000.00, 'credit' => 0.00]);
        $entry->items()->create(['account_id' => $investAcc->id, 'debit' => 0.00, 'credit' => 1000.00]);

        $this->assertDatabaseHas('journal_entries', ['reference' => 'EDIT-01', 'description' => 'Original Entry Description']);
        $this->assertEquals(1000.00, $cashAcc->balance);

        // 2. Edit/Update it to Debit $1500 and Credit $1500
        $response = $this->actingAs($this->superManager)->put(route('finance.entries.update', $entry->id), [
            'entry_date' => '2026-06-30',
            'reference' => 'EDIT-01-REV',
            'description' => 'Updated Entry Description',
            'lines' => [
                [
                    'account_id' => $cashAcc->id,
                    'debit' => 1500.00,
                    'credit' => 0.00,
                    'customer_mobile' => '94770002222'
                ],
                [
                    'account_id' => $investAcc->id,
                    'debit' => 0.00,
                    'credit' => 1500.00,
                    'customer_mobile' => null
                ]
            ]
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('journal_entries', [
            'id' => $entry->id,
            'reference' => 'EDIT-01-REV',
            'description' => 'Updated Entry Description'
        ]);

        // Total cash balance should now be updated to 1500
        $this->assertEquals(1500.00, $cashAcc->fresh()->balance);

        // 3. Delete/Destroy the entry
        $response = $this->actingAs($this->superManager)->delete(route('finance.entries.destroy', $entry->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check entry and items are deleted
        $this->assertDatabaseMissing('journal_entries', ['id' => $entry->id]);
        $this->assertDatabaseMissing('journal_items', ['journal_entry_id' => $entry->id]);

        // Cash balance should return to 0.00
        $this->assertEquals(0.00, $cashAcc->fresh()->balance);
    }

    /**
     * Test separate ledger view by filtering by account_id.
     */
    public function test_ledger_filtering_by_account()
    {
        $cashAcc = Account::where('code', '1000')->first();
        $bankAcc = Account::where('code', '1010')->first();
        $investAcc = Account::where('code', '3200')->first();

        // 1. Create a cash transaction (Cash and Investor)
        $entry1 = JournalEntry::create([
            'entry_date' => '2026-06-30',
            'reference' => 'TXN-CASH',
            'description' => 'Cash contribution'
        ]);
        $entry1->items()->create(['account_id' => $cashAcc->id, 'debit' => 1000.00, 'credit' => 0.00]);
        $entry1->items()->create(['account_id' => $investAcc->id, 'debit' => 0.00, 'credit' => 1000.00]);

        // 2. Create a bank transaction (Bank and Investor)
        $entry2 = JournalEntry::create([
            'entry_date' => '2026-06-30',
            'reference' => 'TXN-BANK',
            'description' => 'Bank contribution'
        ]);
        $entry2->items()->create(['account_id' => $bankAcc->id, 'debit' => 2000.00, 'credit' => 0.00]);
        $entry2->items()->create(['account_id' => $investAcc->id, 'debit' => 0.00, 'credit' => 2000.00]);

        // 3. Request finance index without filters (should return both entries)
        $response = $this->actingAs($this->superManager)->get(route('finance.index'));
        $response->assertStatus(200);
        $journalEntries = $response->viewData('journalEntries');
        $this->assertCount(2, $journalEntries->items());

        // 4. Request finance index with account_id filter for Cash Account
        $responseFilteredCash = $this->actingAs($this->superManager)->get(route('finance.index', ['account_id' => $cashAcc->id]));
        $responseFilteredCash->assertStatus(200);
        $cashEntries = $responseFilteredCash->viewData('journalEntries');
        $this->assertCount(1, $cashEntries->items());
        $this->assertEquals('TXN-CASH', $cashEntries->first()->reference);

        // 5. Request finance index with account_id filter for Bank Account
        $responseFilteredBank = $this->actingAs($this->superManager)->get(route('finance.index', ['account_id' => $bankAcc->id]));
        $responseFilteredBank->assertStatus(200);
        $bankEntries = $responseFilteredBank->viewData('journalEntries');
        $this->assertCount(1, $bankEntries->items());
        $this->assertEquals('TXN-BANK', $bankEntries->first()->reference);
    }

    /**
     * Test ledger audit diagnostics and single-button reconciliation.
     */
    public function test_ledger_audit_and_reconciliation()
    {
        // Setup shop, client, vehicle, jobCard
        $shop = Shop::create(['name' => 'Main Shop', 'address' => 'Colombo', 'contact_number' => '0112345678']);
        $client = Client::create(['name' => 'Jane Doe', 'phone' => '94771234567', 'email' => 'jane@test.com']);
        $vehicle = Vehicle::create([
            'client_id' => $client->id,
            'make' => 'Toyota',
            'model' => 'Aqua',
            'year' => 2018,
            'plate_number' => 'WP CAD-4321',
            'mileage' => 12000
        ]);
        $jobCard = JobCard::create([
            'shop_id' => $shop->id,
            'vehicle_id' => $vehicle->id,
            'card_number' => 'TDC-123456',
            'status' => 'received-vehicle',
            'estimated_cost' => 1000.00
        ]);

        // Create a bill manually without posting its transaction (to mock a missing bill entry)
        $bill = \App\Models\Bill::create([
            'job_card_id' => $jobCard->id,
            'bill_number' => 'INV-999',
            'discount_percent' => 0.00,
            'tax' => 0.00,
            'total_amount' => 5000.00,
            'status' => 'paid'
        ]);

        // Add a bill item so transaction has volume
        \App\Models\BillItem::create([
            'bill_id' => $bill->id,
            'type' => 'labor',
            'description' => 'Engine Servicing',
            'quantity' => 1,
            'cost_price' => 2000.00,
            'unit_price' => 5000.00,
            'total_price' => 5000.00
        ]);

        // 1. Visit bookkeeping dashboard and verify audit shows INV-999 as missing
        $response = $this->actingAs($this->superManager)->get(route('finance.index'));
        $response->assertStatus(200);
        
        $audit = $response->viewData('auditResults');
        $this->assertCount(1, $audit['missingBills']);
        $this->assertEquals('INV-999', $audit['missingBills'][0]['bill_number']);

        // 2. Perform reconciliation
        $reconcileResponse = $this->actingAs($this->superManager)->post(route('finance.reconcile'));
        $reconcileResponse->assertRedirect();
        
        // 3. Verify ledger transactions for INV-999 have been created
        $this->assertDatabaseHas('journal_entries', ['reference' => 'INV-999']);
        $this->assertDatabaseHas('journal_entries', ['reference' => 'INV-999-PAY']);

        // 4. Visit dashboard again, verify audit has cleared the issue
        $response2 = $this->actingAs($this->superManager)->get(route('finance.index'));
        $audit2 = $response2->viewData('auditResults');
        $this->assertCount(0, $audit2['missingBills']);
    }
}
