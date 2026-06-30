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
        $this->assertDatabaseHas('accounts', ['code' => '1000', 'name' => 'Cash & Bank']);
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
}
