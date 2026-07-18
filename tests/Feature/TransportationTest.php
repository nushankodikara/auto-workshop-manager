<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\Vehicle;
use App\Models\Shop;
use App\Models\JobCard;
use App\Models\Bill;
use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TransportationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superManager;
    protected Client $client;
    protected Vehicle $vehicle;
    protected Shop $shop;

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
        Account::create(['code' => '1030', 'name' => 'Transportation Account', 'type' => 'asset']);
        Account::create(['code' => '4200', 'name' => 'Transportation Revenue', 'type' => 'revenue']);
        Account::create(['code' => '5500', 'name' => 'Transportation Hire Expense', 'type' => 'expense']);

        $this->superManager = User::create([
            'name' => 'Super Admin',
            'email' => 'super@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'super-manager',
        ]);

        $this->client = Client::create([
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'phone' => '94777123456',
            'address' => 'Colombo'
        ]);

        $this->vehicle = Vehicle::create([
            'client_id' => $this->client->id,
            'make' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2020,
            'plate_number' => 'WP-CAS-9999'
        ]);

        $this->shop = Shop::create(['name' => 'Colombo Shop']);
    }

    public function test_provided_transportation_bookkeeping()
    {
        $this->actingAs($this->superManager);

        // 1. Create Job Card with Transportation Fee (provided)
        $response = $this->post(route('job-cards.store'), [
            'vehicle_id' => $this->vehicle->id,
            'shop_id' => $this->shop->id,
            'notes' => 'Towing provided by company',
            'estimated_cost' => 5000,
            'transportation_fee' => 3000,
            'transportation_type' => 'provided'
        ]);

        $response->assertRedirect();
        $jobCard = JobCard::first();
        $this->assertEquals(3000, floatval($jobCard->transportation_fee));
        $this->assertEquals('provided', $jobCard->transportation_type);

        // 2. Generate Draft Invoice (Draft Bill)
        $bill = Bill::create([
            'job_card_id' => $jobCard->id,
            'bill_number' => 'INV-TRAN-100',
            'tax' => 0,
            'discount_percent' => 0,
            'total_amount' => 3000, // just transportation fee
            'status' => 'draft'
        ]);

        // Sync to Double-Entry
        \App\Services\DoubleEntryService::postBillTransaction($bill);

        // Assert Invoicing Journal Entry
        $invoiceEntry = JournalEntry::where('reference', 'INV-TRAN-100')->first();
        $this->assertNotNull($invoiceEntry);
        // Debit AR 3000, Credit Transportation Revenue 3000
        $this->assertEquals(3000, floatval($invoiceEntry->items()->where('account_id', Account::where('code', '1200')->first()->id)->first()->debit));
        $this->assertEquals(3000, floatval($invoiceEntry->items()->where('account_id', Account::where('code', '4200')->first()->id)->first()->credit));

        // 3. Pay Invoice
        $bill->update(['status' => 'paid']);
        \App\Services\DoubleEntryService::postBillTransaction($bill);

        // Assert Payment Entry
        $paymentEntry = JournalEntry::where('reference', 'INV-TRAN-100-PAY')->first();
        $this->assertNotNull($paymentEntry);
        // Debit Transportation Account 3000, Credit AR 3000
        $this->assertEquals(3000, floatval($paymentEntry->items()->where('account_id', Account::where('code', '1030')->first()->id)->first()->debit));
        $this->assertEquals(3000, floatval($paymentEntry->items()->where('account_id', Account::where('code', '1200')->first()->id)->first()->credit));

        // Verify no HIRE entry
        $this->assertNull(JournalEntry::where('reference', 'INV-TRAN-100-HIRE')->first());
    }

    public function test_hired_transportation_bookkeeping()
    {
        $this->actingAs($this->superManager);

        // 1. Create Job Card with Transportation Fee (hire)
        $jobCard = JobCard::create([
            'vehicle_id' => $this->vehicle->id,
            'shop_id' => $this->shop->id,
            'notes' => 'Third-party towing hire',
            'estimated_cost' => 5000,
            'transportation_fee' => 4500,
            'transportation_type' => 'hire'
        ]);

        // 2. Generate Draft Invoice
        $bill = Bill::create([
            'job_card_id' => $jobCard->id,
            'bill_number' => 'INV-TRAN-200',
            'tax' => 0,
            'discount_percent' => 0,
            'total_amount' => 4500,
            'status' => 'draft'
        ]);

        \App\Services\DoubleEntryService::postBillTransaction($bill);

        // Assert Invoicing
        $invoiceEntry = JournalEntry::where('reference', 'INV-TRAN-200')->first();
        $this->assertNotNull($invoiceEntry);
        $this->assertEquals(4500, floatval($invoiceEntry->items()->where('account_id', Account::where('code', '4200')->first()->id)->first()->credit));

        // 3. Mark as paid
        $bill->update(['status' => 'paid']);
        \App\Services\DoubleEntryService::postBillTransaction($bill);

        // Assert Payment: Debited Transportation Account 4500
        $paymentEntry = JournalEntry::where('reference', 'INV-TRAN-200-PAY')->first();
        $this->assertNotNull($paymentEntry);
        $this->assertEquals(4500, floatval($paymentEntry->items()->where('account_id', Account::where('code', '1030')->first()->id)->first()->debit));

        // Assert Additional Hired Payout Entry
        $hireEntry = JournalEntry::where('reference', 'INV-TRAN-200-HIRE')->first();
        $this->assertNotNull($hireEntry);
        // Debit Transportation Hire Expense 4500, Credit Transportation Account 4500
        $this->assertEquals(4500, floatval($hireEntry->items()->where('account_id', Account::where('code', '5500')->first()->id)->first()->debit));
        $this->assertEquals(4500, floatval($hireEntry->items()->where('account_id', Account::where('code', '1030')->first()->id)->first()->credit));
    }

    public function test_client_vehicle_deletion_cascade_cleanup()
    {
        $this->actingAs($this->superManager);

        $jobCard = JobCard::create([
            'vehicle_id' => $this->vehicle->id,
            'shop_id' => $this->shop->id,
            'notes' => 'Cleanup test',
            'estimated_cost' => 5000,
            'transportation_fee' => 1000,
            'transportation_type' => 'provided'
        ]);

        $bill = Bill::create([
            'job_card_id' => $jobCard->id,
            'bill_number' => 'INV-CLEAN-1',
            'total_amount' => 1000,
            'status' => 'paid'
        ]);

        \App\Services\DoubleEntryService::postBillTransaction($bill);

        $this->assertNotNull(JournalEntry::where('reference', 'INV-CLEAN-1')->first());
        $this->assertNotNull(JournalEntry::where('reference', 'INV-CLEAN-1-PAY')->first());

        // Delete client
        $this->client->delete();

        // Check cleanups
        $this->assertNull(JournalEntry::where('reference', 'INV-CLEAN-1')->first());
        $this->assertNull(JournalEntry::where('reference', 'INV-CLEAN-1-PAY')->first());
    }

    public function test_historical_transportation_reconciliation()
    {
        $this->actingAs($this->superManager);

        // 1. Create a Job Card with a historical labor service named "Towing fee"
        $jobCard = JobCard::create([
            'vehicle_id' => $this->vehicle->id,
            'shop_id' => $this->shop->id,
            'notes' => 'Old towing record',
            'estimated_cost' => 5000,
            'transportation_fee' => 0.00, // zero initially
            'transportation_type' => 'provided'
        ]);

        $service = \App\Models\JobCardService::create([
            'job_card_id' => $jobCard->id,
            'name' => 'Towing fee from Kandy to Colombo',
            'price' => 7500.00
        ]);

        // 2. Create another Job Card with a bill containing a labor item named "Transport charges"
        $jobCardBilled = JobCard::create([
            'vehicle_id' => $this->vehicle->id,
            'shop_id' => $this->shop->id,
            'notes' => 'Old billed towing record',
            'estimated_cost' => 10000,
            'transportation_fee' => 0.00,
            'transportation_type' => 'provided'
        ]);

        $bill = Bill::create([
            'job_card_id' => $jobCardBilled->id,
            'bill_number' => 'INV-OLD-TOW',
            'total_amount' => 10000.00,
            'status' => 'paid'
        ]);

        // Add regular labor item
        $item1 = \App\Models\BillItem::create([
            'bill_id' => $bill->id,
            'type' => 'labor',
            'description' => 'Wheel alignment and inspection',
            'quantity' => 1,
            'unit_price' => 2500.00,
            'total_price' => 2500.00
        ]);

        // Add towing labor item
        $item2 = \App\Models\BillItem::create([
            'bill_id' => $bill->id,
            'type' => 'labor',
            'description' => 'Flatbed transport service charges',
            'quantity' => 1,
            'unit_price' => 7500.00,
            'total_price' => 7500.00
        ]);

        // Sync old bill to double-entry ledger (will account 10000.00 to service revenue)
        \App\Services\DoubleEntryService::postBillTransaction($bill);

        // Before reconciliation checks
        $this->assertEquals(0, floatval($jobCard->refresh()->transportation_fee));
        $this->assertEquals(0, floatval($jobCardBilled->refresh()->transportation_fee));
        $this->assertDatabaseHas('job_card_services', ['id' => $service->id]);
        $this->assertDatabaseHas('bill_items', ['id' => $item2->id]);

        // 3. Trigger reconciliation route
        $response = $this->post(route('settings.reconcile-transportation'));
        $response->assertRedirect();

        // After reconciliation checks:
        // Job Card 1 should now have transportation_fee = 7500.00 and no job_card_service
        $jobCard->refresh();
        $this->assertEquals(7500.00, floatval($jobCard->transportation_fee));
        $this->assertDatabaseMissing('job_card_services', ['id' => $service->id]);

        // Job Card 2 should now have transportation_fee = 7500.00 and no towing bill item
        $jobCardBilled->refresh();
        $this->assertEquals(7500.00, floatval($jobCardBilled->transportation_fee));
        $this->assertDatabaseMissing('bill_items', ['id' => $item2->id]);
        // The other regular labor item should remain intact
        $this->assertDatabaseHas('bill_items', ['id' => $item1->id]);

        // The ledger should have been re-posted!
        $invoiceEntry = JournalEntry::where('reference', 'INV-OLD-TOW')->first();
        $this->assertNotNull($invoiceEntry);
        // Accounts Receivable is still 10000.00 (AR is debit)
        $this->assertEquals(10000.00, floatval($invoiceEntry->items()->where('account_id', Account::where('code', '1200')->first()->id)->first()->debit));
        // Service Revenue is now only 2500.00 (Service Rev is credit)
        $this->assertEquals(2500.00, floatval($invoiceEntry->items()->where('account_id', Account::where('code', '4000')->first()->id)->first()->credit));
        // Transportation Revenue is now 7500.00 (Trans Rev is credit)
        $this->assertEquals(7500.00, floatval($invoiceEntry->items()->where('account_id', Account::where('code', '4200')->first()->id)->first()->credit));

        // Payment entry checks:
        $paymentEntry = JournalEntry::where('reference', 'INV-OLD-TOW-PAY')->first();
        $this->assertNotNull($paymentEntry);
        // Debit cashbook = 2500.00
        $this->assertEquals(2500.00, floatval($paymentEntry->items()->where('account_id', Account::where('code', '1000')->first()->id)->first()->debit));
        // Debit transportation asset = 7500.00
        $this->assertEquals(7500.00, floatval($paymentEntry->items()->where('account_id', Account::where('code', '1030')->first()->id)->first()->debit));
    }
}
