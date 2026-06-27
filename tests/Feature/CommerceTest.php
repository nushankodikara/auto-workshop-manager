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

    /**
     * Test that a regular manager cannot update details, add service, delete service,
     * allocate parts, or change status on a billed job card.
     */
    public function test_manager_cannot_modify_billed_job_cards()
    {
        $manager = User::create([
            'name' => 'Regular Manager',
            'email' => 'manager@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'manager'
        ]);

        // Generate invoice first
        $this->actingAs($this->admin)
            ->post("/job-cards/{$this->jobCard->id}/billing", [
                'tax' => 0,
                'status' => 'draft',
                'labor_desc' => ['General repair'],
                'labor_price' => [5000.00]
            ]);

        // 1. Attempt update details as manager
        $response = $this->actingAs($manager)
            ->put("/job-cards/{$this->jobCard->id}", [
                'notes' => 'Updated Notes',
                'estimated_cost' => 12000.00,
                'mileage' => 80000
            ]);
        $response->assertSessionHasErrors('bill');

        // 2. Attempt add service as manager
        $response = $this->actingAs($manager)
            ->post("/job-cards/{$this->jobCard->id}/services", [
                'name' => 'New Service',
                'price' => 1000.00
            ]);
        $response->assertSessionHasErrors('bill');

        // 3. Attempt allocate parts as manager
        $batch = PurchaseBatch::where('batch_code', 'BAT-INIT-01')->first();
        $response = $this->actingAs($manager)
            ->post("/job-cards/{$this->jobCard->id}/parts", [
                'inventory_id' => $this->part->id,
                'purchase_batch_id' => $batch->id,
                'quantity' => 1
            ]);
        $response->assertSessionHasErrors('bill');

        // 4. Attempt status change as manager
        $response = $this->actingAs($manager)
            ->patch("/job-cards/{$this->jobCard->id}/status", [
                'status' => 'on-going'
            ]);
        $response->assertSessionHasErrors('bill');
    }

    /**
     * Test that a super manager (super admin) can update details, add service, delete service,
     * allocate parts, and regenerate/update existing invoice.
     */
    public function test_super_manager_can_modify_billed_job_cards_and_regenerate_invoices()
    {
        // 1. Generate initial invoice (Rs. 5000.00 labor)
        $this->actingAs($this->admin)
            ->post("/job-cards/{$this->jobCard->id}/billing", [
                'tax' => 0,
                'status' => 'draft',
                'labor_desc' => ['General repair'],
                'labor_price' => [5000.00]
            ]);

        $this->assertDatabaseHas('bills', [
            'job_card_id' => $this->jobCard->id,
            'total_amount' => 5000.00
        ]);

        // 2. Add service task as super-manager (should be allowed)
        $response = $this->actingAs($this->admin)
            ->post("/job-cards/{$this->jobCard->id}/services", [
                'name' => 'Additional tuning',
                'price' => 2500.00
            ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('job_card_services', [
            'job_card_id' => $this->jobCard->id,
            'name' => 'Additional tuning',
            'price' => 2500.00
        ]);

        // 3. Allocate part as super-manager
        $batch = PurchaseBatch::where('batch_code', 'BAT-INIT-01')->first();
        $response = $this->actingAs($this->admin)
            ->post("/job-cards/{$this->jobCard->id}/parts", [
                'inventory_id' => $this->part->id,
                'purchase_batch_id' => $batch->id,
                'quantity' => 1
            ]);
        $response->assertRedirect();

        // 4. Update calculations (regenerate invoice) via BillingController@store
        // The parts will be automatically gathered (1 Engine Oil at Rs. 3000)
        // We supply updated labor list (labor_desc, labor_price)
        $response = $this->actingAs($this->admin)
            ->post("/job-cards/{$this->jobCard->id}/billing", [
                'tax' => 10, // 10% tax
                'status' => 'paid',
                'labor_desc' => ['General repair', 'Additional tuning'],
                'labor_price' => [5000.00, 2500.00]
            ]);
        $response->assertRedirect();

        // Verify the existing bill was updated, not duplicated
        $this->assertEquals(1, Bill::where('job_card_id', $this->jobCard->id)->count());

        // Expected amount:
        // Parts: 1 * 3000.00 = 3000.00
        // Labor: 5000.00 + 2500.00 = 7500.00
        // Subtotal: 10500.00
        // Tax: 10% of 10500.00 = 1050.00
        // Total: 11550.00
        $this->assertDatabaseHas('bills', [
            'job_card_id' => $this->jobCard->id,
            'status' => 'paid',
            'tax' => 10.00,
            'total_amount' => 11550.00
        ]);

        $this->assertDatabaseHas('bill_items', [
            'type' => 'part',
            'description' => 'Engine Oil',
            'quantity' => 1.00,
            'unit_price' => 3000.00,
            'total_price' => 3000.00
        ]);
    }

    /**
     * Test outsourcing partners, predefined services, custom cost/selling pricing, and discounts.
     */
    public function test_outsourcing_and_custom_pricing_with_discounts()
    {
        // 1. Create Outsourcing Partner Company
        $partner = \App\Models\OutsourcingCompany::create([
            'name' => 'Apex Lathe Works',
            'phone' => '0772223334',
            'email' => 'apex@lathe.com',
            'address' => 'Colombo'
        ]);

        $this->assertDatabaseHas('outsourcing_companies', [
            'name' => 'Apex Lathe Works'
        ]);

        // 2. Create Predefined Labor Service
        $predefined = \App\Models\PredefinedService::create([
            'name' => 'Engine Cylinder Boring',
            'description' => 'Standard block boring',
            'cost_price' => 4500.00,
            'selling_price' => 6000.00
        ]);

        $this->assertDatabaseHas('predefined_services', [
            'name' => 'Engine Cylinder Boring',
            'cost_price' => 4500.00
        ]);

        // Allocate a part first so we can verify custom part price
        $batch = PurchaseBatch::where('batch_code', 'BAT-INIT-01')->first();
        $this->actingAs($this->admin)
            ->post("/job-cards/{$this->jobCard->id}/parts", [
                'inventory_id' => $this->part->id,
                'purchase_batch_id' => $batch->id,
                'quantity' => 2
            ]);

        // 3. Generate Bill with custom prices, labor, outsourcing, and discount
        // Parameters:
        // Parts custom pricing: parts_cost[mov_id] = 1800, parts_price[mov_id] = 3500
        // Labor: description = "Engine Cylinder Boring", cost = 4500, price = 6500 (modified selling price)
        // Outsourcing: partner = $partner->id, description = "Crankshaft grinding", cost = 12000, price = 15000
        // Discount: 10%
        // Tax: 12%
        $mov = StockMovement::where('job_card_id', $this->jobCard->id)->where('type', 'out')->first();

        $response = $this->actingAs($this->admin)
            ->post("/job-cards/{$this->jobCard->id}/billing", [
                'tax' => 12.00,
                'discount_percent' => 10.00,
                'status' => 'paid',
                'parts_cost' => [
                    $mov->id => 1800.00
                ],
                'parts_price' => [
                    $mov->id => 3500.00
                ],
                'labor_desc' => ['Engine Cylinder Boring'],
                'labor_cost' => [4500.00],
                'labor_price' => [6500.00],
                'outsourcing_company_id' => [$partner->id],
                'outsourcing_desc' => ['Crankshaft grinding'],
                'outsourcing_cost' => [12000.00],
                'outsourcing_price' => [15000.00]
            ]);

        $response->assertRedirect();

        // 4. Verify calculations
        // Parts: 2 * 3500 = 7000 (cost: 2 * 1800 = 3600)
        // Labor: 1 * 6500 = 6500 (cost: 4500)
        // Outsourcing: 1 * 15000 = 15000 (cost: 12000)
        // Subtotal = 7000 + 6500 + 15000 = 28500
        // Discount: 10% of 28500 = 2850
        // Net Subtotal = 28500 - 2850 = 25650
        // Tax: 12% of 25650 = 3078
        // Total = 25650 + 3078 = 28728
        $this->assertDatabaseHas('bills', [
            'job_card_id' => $this->jobCard->id,
            'tax' => 12.00,
            'discount_percent' => 10.00,
            'total_amount' => 28728.00,
            'status' => 'paid'
        ]);

        // Verify bill items contain proper costs and selling prices
        $this->assertDatabaseHas('bill_items', [
            'type' => 'part',
            'description' => 'Engine Oil',
            'cost_price' => 1800.00,
            'unit_price' => 3500.00,
            'total_price' => 7000.00
        ]);

        $this->assertDatabaseHas('bill_items', [
            'type' => 'labor',
            'description' => 'Engine Cylinder Boring',
            'cost_price' => 4500.00,
            'unit_price' => 6500.00
        ]);

        $this->assertDatabaseHas('bill_items', [
            'type' => 'outsourcing',
            'outsourcing_company_id' => $partner->id,
            'description' => 'Crankshaft grinding',
            'cost_price' => 12000.00,
            'unit_price' => 15000.00
        ]);
    }
}
