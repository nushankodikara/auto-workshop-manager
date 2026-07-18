<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Consumable;
use App\Models\ConsumablePurchase;
use App\Models\ConsumableUsage;
use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ConsumablesTest extends TestCase
{
    use RefreshDatabase;

    protected User $superManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Create standard Chart of Accounts
        Account::create(['code' => '1000', 'name' => 'Cash & Bank', 'type' => 'asset']);
        Account::create(['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset']);
        Account::create(['code' => '1300', 'name' => 'Parts Inventory', 'type' => 'asset']);
        Account::create(['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability']);
        Account::create(['code' => '4000', 'name' => 'Service Revenue', 'type' => 'revenue']);
        Account::create(['code' => '4105', 'name' => 'Parts Revenue', 'type' => 'revenue']);
        Account::create(['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense']);
        Account::create(['code' => '5100', 'name' => 'Salaries Expense', 'type' => 'expense']);
        Account::create(['code' => '5400', 'name' => 'Tools & Consumables', 'type' => 'expense']);

        $this->superManager = User::create([
            'name' => 'Super Admin',
            'email' => 'super@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'super-manager',
            'allowed_modules' => ['dashboard', 'job-cards', 'clients', 'inventory', 'billing', 'payroll', 'settings']
        ]);
    }

    public function test_consumables_crud_actions()
    {
        $this->actingAs($this->superManager);

        // 1. Create a Consumable
        $response = $this->post(route('consumables.store'), [
            'name' => 'Wurth Brake Cleaner',
            'sku' => 'WURTH-BC-500',
            'unit' => 'cans',
            'description' => 'Brake cleaner spray 500ml'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('consumables', [
            'name' => 'Wurth Brake Cleaner',
            'sku' => 'WURTH-BC-500'
        ]);

        $consumable = Consumable::where('sku', 'WURTH-BC-500')->firstOrFail();
        $this->assertEquals(0, $consumable->quantity);

        // 2. Post a Purchase Batch
        $purchaseResponse = $this->post(route('consumables.purchase.store', $consumable->id), [
            'quantity' => 10,
            'cost_price' => 12000.00,
            'supplier' => 'Wurth Lanka',
            'purchased_at' => date('Y-m-d'),
            'payment_method' => 'cash'
        ]);

        $purchaseResponse->assertRedirect();
        $consumable->refresh();
        $this->assertEquals(10, $consumable->quantity);

        // Verify Journal Entry exists
        $purchase = ConsumablePurchase::where('consumable_id', $consumable->id)->firstOrFail();
        $this->assertNotNull($purchase->journal_entry_id);
        
        $entry = JournalEntry::find($purchase->journal_entry_id);
        $this->assertNotNull($entry);
        $this->assertEquals("CONS-PURCH-{$purchase->id}", $entry->reference);

        // 3. Log Consumption Usage
        $usageResponse = $this->post(route('consumables.usage.store', $consumable->id), [
            'quantity_consumed' => 3,
            'recorded_at' => date('Y-m-d'),
            'notes' => 'Used on vehicle WP CAD-1234'
        ]);

        $usageResponse->assertRedirect();
        $consumable->refresh();
        $this->assertEquals(7, $consumable->quantity);

        // 4. Verify Forecast Predictions
        $forecastResponse = $this->get(route('consumables.forecast', [
            'days' => 30,
            'safety_factor' => 1.2
        ]));
        $forecastResponse->assertStatus(200);

        // 5. Delete Purchase Batch and verify voided entries
        $deleteResponse = $this->delete(route('consumables.purchase.delete', $purchase->id));
        $deleteResponse->assertRedirect();
        
        $consumable->refresh();
        $this->assertEquals(-3, $consumable->quantity); // 10 - 3 logged usage - 10 removed = -3

        $this->assertDatabaseMissing('consumable_purchases', ['id' => $purchase->id]);
        $this->assertDatabaseMissing('journal_entries', ['id' => $purchase->journal_entry_id]);
    }
}
