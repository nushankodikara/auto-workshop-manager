<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\Vehicle;
use App\Models\JobCard;
use App\Models\Inventory;
use App\Models\PurchaseBatch;
use App\Models\StockMovement;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\PayrollSlip;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CommerceTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $worker;
    protected $shop;
    protected $client;
    protected $vehicle;
    protected $jobCard;
    protected $part;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $this->admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'super-manager'
        ]);

        $this->worker = User::create([
            'name' => 'Tech Worker',
            'email' => 'worker@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'worker',
            'basic_salary' => 60000.00
        ]);

        $this->shop = Shop::create([
            'name' => 'Main Workshop',
            'address' => '123 Test Lane'
        ]);

        $this->client = Client::create([
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'phone' => '+94771234567',
            'address' => 'Colombo'
        ]);

        $this->vehicle = Vehicle::create([
            'client_id' => $this->client->id,
            'make' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2015,
            'plate_number' => 'WP CAD-1234',
            'vin' => '123456789'
        ]);

        $this->jobCard = JobCard::create([
            'vehicle_id' => $this->vehicle->id,
            'shop_id' => $this->shop->id,
            'status' => 'received-vehicle',
            'notes' => 'Test Job Card',
            'estimated_cost' => 15000.00
        ]);

        // Create inventory part
        $this->part = Inventory::create([
            'name' => 'Engine Oil',
            'sku' => 'OIL-5W30',
            'quantity' => 10,
            'cost_price' => 2000.00,
            'selling_price' => 3000.00,
            'unit' => 'liters'
        ]);

        // Create initial batch
        PurchaseBatch::create([
            'inventory_id' => $this->part->id,
            'batch_code' => 'BAT-INIT-01',
            'quantity_received' => 10,
            'quantity_remaining' => 10,
            'cost_price' => 2000.00,
            'selling_price' => 3000.00,
            'supplier' => 'Initial Supplier',
            'purchased_at' => date('Y-m-d')
        ]);
    }

    /**
     * Test adding a purchase batch increments inventory quantity and updates latest prices.
     */
    public function test_adding_purchase_batch_increments_quantity_and_updates_prices()
    {
        $response = $this->actingAs($this->admin)
            ->post("/inventory/{$this->part->id}/batch", [
                'batch_code' => 'BAT-NEW-01',
                'quantity' => 5,
                'cost_price' => 2200.00,
                'selling_price' => 3300.00,
                'supplier' => 'New Supplier',
                'purchased_at' => date('Y-m-d')
            ]);

        $response->assertRedirect();
        
        // Refresh part
        $this->part->refresh();
        $this->assertEquals(15, $this->part->quantity);
        $this->assertEquals(2200.00, $this->part->cost_price);
        $this->assertEquals(3300.00, $this->part->selling_price);

        // Verify batch exists
        $this->assertDatabaseHas('purchase_batches', [
            'batch_code' => 'BAT-NEW-01',
            'quantity_remaining' => 5,
            'cost_price' => 2200.00
        ]);
    }

    /**
     * Test allocating parts from a specific batch deducts from that batch's quantity.
     */
    public function test_allocating_parts_deducts_from_batch_quantity()
    {
        $batch = PurchaseBatch::where('batch_code', 'BAT-INIT-01')->first();

        $response = $this->actingAs($this->admin)
            ->post("/job-cards/{$this->jobCard->id}/parts", [
                'inventory_id' => $this->part->id,
                'purchase_batch_id' => $batch->id,
                'quantity' => 3
            ]);

        $response->assertRedirect();

        $batch->refresh();
        $this->part->refresh();

        $this->assertEquals(7, $batch->quantity_remaining);
        $this->assertEquals(7, $this->part->quantity);

        // Verify stock movement recorded correct batch and cost
        $this->assertDatabaseHas('stock_movements', [
            'job_card_id' => $this->jobCard->id,
            'inventory_id' => $this->part->id,
            'purchase_batch_id' => $batch->id,
            'quantity' => -3,
            'cost_price' => 2000.00
        ]);
    }

    /**
     * Test invoice generation captures correct unit selling price and cost price.
     */
    public function test_invoice_generation_captures_correct_selling_and_cost_prices()
    {
        $batch = PurchaseBatch::where('batch_code', 'BAT-INIT-01')->first();

        // Allocate parts first
        $this->actingAs($this->admin)
            ->post("/job-cards/{$this->jobCard->id}/parts", [
                'inventory_id' => $this->part->id,
                'purchase_batch_id' => $batch->id,
                'quantity' => 2
            ]);

        // Generate invoice
        $response = $this->actingAs($this->admin)
            ->post("/job-cards/{$this->jobCard->id}/billing", [
                'tax' => 0,
                'status' => 'paid',
                'labor_desc' => ['General repair'],
                'labor_price' => [5000.00]
            ]);

        $response->assertRedirect();

        // Verify bill item matches prices
        $this->assertDatabaseHas('bill_items', [
            'inventory_id' => $this->part->id,
            'type' => 'part',
            'quantity' => 2,
            'cost_price' => 2000.00,
            'unit_price' => 3000.00,
            'total_price' => 6000.00
        ]);

        $this->assertDatabaseHas('bill_items', [
            'type' => 'labor',
            'unit_price' => 5000.00,
            'total_price' => 5000.00
        ]);
    }

    /**
     * Test statistics calculation matches totals.
     */
    public function test_statistics_calculation_matches_totals()
    {
        $batch = PurchaseBatch::where('batch_code', 'BAT-INIT-01')->first();

        // Allocate and generate paid bill
        $this->actingAs($this->admin)
            ->post("/job-cards/{$this->jobCard->id}/parts", [
                'inventory_id' => $this->part->id,
                'purchase_batch_id' => $batch->id,
                'quantity' => 2
            ]);

        $this->actingAs($this->admin)
            ->post("/job-cards/{$this->jobCard->id}/billing", [
                'tax' => 0,
                'status' => 'paid',
                'labor_desc' => ['General repair'],
                'labor_price' => [5000.00]
            ]);

        // Seed a paid payroll slip
        PayrollSlip::create([
            'user_id' => $this->worker->id,
            'month' => (int)date('m'),
            'year' => (int)date('Y'),
            'basic_salary' => 60000.00,
            'allowance' => 4000.00,
            'deductions' => 1000.00,
            'net_salary' => 63000.00,
            'status' => 'paid'
        ]);

        // Fetch statistics page
        $response = $this->actingAs($this->admin)
            ->get('/statistics');

        $response->assertStatus(200);

        // Check view variables passed
        $response->assertViewHas('totalIncome', 11000.00); // 2 * 3000 (parts) + 5000 (labor)
        $response->assertViewHas('totalStockPurchases', 20000.00); // 10 * 2000 (initial batch)
        $response->assertViewHas('totalPayroll', 64000.00); // basic 60000 + allowance 4000
        $response->assertViewHas('totalExpenditure', 84000.00); // 20000 + 64000
        $response->assertViewHas('netProfit', 11000.00 - 84000.00);
        $response->assertViewHas('partsRevenue', 6000.00);
        $response->assertViewHas('partsCOGS', 4000.00); // 2 * 2000
        $response->assertViewHas('partsProfit', 2000.00);
        $response->assertViewHas('laborRevenue', 5000.00);
    }
}
