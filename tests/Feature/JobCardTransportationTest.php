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
}
