<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use App\Models\Bill;
use App\Models\BillItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test labor segment calculations on the statistics dashboard.
     */
    public function test_attendance_based_labor_cost_calculations()
    {
        // 1. Create users
        $admin = User::factory()->create([
            'role' => 'super-manager',
            'basic_salary' => 100000.00
        ]);

        $worker = User::create([
            'name' => 'Test Worker',
            'email' => 'worker@test.com',
            'password' => bcrypt('password'),
            'role' => 'worker',
            'basic_salary' => 20000.00,
            'required_days' => 20 // Daily wage = 1000.00
        ]);

        $manager = User::create([
            'name' => 'Test Manager',
            'email' => 'manager@test.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'basic_salary' => 52000.00, // Excluded from worker calculations
            'required_days' => 26
        ]);

        // 2. Create attendance logs in a specific range: 2026-07-01 to 2026-07-10
        // Worker: 2 full days, 1 half day = 2.5 days * 1000.00 = 2500.00
        Attendance::create([
            'user_id' => $worker->id,
            'date' => '2026-07-02',
            'status' => 'present'
        ]);
        Attendance::create([
            'user_id' => $worker->id,
            'date' => '2026-07-03',
            'status' => 'present'
        ]);
        Attendance::create([
            'user_id' => $worker->id,
            'date' => '2026-07-04',
            'status' => 'half_day'
        ]);
        // Absent day (should be ignored)
        Attendance::create([
            'user_id' => $worker->id,
            'date' => '2026-07-05',
            'status' => 'absent'
        ]);

        // Manager (should be ignored)
        Attendance::create([
            'user_id' => $manager->id,
            'date' => '2026-07-02',
            'status' => 'present'
        ]);

        // Attendance outside of range (should be ignored)
        Attendance::create([
            'user_id' => $worker->id,
            'date' => '2026-07-20',
            'status' => 'present'
        ]);

        // 3. Create a paid bill with some labor revenue to verify margin calculations
        $bill = Bill::create([
            'client_id' => 1, // seeded or fake
            'status' => 'paid',
            'discount_percent' => 0,
            'tax' => 0,
            'total_amount' => 5000.00,
            'created_at' => '2026-07-03 12:00:00'
        ]);

        BillItem::create([
            'bill_id' => $bill->id,
            'type' => 'labor',
            'description' => 'Test Labor Service',
            'quantity' => 1,
            'cost_price' => 1000.00, // Should be ignored in favor of attendance
            'unit_price' => 5000.00,
            'total_price' => 5000.00
        ]);

        // 4. Request statistics dashboard for the target date range
        $response = $this->actingAs($admin)->get(route('dashboard.statistics', [
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-10'
        ]));

        $response->assertStatus(200);

        // Verify direct labor cost is 2.5 * 1000.00 = 2500.00
        $this->assertEquals(2500.00, $response->viewData('laborCOGS'));

        // Verify labor revenue is 5000.00
        $this->assertEquals(5000.00, $response->viewData('laborRevenue'));

        // Verify labor profit is 5000.00 - 2500.00 = 2500.00
        $this->assertEquals(2500.00, $response->viewData('laborProfit'));

        // Verify labor margin is 50.0%
        $this->assertEquals(50.0, $response->viewData('laborMargin'));

        // Verify chart data collections are present
        $this->assertNotNull($response->viewData('revenueData'));
        $this->assertNotNull($response->viewData('incomeData'));
        $this->assertNotNull($response->viewData('expenditureData'));
        
        $revenueData = $response->viewData('revenueData');
        $this->assertCount(1, $revenueData);
        $this->assertEquals('2026-07-03', $revenueData->first()['date']);
        $this->assertEquals(5000.00, $revenueData->first()['total_revenue']);
    }
}
