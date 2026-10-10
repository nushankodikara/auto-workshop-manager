<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Account;
use App\Models\Client;
use App\Models\Vehicle;
use App\Models\JobCard;
use App\Models\Shop;
use App\Models\JournalEntry;
use App\Models\Bill;
use App\Models\JobCardTransportation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class JobCardTransportationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superManager;
    protected Shop $shop;
    protected Client $client;
    protected Vehicle $vehicle;
    protected JobCard $jobCard;

    protected function setUp(): void
    {
        parent::setUp();

        // Create standard Chart of Accounts
        Account::create(['code' => '1000', 'name' => 'Cash & Bank', 'type' => 'asset']);
        Account::create(['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset']);
        Account::create(['code' => '4000', 'name' => 'Service Revenue', 'type' => 'revenue']);
        Account::create(['code' => '4105', 'name' => 'Parts Revenue', 'type' => 'revenue']);
        Account::create(['code' => '5000', 'name' => 'COGS', 'type' => 'expense']);
        Account::create(['code' => '1300', 'name' => 'Parts Inventory', 'type' => 'asset']);
        Account::create(['code' => '1030', 'name' => 'Transportation Account', 'type' => 'asset']);
        Account::create(['code' => '4200', 'name' => 'Transportation Revenue', 'type' => 'revenue']);
        Account::create(['code' => '5500', 'name' => 'Transportation Hire Expense', 'type' => 'expense']);

        $this->superManager = User::create([
            'name' => 'Super Admin',
            'email' => 'super@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'super-manager'
        ]);

        $this->shop = Shop::create([
            'name' => 'Colombo Main Shop',
            'address' => 'Colombo'
        ]);

        $this->client = Client::create([
            'name' => 'John Doe',
            'phone' => '0771234567',
            'email' => 'john@test.com'
        ]);

        $this->vehicle = Vehicle::create([
            'client_id' => $this->client->id,
            'license_plate' => 'WP-CAD-1234',
            'make' => 'Toyota',
            'model' => 'Allion',
            'year' => 2018
        ]);

        $this->jobCard = JobCard::create([
            'shop_id' => $this->shop->id,
            'client_id' => $this->client->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => 'received-vehicle'
        ]);
    }

    public function test_transportation_logs_addition_and_billing_ledger()
    {
        $this->actingAs($this->superManager);

        // 1. Add provided transportation log
        $response1 = $this->post(route('job-cards.add-transportation', $this->jobCard->id), [
            'description' => 'Towing from Colombo 03',
            'amount' => 5000.00,
            'type' => 'provided'
        ]);
        $response1->assertRedirect();
        $this->assertDatabaseHas('job_card_transportations', [
            'job_card_id' => $this->jobCard->id,
            'description' => 'Towing from Colombo 03',
            'amount' => '5000.00',
            'type' => 'provided'
        ]);

        // 2. Add hired transportation log
        $response2 = $this->post(route('job-cards.add-transportation', $this->jobCard->id), [
            'description' => 'Third-party hire from Kandy',
            'amount' => 12000.00,
            'type' => 'hire'
        ]);
        $response2->assertRedirect();
        $this->assertDatabaseHas('job_card_transportations', [
            'job_card_id' => $this->jobCard->id,
            'description' => 'Third-party hire from Kandy',
            'amount' => '12000.00',
            'type' => 'hire'
        ]);

        $this->assertEquals(2, $this->jobCard->transportations()->count());
        $this->assertEquals(17000.00, floatval($this->jobCard->transportations()->sum('amount')));

        // 3. Generate paid invoice for the job card
        $billResponse = $this->post(route('billing.store', $this->jobCard->id), [
            'tax' => 0,
            'discount_percent' => 0,
            'status' => 'paid',
            'labor_desc' => ['General Tune Up'],
            'labor_cost' => [1000.00],
            'labor_price' => [3000.00]
        ]);
        $billResponse->assertRedirect();

        $bill = Bill::first();
        $this->assertNotNull($bill);
        $this->assertEquals('paid', $bill->status);
        // Total should be: Labor 3000.00 + Transportation 17000.00 = 20000.00
        $this->assertEquals(20000.00, floatval($bill->total_amount));

        // 4. Verify Double-entry postings
        // Payment Entry: should debit Transportation Account (1030) for 17000.00, debit Cash & Bank (1000) for 3000.00, and credit Accounts Receivable (1200) for 20000.00
        $paymentEntry = JournalEntry::where('reference', $bill->bill_number . '-PAY')->first();
        $this->assertNotNull($paymentEntry);

        $transDebitItem = $paymentEntry->items()->where('account_id', Account::where('code', '1030')->first()->id)->first();
        $this->assertNotNull($transDebitItem);
        $this->assertEquals(17000.00, floatval($transDebitItem->debit));

        $cashDebitItem = $paymentEntry->items()->where('account_id', Account::where('code', '1000')->first()->id)->first();
        $this->assertNotNull($cashDebitItem);
        $this->assertEquals(3000.00, floatval($cashDebitItem->debit));

        // 5. Verify Hired Towing Payout entry
        // HIRE Entry: should debit Hire Expense (5500) for 12000.00 and credit Transportation Account (1030) for 12000.00
        $hireEntry = JournalEntry::where('reference', $bill->bill_number . '-HIRE')->first();
        $this->assertNotNull($hireEntry);

        $expenseDebitItem = $hireEntry->items()->where('account_id', Account::where('code', '5500')->first()->id)->first();
        $this->assertNotNull($expenseDebitItem);
        $this->assertEquals(12000.00, floatval($expenseDebitItem->debit));

        $transCreditItem = $hireEntry->items()->where('account_id', Account::where('code', '1030')->first()->id)->first();
        $this->assertNotNull($transCreditItem);
        $this->assertEquals(12000.00, floatval($transCreditItem->credit));
    }

    public function test_transportation_update_on_unbilled_job_card()
    {
        $this->actingAs($this->superManager);

        $trans = JobCardTransportation::create([
            'job_card_id' => $this->jobCard->id,
            'description' => 'Original tow',
            'amount' => 4000.00,
            'type' => 'provided',
        ]);

        $response = $this->put(route('job-cards.update-transportation', $trans->id), [
            'description' => 'Updated tow',
            'amount' => 6000.00,
            'type' => 'hire',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('job_card_transportations', [
            'id' => $trans->id,
            'description' => 'Updated tow',
            'amount' => '6000.00',
            'type' => 'hire',
        ]);
    }

    public function test_transportation_update_on_billed_job_card_recalculates_bill_and_ledgers()
    {
        $this->actingAs($this->superManager);

        // 1. Create hired transportation log
        $trans = JobCardTransportation::create([
            'job_card_id' => $this->jobCard->id,
            'description' => 'Third-party hire from Kandy',
            'amount' => 12000.00,
            'type' => 'hire',
        ]);

        // 2. Bill job card
        $billResponse = $this->post(route('billing.store', $this->jobCard->id), [
            'tax' => 0,
            'discount_percent' => 0,
            'status' => 'paid',
            'labor_desc' => ['General Tune Up'],
            'labor_cost' => [1000.00],
            'labor_price' => [3000.00]
        ]);
        $billResponse->assertRedirect();

        $bill = Bill::first();
        $this->assertEquals(15000.00, floatval($bill->total_amount));

        // Verify initial HIRE journal entry exists
        $this->assertNotNull(JournalEntry::where('reference', $bill->bill_number . '-HIRE')->first());

        // 3. Update transportation: switch from 'hire' to 'provided' and change amount to 8000.00
        $updateResponse = $this->put(route('job-cards.update-transportation', $trans->id), [
            'description' => 'Company recovery truck',
            'amount' => 8000.00,
            'type' => 'provided',
        ]);
        $updateResponse->assertRedirect();

        // 4. Verify JobCardTransportation updated in DB
        $this->assertDatabaseHas('job_card_transportations', [
            'id' => $trans->id,
            'description' => 'Company recovery truck',
            'amount' => '8000.00',
            'type' => 'provided',
        ]);

        // 5. Verify Bill and BillItem updated
        $bill->refresh();
        // New total: 3000 (labor) + 8000 (transport) = 11000.00
        $this->assertEquals(11000.00, floatval($bill->total_amount));
        $this->assertDatabaseHas('bill_items', [
            'bill_id' => $bill->id,
            'description' => 'Company recovery truck',
            'total_price' => '8000.00',
            'type' => 'transport',
        ]);

        // 6. Verify HIRE journal entry is now deleted since there are no more hired towings
        $this->assertNull(JournalEntry::where('reference', $bill->bill_number . '-HIRE')->first());

        // 7. Verify Payment Entry (-PAY) is updated with new totals
        $paymentEntry = JournalEntry::where('reference', $bill->bill_number . '-PAY')->first();
        $this->assertNotNull($paymentEntry);

        $transDebitItem = $paymentEntry->items()->where('account_id', Account::where('code', '1030')->first()->id)->first();
        $this->assertNotNull($transDebitItem);
        $this->assertEquals(8000.00, floatval($transDebitItem->debit));

        $receivableCreditItem = $paymentEntry->items()->where('account_id', Account::where('code', '1200')->first()->id)->first();
        $this->assertNotNull($receivableCreditItem);
        $this->assertEquals(11000.00, floatval($receivableCreditItem->credit));

        // 8. Verify invoice entry has 4200 (Transportation Revenue) updated to 8000.00
        $invoiceEntry = JournalEntry::where('reference', $bill->bill_number)->first();
        $this->assertNotNull($invoiceEntry);
        $transRevItem = $invoiceEntry->items()->where('account_id', Account::where('code', '4200')->first()->id)->first();
        $this->assertNotNull($transRevItem);
        $this->assertEquals(8000.00, floatval($transRevItem->credit));
    }

    public function test_transportation_update_from_provided_to_hire_on_billed_job_card()
    {
        $this->actingAs($this->superManager);

        // 1. Create provided transportation log
        $trans = JobCardTransportation::create([
            'job_card_id' => $this->jobCard->id,
            'description' => 'Company flatbed',
            'amount' => 5000.00,
            'type' => 'provided',
        ]);

        // 2. Bill job card
        $this->post(route('billing.store', $this->jobCard->id), [
            'tax' => 0,
            'discount_percent' => 0,
            'status' => 'paid',
            'labor_desc' => ['Brake repair'],
            'labor_cost' => [1000.00],
            'labor_price' => [4000.00]
        ]);

        $bill = Bill::first();
        $this->assertEquals(9000.00, floatval($bill->total_amount));
        // No HIRE journal entry initially
        $this->assertNull(JournalEntry::where('reference', $bill->bill_number . '-HIRE')->first());

        // 3. Update transportation to 'hire' for 7000.00
        $this->put(route('job-cards.update-transportation', $trans->id), [
            'description' => 'Subcontracted flatbed',
            'amount' => 7000.00,
            'type' => 'hire',
        ]);

        // 4. Verify Bill updated: 4000 (labor) + 7000 (transport) = 11000.00
        $bill->refresh();
        $this->assertEquals(11000.00, floatval($bill->total_amount));

        // 5. Verify HIRE journal entry created
        $hireEntry = JournalEntry::where('reference', $bill->bill_number . '-HIRE')->first();
        $this->assertNotNull($hireEntry);
        $expenseDebitItem = $hireEntry->items()->where('account_id', Account::where('code', '5500')->first()->id)->first();
        $this->assertEquals(7000.00, floatval($expenseDebitItem->debit));
        $transCreditItem = $hireEntry->items()->where('account_id', Account::where('code', '1030')->first()->id)->first();
        $this->assertEquals(7000.00, floatval($transCreditItem->credit));
    }
}
