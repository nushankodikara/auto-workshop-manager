<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Inventory;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InventoryAlertTest extends TestCase
{
    use RefreshDatabase;

    protected User $superManager;
    protected User $worker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create system users
        $this->superManager = User::create([
            'name' => 'Super Admin',
            'email' => 'super@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'super-manager'
        ]);

        $this->worker = User::create([
            'name' => 'Worker User',
            'email' => 'worker@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'worker'
        ]);
    }

    /**
     * Test custom low stock alerts logic.
     */
    public function test_custom_low_stock_alerts_filtering()
    {
        // 1. Item with threshold 5 and quantity 10 (Not Low Stock)
        $item1 = Inventory::create([
            'name' => 'Part High Stock',
            'sku' => 'PART-HIGH',
            'quantity' => 10,
            'cost_price' => 100.00,
            'selling_price' => 150.00,
            'unit' => 'pcs',
            'low_stock_alert_qty' => 5
        ]);

        // 2. Item with threshold 5 and quantity 5 (Is Low Stock)
        $item2 = Inventory::create([
            'name' => 'Part Low Stock',
            'sku' => 'PART-LOW',
            'quantity' => 5,
            'cost_price' => 200.00,
            'selling_price' => 300.00,
            'unit' => 'pcs',
            'low_stock_alert_qty' => 5
        ]);

        // 3. Item with threshold 0 and quantity 0 (No Alert - disabled)
        $item3 = Inventory::create([
            'name' => 'Part Disabled Alert',
            'sku' => 'PART-DISABLED',
            'quantity' => 0,
            'cost_price' => 50.00,
            'selling_price' => 80.00,
            'unit' => 'pcs',
            'low_stock_alert_qty' => 0
        ]);

        // Authenticate super manager and hit Dashboard
        $response = $this->actingAs($this->superManager)->get(route('dashboard'));
        $response->assertStatus(200);

        // Verify index view only has the low stock item (item2)
        $lowStockItems = $response->viewData('lowStockItems');
        $this->assertCount(1, $lowStockItems);
        $this->assertEquals($item2->id, $lowStockItems->first()->id);

        // Verify Insights statistics low stock count matches
        $response = $this->actingAs($this->superManager)->get(route('dashboard.insights'));
        $response->assertStatus(200);
        $this->assertEquals(1, $response->viewData('lowStockCount'));
    }

    /**
     * Test inventory item show details page.
     */
    public function test_inventory_show_page()
    {
        $item = Inventory::create([
            'name' => 'Part A',
            'sku' => 'PART-A',
            'quantity' => 20,
            'cost_price' => 10.00,
            'selling_price' => 15.00,
            'unit' => 'pcs',
            'low_stock_alert_qty' => 3
        ]);

        // Non-authenticated users or workers are protected (if index routes are protected, show route is too)
        // Note: index / show routes in web.php are inside 'auth' group
        $response = $this->actingAs($this->worker)->get(route('inventory.show', $item));
        $response->assertStatus(200); // Workers can view details too

        $response = $this->actingAs($this->superManager)->get(route('inventory.show', $item));
        $response->assertStatus(200);
        $response->assertSee('Part A');
        $response->assertSee('PART-A');
    }

    /**
     * Test inventory item update details.
     */
    public function test_inventory_update_details()
    {
        $item = Inventory::create([
            'name' => 'Original Name',
            'sku' => 'SKU-ORIGINAL',
            'quantity' => 10,
            'cost_price' => 100.00,
            'selling_price' => 150.00,
            'unit' => 'pcs',
            'low_stock_alert_qty' => 5
        ]);

        $response = $this->actingAs($this->superManager)->put(route('inventory.update', $item), [
            'name' => 'Updated Name',
            'sku' => 'SKU-UPDATED',
            'cost_price' => 120.00,
            'selling_price' => 180.00,
            'unit' => 'box',
            'low_stock_alert_qty' => 8
        ]);

        $response->assertSessionHasNoErrors();
        
        $item->refresh();
        $this->assertEquals('Updated Name', $item->name);
        $this->assertEquals('SKU-UPDATED', $item->sku);
        $this->assertEquals(120.00, $item->cost_price);
        $this->assertEquals(180.00, $item->selling_price);
        $this->assertEquals('box', $item->unit);
        $this->assertEquals(8, $item->low_stock_alert_qty);
    }
}
