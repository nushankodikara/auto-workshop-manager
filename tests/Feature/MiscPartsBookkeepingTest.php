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
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MiscPartsBookkeepingTest extends TestCase
{
    use RefreshDatabase;

    protected User $superManager;
    protected Client $client;
    protected Vehicle $vehicle;
    protected Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure settings keys correctly matching double-entry accounts
        Setting::set('account_cashbook', '1000');
        Setting::set('account_receivable', '1200');
        Setting::set('account_inventory', '1300');
        Setting::set('account_payable', '2000');
        Setting::set('account_service_revenue', '4000');
        Setting::set('account_parts_revenue', '4100'); // 4100 is Parts Revenue seeded in migration
        Setting::set('account_cogs', '5000');
        Setting::set('account_salaries', '5100');
        Setting::set('account_employee_advances', '1220');
        Setting::set('account_employee_benefits', '5150');
        Setting::set('account_transportation', '1030');
        Setting::set('account_transportation_revenue', '4200');
        Setting::set('account_transportation_hire_expense', '5500');

        $this->superManager = User::create([
            'name' => 'Finance Controller',
            'email' => 'finance@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'super-manager'
        ]);

        $this->shop = Shop::create([
            'name' => 'Main Workshop',
            'address' => 'Colombo Road',
            'contact_number' => '0711144444'
        ]);

        $this->client = Client::create([
            'name' => 'John Doe',
            'phone' => '94771234567',
            'email' => 'john@test.com'
        ]);

        $this->vehicle = Vehicle::create([
            'client_id' => $this->client->id,
            'make' => 'Toyota',
            'model' => 'Land Cruiser',
            'year' => 2021,
            'plate_number' => 'WP CBX-9988',
            'mileage' => 45000
        ]);
    }

    /**
     * Test bookkeeping journal entries for miscellaneous dealer-direct parts.
     */
    public function test_misc_part_and_outsourcing_bookkeeping_deducts_cost_from_cashbook()
    {
        $jobCard = JobCard::create([
            'shop_id' => $this->shop->id,
            'vehicle_id' => $this->vehicle->id,
            'card_number' => 'TDC-777777',
            'status' => 'received-vehicle'
        ]);

        // 1. Add a miscellaneous part via the job card controller action route
        $response = $this->actingAs($this->superManager)->post(route('job-cards.add-misc-part', $jobCard->id), [
            'name' => 'OEM Fuel Injector (Misc)',
            'cost_price' => 12000.00,
            'selling_price' => 18000.00
        ]);
        $response->assertRedirect();
        
        $miscPart = \App\Models\JobCardMiscPart::where('job_card_id', $jobCard->id)->first();
        $this->assertNotNull($miscPart);
        
        // Assert ledger entry was created immediately upon addition
        $this->assertDatabaseHas('journal_entries', ['reference' => 'MISC-PART-' . $miscPart->id]);
        $miscPartEntry = JournalEntry::where('reference', 'MISC-PART-' . $miscPart->id)->first();
        $this->assertNotNull($miscPartEntry);

        // Debit COGS (5000) for Rs. 12,000
        $cogsDebitItem = $miscPartEntry->items()->whereHas('account', function ($q) {
            $q->where('code', '5000');
        })->first();
        $this->assertNotNull($cogsDebitItem);
        $this->assertEquals(12000.00, $cogsDebitItem->debit);
        $this->assertEquals(0.00, $cogsDebitItem->credit);

        // Credit Cash Drawer (1000) for Rs. 12,000
        $cashCreditItem = $miscPartEntry->items()->whereHas('account', function ($q) {
            $q->where('code', '1000');
        })->first();
        $this->assertNotNull($cashCreditItem);
        $this->assertEquals(0.00, $cashCreditItem->debit);
        $this->assertEquals(12000.00, $cashCreditItem->credit);

        // 2. Add an outsourced service via the job card controller action route
        $responseOutsourcing = $this->actingAs($this->superManager)->post(route('job-cards.add-outsourcing', $jobCard->id), [
            'description' => 'Crankshaft Grinding & Balancing',
            'cost_price' => 8000.00,
            'selling_price' => 12000.00,
            'outsourcing_company_id' => null
        ]);
        $responseOutsourcing->assertRedirect();
        
        $outsourcing = \App\Models\JobCardOutsourcing::where('job_card_id', $jobCard->id)->first();
        $this->assertNotNull($outsourcing);

        // Assert ledger entry was created immediately upon addition
        $this->assertDatabaseHas('journal_entries', ['reference' => 'OUTSOURCING-' . $outsourcing->id]);
        $outsourcingEntry = JournalEntry::where('reference', 'OUTSOURCING-' . $outsourcing->id)->first();
        $this->assertNotNull($outsourcingEntry);

        // Debit COGS (5000) for Rs. 8,000
        $cogsDebitOut = $outsourcingEntry->items()->whereHas('account', function ($q) {
            $q->where('code', '5000');
        })->first();
        $this->assertNotNull($cogsDebitOut);
        $this->assertEquals(8000.00, $cogsDebitOut->debit);

        // Credit Cash Drawer (1000) for Rs. 8,000
        $cashCreditOut = $outsourcingEntry->items()->whereHas('account', function ($q) {
            $q->where('code', '1000');
        })->first();
        $this->assertNotNull($cashCreditOut);
        $this->assertEquals(8000.00, $cashCreditOut->credit);

        // 3. Generate paid bill invoice via controller (this triggers DoubleEntryService::postBillTransaction)
        $billResponse = $this->actingAs($this->superManager)->post(route('billing.store', $jobCard->id), [
            'discount_percent' => 0.00,
            'tax' => 0.00,
            'status' => 'paid',
            'payment_method' => 'cash'
        ]);
        $billResponse->assertRedirect();

        $bill = Bill::where('job_card_id', $jobCard->id)->first();
        $this->assertNotNull($bill);
        $this->assertEquals('paid', $bill->status);

        // Verify journal entries are created for bill (Invoice and Payment)
        $this->assertDatabaseHas('journal_entries', ['reference' => $bill->bill_number]);
        $this->assertDatabaseHas('journal_entries', ['reference' => $bill->bill_number . '-PAY']);
        
        // Confirm the bill's COGS entry was NOT created (since all costs are from immediate cost entries and there are no inventory parts)
        $this->assertDatabaseMissing('journal_entries', ['reference' => $bill->bill_number . '-COGS']);
    }

    /**
     * Test that removing misc parts or outsourcing lines deletes their ledger entries.
     */
    public function test_misc_part_and_outsourcing_removal_removes_ledger_entry()
    {
        $jobCard = JobCard::create([
            'shop_id' => $this->shop->id,
            'vehicle_id' => $this->vehicle->id,
            'card_number' => 'TDC-888888',
            'status' => 'received-vehicle'
        ]);

        // Add misc part
        $this->actingAs($this->superManager)->post(route('job-cards.add-misc-part', $jobCard->id), [
            'name' => 'Legacy Part',
            'cost_price' => 5000.00,
            'selling_price' => 7500.00
        ]);
        $miscPart = \App\Models\JobCardMiscPart::where('job_card_id', $jobCard->id)->first();
        $this->assertNotNull($miscPart);
        $this->assertDatabaseHas('journal_entries', ['reference' => 'MISC-PART-' . $miscPart->id]);

        // Delete misc part
        $responseDelMisc = $this->actingAs($this->superManager)->delete(route('job-cards.delete-misc-part', $miscPart->id));
        $responseDelMisc->assertRedirect();
        $this->assertDatabaseMissing('job_card_misc_parts', ['id' => $miscPart->id]);
        $this->assertDatabaseMissing('journal_entries', ['reference' => 'MISC-PART-' . $miscPart->id]);

        // Add outsourcing
        $this->actingAs($this->superManager)->post(route('job-cards.add-outsourcing', $jobCard->id), [
            'description' => 'Legacy Service',
            'cost_price' => 4000.00,
            'selling_price' => 6000.00,
            'outsourcing_company_id' => null
        ]);
        $outsourcing = \App\Models\JobCardOutsourcing::where('job_card_id', $jobCard->id)->first();
        $this->assertNotNull($outsourcing);
        $this->assertDatabaseHas('journal_entries', ['reference' => 'OUTSOURCING-' . $outsourcing->id]);

        // Delete outsourcing
        $responseDelOut = $this->actingAs($this->superManager)->delete(route('job-cards.delete-outsourcing', $outsourcing->id));
        $responseDelOut->assertRedirect();
        $this->assertDatabaseMissing('job_card_outsourcing', ['id' => $outsourcing->id]);
        $this->assertDatabaseMissing('journal_entries', ['reference' => 'OUTSOURCING-' . $outsourcing->id]);
    }

    /**
     * Test the general ledger matrix CSV export response.
     */
    public function test_ledger_matrix_export_includes_accounts_and_records()
    {
        $jobCard = JobCard::create([
            'shop_id' => $this->shop->id,
            'vehicle_id' => $this->vehicle->id,
            'card_number' => 'TDC-777777',
            'status' => 'received-vehicle'
        ]);

        $responseMisc = $this->actingAs($this->superManager)->post(route('job-cards.add-misc-part', $jobCard->id), [
            'name' => 'OEM Fuel Injector (Misc)',
            'cost_price' => 12000.00,
            'selling_price' => 18000.00
        ]);

        $this->actingAs($this->superManager)->post(route('billing.store', $jobCard->id), [
            'discount_percent' => 0.00,
            'tax' => 0.00,
            'status' => 'paid',
            'payment_method' => 'cash'
        ]);

        $response = $this->actingAs($this->superManager)->get(route('finance.export.ledger-matrix'));
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        
        $content = $response->streamedContent();
        
        // Header contains Date, Reference, Description, etc.
        $this->assertStringContainsString('Date,Reference,Description', $content);
        
        // Header ends with Cash Drawer (code 1000)
        $this->assertStringContainsString('1000 - Cash Drawer', $content);
        
        // Assert some values like +18000.00 and -12000.00 are recorded
        $this->assertStringContainsString('+18000.00', $content);
        $this->assertStringContainsString('-12000.00', $content);
    }
}
