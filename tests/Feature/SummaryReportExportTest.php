<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Shop;
use App\Models\Client;
use App\Models\Vehicle;
use App\Models\JobCard;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Consumable;
use App\Models\ConsumablePurchase;
use App\Models\ConsumableUsage;
use App\Models\Attendance;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SummaryReportExportTest extends TestCase
{
    use RefreshDatabase;

    private $superManager;
    private $worker;
    private $shop;
    private $client;
    private $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed settings for double entry accounts mapping
        Setting::set('account_parts_revenue', '4100');
        Setting::set('account_service_revenue', '4000');
        Setting::set('account_cashbook', '1000');
        Setting::set('account_cogs', '5000');
        Setting::set('account_inventory', '1300');
        Setting::set('account_transportation', '1030');
        Setting::set('account_transportation_revenue', '4200');
        Setting::set('account_transportation_hire_expense', '5500');

        $this->superManager = User::factory()->create([
            'role' => 'super-manager',
            'basic_salary' => 120000.00
        ]);

        $this->worker = User::create([
            'name' => 'Worker User',
            'email' => 'worker@test.com',
            'password' => bcrypt('password'),
            'role' => 'worker',
            'basic_salary' => 50000.00,
            'required_days' => 20,
            'overtime_rate' => 300.00
        ]);

        $this->shop = Shop::create([
            'name' => 'Main Workshop',
            'address' => '123 Main St'
        ]);

        $this->client = Client::create([
            'name' => 'John Client',
            'email' => 'john@client.com',
            'phone' => '+94771234567',
            'address' => 'Colombo'
        ]);

        $this->vehicle = Vehicle::create([
            'client_id' => $this->client->id,
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2020,
            'plate_number' => 'WP CAD-4567'
        ]);
    }

    /**
     * Test role access control for the summary export.
     */
    public function test_access_control()
    {
        // Workers should not be allowed
        $response = $this->actingAs($this->worker)->get(route('finance.export.summary'));
        $response->assertStatus(403);

        // Super managers should be allowed
        $response = $this->actingAs($this->superManager)->get(route('finance.export.summary'));
        $response->assertStatus(200);
    }

    /**
     * Test daily aggregated summary report download and contents.
     */
    public function test_daily_aggregated_summary_report()
    {
        $toolsExpenseAcc = Account::firstOrCreate(
            ['code' => '5400'],
            ['name' => 'Tools & Consumables', 'type' => 'expense']
        );
        $toolsAssetAcc = Account::firstOrCreate(
            ['code' => '1400'],
            ['name' => 'Tools ( Assets )', 'type' => 'asset']
        );
        $cashAcc = Account::firstOrCreate(
            ['code' => '1000'],
            ['name' => 'Cash Drawer', 'type' => 'asset']
        );

        // 1. Create a job card with a bill
        $jobCard = JobCard::create([
            'vehicle_id' => $this->vehicle->id,
            'shop_id' => $this->shop->id,
            'status' => 'completed',
            'notes' => 'Brake pad replacement',
            'completed_at' => now()->format('Y-m-d H:i:s')
        ]);

        $bill = Bill::create([
            'job_card_id' => $jobCard->id,
            'bill_number' => 'BILL-001',
            'client_id' => $this->client->id,
            'status' => 'paid',
            'discount_percent' => 0,
            'tax' => 0,
            'total_amount' => 15000.00
        ]);

        BillItem::create([
            'bill_id' => $bill->id,
            'type' => 'labor',
            'description' => 'Brake pad replacement labour',
            'quantity' => 1,
            'cost_price' => 0,
            'unit_price' => 15000.00,
            'total_price' => 15000.00
        ]);

        \App\Services\DoubleEntryService::postBillTransaction($bill);

        // 2. Log consumable usage
        $consumable = Consumable::create([
            'name' => 'Engine Oil',
            'unit' => 'Litre',
            'quantity' => 10.00
        ]);

        ConsumablePurchase::create([
            'consumable_id' => $consumable->id,
            'batch_code' => 'BATCH-001',
            'quantity' => 10.00,
            'cost_price' => 5000.00, // Weighted avg unit cost = 500.00
            'purchased_at' => now()->format('Y-m-d')
        ]);

        ConsumableUsage::create([
            'consumable_id' => $consumable->id,
            'quantity_consumed' => 2.00, // Cost = 1000.00
            'recorded_at' => now()->format('Y-m-d')
        ]);

        // Seed Tools Expense: Debit 5400, Credit 1000 (1500.00)
        $entry1 = JournalEntry::create([
            'entry_date' => now()->format('Y-m-d'),
            'description' => 'Screwdriver set purchase'
        ]);
        $entry1->items()->create([
            'account_id' => $toolsExpenseAcc->id,
            'debit' => 1500.00,
            'credit' => 0.00
        ]);
        $entry1->items()->create([
            'account_id' => $cashAcc->id,
            'debit' => 0.00,
            'credit' => 1500.00
        ]);

        // Seed Tools Asset: Debit 1400, Credit 1000 (8000.00)
        $entry2 = JournalEntry::create([
            'entry_date' => now()->format('Y-m-d'),
            'description' => 'Hydraulic lift purchase'
        ]);
        $entry2->items()->create([
            'account_id' => $toolsAssetAcc->id,
            'debit' => 8000.00,
            'credit' => 0.00
        ]);
        $entry2->items()->create([
            'account_id' => $cashAcc->id,
            'debit' => 0.00,
            'credit' => 8000.00
        ]);

        // Seed worker attendance to test daily worker salary cost
        Attendance::create([
            'user_id' => $this->worker->id,
            'date' => now()->format('Y-m-d'),
            'status' => 'present',
            'overtime_hours' => 2.00 // Overtime cost = 2.00 * 300 = 600.00. Basic = 50000 / 20 = 2500. Total = 3100.00
        ]);

        // 3. Request daily summary CSV
        $response = $this->actingAs($this->superManager)->get(route('finance.export.summary', [
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'format' => 'daily'
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Date', $content);
        $this->assertStringContainsString('Vehicles Serviced', $content);
        $this->assertStringContainsString('Repair Description', $content);
        $this->assertStringContainsString('Parts Cost (Inventory)', $content);
        $this->assertStringContainsString('Labour Cost (Job Card)', $content);
        $this->assertStringContainsString('Consumables Cost', $content);
        $this->assertStringContainsString('Tools Cost (Expense)', $content);
        $this->assertStringContainsString('Tools (Assets) Purchased', $content);
        $this->assertStringContainsString('Daily Worker Salary (Accrued)', $content);
        $this->assertStringContainsString('Gross Profit', $content);
        $this->assertStringContainsString('Net Profit (Job Card Labour)', $content);
        $this->assertStringContainsString('Net Profit (Daily Payroll)', $content);
        $this->assertStringContainsString('Cash Book Balance (Actual)', $content);
        $this->assertStringContainsString('Cash Book Balance (Daily Payroll)', $content);

        $this->assertStringContainsString('WP CAD-4567', $content);
        $this->assertStringContainsString('Brake pad replacement', $content);
        $this->assertStringContainsString('15000.00', $content); // Total Income
        $this->assertStringContainsString('1500.00', $content); // Tools Cost (Expense)
        $this->assertStringContainsString('8000.00', $content); // Tools Assets
        $this->assertStringContainsString('3100.00', $content); // Daily Worker Salary (Accrued)
        $this->assertStringContainsString('14000.00', $content); // Gross Profit
        $this->assertStringContainsString('6048.39', $content); // Net Profit (Job Card Labour)
        $this->assertStringContainsString('2948.39', $content); // Net Profit (Daily Payroll) (14000 - 1500 - 6451.61 - 3100)
        $this->assertStringContainsString('500.00', $content); // Cash Book Balance (Actual)
        $this->assertStringContainsString('-2600.00', $content); // Cash Book Balance (Daily Payroll) (500 - 3100)
    }

    /**
     * Test job card detailed summary report download and contents.
     */
    public function test_job_card_detailed_summary_report()
    {
        // Create job card
        $jobCard = JobCard::create([
            'vehicle_id' => $this->vehicle->id,
            'shop_id' => $this->shop->id,
            'status' => 'completed',
            'notes' => 'Engine inspection',
            'completed_at' => now()->format('Y-m-d H:i:s')
        ]);

        Bill::create([
            'job_card_id' => $jobCard->id,
            'bill_number' => 'BILL-002',
            'client_id' => $this->client->id,
            'status' => 'paid',
            'discount_percent' => 0,
            'tax' => 0,
            'total_amount' => 8500.00
        ]);

        // Request detailed job card CSV
        $response = $this->actingAs($this->superManager)->get(route('finance.export.summary', [
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'format' => 'job_card'
        ]));

        $response->assertStatus(200);
        $content = $response->streamedContent();
        $this->assertStringContainsString('Date', $content);
        $this->assertStringContainsString('Job Card No', $content);
        $this->assertStringContainsString('Vehicle No', $content);
        $this->assertStringContainsString('Client Name', $content);
        $this->assertStringContainsString('Repair Description', $content);
        $this->assertStringContainsString('Consumables Cost (Prorated)', $content);
        $this->assertStringContainsString('Tools Cost (Prorated)', $content);
        $this->assertStringContainsString('Tools Assets (Prorated)', $content);
        $this->assertStringContainsString('Worker Salary (Prorated)', $content);
        $this->assertStringContainsString('Gross Profit', $content);
        $this->assertStringContainsString('Net Profit (Job Card Labour)', $content);
        $this->assertStringContainsString('Net Profit (Daily Payroll)', $content);
        $this->assertStringContainsString('Cash Book Balance (Actual)', $content);
        $this->assertStringContainsString('Cash Book Balance (Daily Payroll)', $content);
        $this->assertStringContainsString('WP CAD-4567', $content);
        $this->assertStringContainsString('8500.00', $content);
    }

    /**
     * Test daily break even target logic on Statistics page.
     */
    public function test_daily_break_even_statistics()
    {
        // Record technician attendance (Labour cost)
        Attendance::create([
            'user_id' => $this->worker->id,
            'date' => now()->format('Y-m-d'),
            'status' => 'present' // Cost = 50000 / 20 = 2500.00
        ]);

        // Management basic salary is 120000.00 (overhead)
        // Utilities = 80000.00
        // Consumables cost = 0 (none consumed)
        // Parts cost = 0 (none used)
        // Total cost = 120000 + 80000 + 2500 = 202500.00
        // Income = 0
        // Days left in August: let's calculate days left until 30th dynamically
        $daysLeft = max(1, 30 - intval(date('d')) + 1);
        $expectedDailyBreakEven = 202500.00 / $daysLeft;

        $response = $this->actingAs($this->superManager)->get(route('dashboard.statistics'));

        $response->assertStatus(200);
        $this->assertEquals(202500.00, $response->viewData('totalAugustCosts'));
        $this->assertEquals($daysLeft, $response->viewData('daysLeft'));
        $this->assertEquals($expectedDailyBreakEven, $response->viewData('dailyBreakEvenTarget'));
    }
}
