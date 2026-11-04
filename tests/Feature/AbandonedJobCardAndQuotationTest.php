<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Bill;
use App\Models\Client;
use App\Models\Inventory;
use App\Models\JobCard;
use App\Models\JournalEntry;
use App\Models\PurchaseBatch;
use App\Models\Quotation;
use App\Models\Shop;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\DoubleEntryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AbandonedJobCardAndQuotationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superManager;
    protected User $manager;
    protected Shop $shop;
    protected Client $client;
    protected Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superManager = User::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => Hash::make('Secret123!'),
            'role' => 'super-manager'
        ]);

        $this->manager = User::create([
            'name' => 'Standard Manager',
            'email' => 'manager@example.com',
            'password' => Hash::make('Secret123!'),
            'role' => 'manager',
            'allowed_modules' => ['job-cards', 'quotations', 'billing']
        ]);

        $this->shop = Shop::create([
            'name' => 'Main Workshop',
            'address' => '456 Workshop Way, Colombo'
        ]);

        $this->client = Client::create([
            'name' => 'Kamal Silva',
            'phone' => '0771234567',
            'email' => 'kamal@example.com'
        ]);

        $this->vehicle = Vehicle::create([
            'client_id' => $this->client->id,
            'make' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2018,
            'plate_number' => 'WP CAD-5544'
        ]);
    }

    /**
     * Test quotation creation and revision with customer vehicle number.
     */
    public function test_quotation_workspace_vehicle_number_field()
    {
        $this->withoutExceptionHandling();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        // 1. Create quotation with vehicle number
        $response = $this->actingAs($this->superManager)->post(route('quotations.store'), [
            'customer_name' => 'Nimal Perera',
            'vehicle_number' => 'WP CBF-9988',
            'customer_phone' => '0711122334',
            'items' => [
                [
                    'type' => 'labor',
                    'description' => 'Engine Tuneup',
                    'quantity' => 1,
                    'unit_price' => 5000.00
                ]
            ]
        ]);

        $quotation = Quotation::where('customer_name', 'Nimal Perera')->firstOrFail();
        $this->assertEquals('WP CBF-9988', $quotation->vehicle_number);
        $response->assertRedirect(route('quotations.show', $quotation->id));

        // 2. View show page - vehicle number is displayed, top field lacks contact numbers, bottom footer has contact numbers
        $showResponse = $this->actingAs($this->superManager)->get(route('quotations.show', $quotation->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('WP CBF-9988');
        $showResponse->assertSee('Vehicle No:');

        // Check contact numbers in show view
        $html = $showResponse->getContent();
        // The bottom footer contains "Official: 071 114 4444"
        $this->assertStringContainsString('071 114 4444', $html);

        // 3. Update / Revise quotation
        $updateResponse = $this->actingAs($this->superManager)->put(route('quotations.update', $quotation->id), [
            'customer_name' => 'Nimal Perera',
            'vehicle_number' => 'WP CBF-9988-REV',
            'revision_reason' => 'Updated vehicle registration plate after verification',
            'items' => [
                [
                    'type' => 'labor',
                    'description' => 'Engine Tuneup & Scan',
                    'quantity' => 1,
                    'unit_price' => 6000.00
                ]
            ]
        ]);

        $quotation->refresh();
        $this->assertEquals('WP CBF-9988-REV', $quotation->vehicle_number);
        $revision = $quotation->revisions()->firstOrFail();
        $this->assertEquals('WP CBF-9988', $revision->metadata['vehicle_number']);
    }

    /**
     * Test non-super admin cannot mark job card as abandoned.
     */
    public function test_standard_manager_cannot_mark_job_card_as_abandoned()
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $jobCard = JobCard::create([
            'vehicle_id' => $this->vehicle->id,
            'shop_id' => $this->shop->id,
            'status' => 'on-going',
            'card_number' => 'JC-0001'
        ]);

        $response = $this->actingAs($this->manager)->patch(route('job-cards.update-status', $jobCard->id), [
            'status' => 'abandoned'
        ]);

        $response->assertSessionHasErrors('status');
        $jobCard->refresh();
        $this->assertEquals('on-going', $jobCard->status);
    }

    /**
     * Test super admin can mark job card as abandoned and actual costs are posted to COGS.
     */
    public function test_super_admin_can_mark_abandoned_and_costs_are_booked()
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        // Seed chart of accounts for double entry
        Account::firstOrCreate(['code' => '5000'], ['name' => 'Cost of Goods Sold', 'type' => 'expense']);
        Account::firstOrCreate(['code' => '1300'], ['name' => 'Parts Inventory', 'type' => 'asset']);
        Account::firstOrCreate(['code' => '1200'], ['name' => 'Accounts Receivable', 'type' => 'asset']);
        Account::firstOrCreate(['code' => '4105'], ['name' => 'Parts Revenue', 'type' => 'revenue']);
        Account::firstOrCreate(['code' => '4000'], ['name' => 'Service Revenue', 'type' => 'revenue']);
        Account::firstOrCreate(['code' => '1000'], ['name' => 'Cashbook', 'type' => 'asset']);
        Account::firstOrCreate(['code' => '1030'], ['name' => 'Transportation Account', 'type' => 'asset']);
        Account::firstOrCreate(['code' => '4200'], ['name' => 'Transportation Revenue', 'type' => 'revenue']);
        Account::firstOrCreate(['code' => '5500'], ['name' => 'Transportation Hire Expense', 'type' => 'expense']);

        // Create inventory and purchase batch
        $inventory = Inventory::create([
            'name' => 'Brake Pads Set',
            'sku' => 'BP-001',
            'unit' => 'pcs',
            'quantity' => 10,
            'unit_cost' => 4000.00,
            'unit_price' => 7500.00
        ]);

        $batch = PurchaseBatch::create([
            'inventory_id' => $inventory->id,
            'batch_code' => 'BATCH-001',
            'quantity_received' => 10,
            'quantity_remaining' => 8,
            'cost_price' => 4000.00,
            'selling_price' => 7500.00,
            'purchased_at' => now()->format('Y-m-d')
        ]);

        $jobCard = JobCard::create([
            'vehicle_id' => $this->vehicle->id,
            'shop_id' => $this->shop->id,
            'status' => 'on-going',
            'card_number' => 'JC-0099'
        ]);

        // Allocate 2 brake pads to job card (Cost = 2 * 4000 = 8000)
        StockMovement::create([
            'inventory_id' => $inventory->id,
            'purchase_batch_id' => $batch->id,
            'job_card_id' => $jobCard->id,
            'type' => 'out',
            'quantity' => -2,
            'cost_price' => 4000.00
        ]);

        // Create a bill with status paid previously to verify revenue is diminished on abandonment
        $bill = Bill::create([
            'job_card_id' => $jobCard->id,
            'bill_number' => 'INV-9999',
            'total_amount' => 15000.00,
            'status' => 'draft'
        ]);

        // Mark as abandoned by Super Admin
        $response = $this->actingAs($this->superManager)->patch(route('job-cards.update-status', $jobCard->id), [
            'status' => 'abandoned'
        ]);

        $response->assertSessionHas('success');
        $jobCard->refresh();
        $this->assertEquals('abandoned', $jobCard->status);

        // Verify bill is cancelled
        $bill->refresh();
        $this->assertEquals('cancelled', $bill->status);

        // Verify Journal Entry was created for abandoned costs: Debit COGS 5000, Credit Parts Inventory 1300
        $abandonedEntry = JournalEntry::where('reference', 'ABANDONED-JC-' . $jobCard->id)->first();
        $this->assertNotNull($abandonedEntry);
        $this->assertEquals(8000.00, $abandonedEntry->items()->where('account_id', Account::where('code', '5000')->first()->id)->sum('debit'));
        $this->assertEquals(8000.00, $abandonedEntry->items()->where('account_id', Account::where('code', '1300')->first()->id)->sum('credit'));

        // Verify billing workspace is blocked for abandoned job card
        $workspaceResponse = $this->actingAs($this->superManager)->get(route('billing.workspace', $jobCard->id));
        $workspaceResponse->assertRedirect(route('job-cards.show', $jobCard->id));
        $workspaceResponse->assertSessionHasErrors('status');

        // Verify super admin can un-abandon the ticket and entries are reversed
        $reopenResponse = $this->actingAs($this->superManager)->patch(route('job-cards.update-status', $jobCard->id), [
            'status' => 'on-going'
        ]);
        $reopenResponse->assertSessionHas('success');
        $jobCard->refresh();
        $this->assertEquals('on-going', $jobCard->status);

        // Abandoned journal entry should be deleted
        $this->assertNull(JournalEntry::where('reference', 'ABANDONED-JC-' . $jobCard->id)->first());
    }

    /**
     * Test invoice view has contact numbers in footer but not in top company header block.
     */
    public function test_invoice_contact_numbers_only_in_bottom_field()
    {
        $jobCard = JobCard::create([
            'vehicle_id' => $this->vehicle->id,
            'shop_id' => $this->shop->id,
            'status' => 'waiting-to-pickup',
            'card_number' => 'JC-0077'
        ]);

        $bill = Bill::create([
            'job_card_id' => $jobCard->id,
            'bill_number' => 'INV-7777',
            'total_amount' => 12000.00,
            'status' => 'draft'
        ]);

        $response = $this->actingAs($this->superManager)->get(route('billing.show', $jobCard->id));
        $response->assertStatus(200);

        $html = $response->getContent();

        // Footer contains contact numbers
        $this->assertStringContainsString('071 114 4444', $html);
        $this->assertStringContainsString('077 728 4077', $html);
        $this->assertStringContainsString('077 399 6538', $html);

        // Top company header has shop name and address, but no longer has contact numbers
        $this->assertStringContainsString($this->shop->address, $html);
    }
}
