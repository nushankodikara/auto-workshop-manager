<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Account;
use App\Models\Inventory;
use App\Models\PurchaseBatch;
use App\Models\StockMovement;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InventoryDisposalTest extends TestCase
{
    use RefreshDatabase;

    protected User $superManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed Chart of Accounts
        Account::create(['code' => '1000', 'name' => 'Cash & Bank', 'type' => 'asset']);
        Account::create(['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset']);
        Account::create(['code' => '1300', 'name' => 'Parts Inventory', 'type' => 'asset']);
        Account::create(['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability']);
        Account::create(['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense']);
        Account::create(['code' => '5600', 'name' => 'Inventory Shrinkage & Disposal Expense', 'type' => 'expense']);

        $this->superManager = User::create([
            'name' => 'Inventory Manager',
            'email' => 'invmanager@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'super-manager',
            'allowed_modules' => ['dashboard', 'inventory', 'settings']
        ]);
    }

    public function test_inventory_disposal_write_off_flow()
    {
        $this->actingAs($this->superManager);

        // 1. Create an Inventory item
        $item = Inventory::create([
            'name' => 'Synthetic Motor Oil 5W30',
            'sku' => 'OIL-5W30-1L',
            'quantity' => 20,
            'cost_price' => 3500.00,
            'selling_price' => 5000.00,
            'unit' => 'bottles',
            'low_stock_alert_qty' => 5
        ]);

        $batch = PurchaseBatch::create([
            'inventory_id' => $item->id,
            'batch_code' => 'BAT-OIL-001',
            'quantity_received' => 20,
            'quantity_remaining' => 20,
            'cost_price' => 3500.00,
            'selling_price' => 5000.00,
            'supplier' => 'Shell Lanka',
            'purchased_at' => date('Y-m-d')
        ]);

        // 2. Submit disposal request for 3 damaged bottles
        $response = $this->post(route('inventory.dispose', $item->id), [
            'quantity' => 3,
            'reason' => 'damaged',
            'notes' => 'Dropped and cracked in warehouse',
            'disposed_at' => date('Y-m-d'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // 3. Verify stock quantities
        $item->refresh();
        $batch->refresh();
        $this->assertEquals(17, $item->quantity);
        $this->assertEquals(17, $batch->quantity_remaining);

        // 4. Verify StockMovement recorded
        $movement = StockMovement::where('inventory_id', $item->id)->where('type', 'disposal')->first();
        $this->assertNotNull($movement);
        $this->assertEquals(-3, $movement->quantity);
        $this->assertEquals(3500.00, floatval($movement->cost_price));

        // 5. Verify Double Entry Journal Entry
        $ref = 'INV-DISP-' . $movement->id;
        $entry = JournalEntry::where('reference', $ref)->first();
        $this->assertNotNull($entry);

        $disposalAcc = Account::where('code', '5600')->first();
        $inventoryAcc = Account::where('code', '1300')->first();

        // Total loss: 3 * 3500.00 = 10,500.00
        $debitItem = $entry->items()->where('account_id', $disposalAcc->id)->first();
        $creditItem = $entry->items()->where('account_id', $inventoryAcc->id)->first();

        $this->assertNotNull($debitItem);
        $this->assertNotNull($creditItem);
        $this->assertEquals(10500.00, floatval($debitItem->debit));
        $this->assertEquals(0.00, floatval($debitItem->credit));
        $this->assertEquals(0.00, floatval($creditItem->debit));
        $this->assertEquals(10500.00, floatval($creditItem->credit));
    }
}
