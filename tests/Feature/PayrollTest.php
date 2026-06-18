<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\PayrollSlip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Detect testing env and refresh DB setup
        $this->app->detectEnvironment(fn() => 'testing');
    }

    /**
     * Test employee CRUD actions.
     */
    public function test_super_manager_can_manage_employees()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@totaldrivecare.com',
            'password' => bcrypt('Password123!'),
            'role' => 'super-manager',
            'allowed_modules' => ['payroll']
        ]);

        $this->actingAs($admin);

        // 1. Create Employee
        $response = $this->post(route('employees.store'), [
            'name' => 'Test Tech',
            'email' => 'tech@test.com',
            'password' => 'Password123!',
            'role' => 'worker',
            'basic_salary' => 2000,
            'required_days' => 26,
            'overtime_rate' => 15
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('users', [
            'email' => 'tech@test.com',
            'role' => 'worker',
            'basic_salary' => 2000,
            'required_days' => 26,
            'overtime_rate' => 15
        ]);

        $worker = User::where('email', 'tech@test.com')->first();

        // 2. Update Employee
        $response = $this->put(route('employees.update', $worker->id), [
            'name' => 'Updated Tech Name',
            'email' => 'tech@test.com',
            'role' => 'worker',
            'basic_salary' => 2500,
            'required_days' => 24,
            'overtime_rate' => 18
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('users', [
            'id' => $worker->id,
            'name' => 'Updated Tech Name',
            'basic_salary' => 2500,
            'required_days' => 24,
            'overtime_rate' => 18
        ]);

        // 3. Delete Employee
        $response = $this->delete(route('employees.destroy', $worker->id));
        $response->assertStatus(302);
        $this->assertDatabaseMissing('users', ['id' => $worker->id]);
    }

    /**
     * Test bulk attendance tracking.
     */
    public function test_can_log_daily_attendance()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@totaldrivecare.com',
            'password' => bcrypt('Password123!'),
            'role' => 'super-manager'
        ]);

        $worker = User::create([
            'name' => 'Test Tech',
            'email' => 'tech@test.com',
            'password' => bcrypt('Password123!'),
            'role' => 'worker',
            'basic_salary' => 2000
        ]);

        $this->actingAs($admin);

        $response = $this->post(route('payroll.attendance.store'), [
            'date' => '2026-06-18',
            'attendance' => [
                $worker->id => 'present'
            ],
            'overtime' => [
                $worker->id => 2.5
            ]
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('attendances', [
            'user_id' => $worker->id,
            'date' => '2026-06-18 00:00:00',
            'status' => 'present',
            'overtime_hours' => 2.50
        ]);
    }

    /**
     * Test pro-rated payroll salary slips generation.
     */
    public function test_can_calculate_prorated_salary_slip()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@totaldrivecare.com',
            'password' => bcrypt('Password123!'),
            'role' => 'super-manager'
        ]);

        $worker = User::create([
            'name' => 'Test Tech',
            'email' => 'tech@test.com',
            'password' => bcrypt('Password123!'),
            'role' => 'worker',
            'basic_salary' => 2600.00,
            'required_days' => 26,
            'overtime_rate' => 20.00
        ]);

        $this->actingAs($admin);

        // Seed attendance logs: 24 present days out of 26 required days, and 5 hours overtime
        for ($day = 1; $day <= 24; $day++) {
            Attendance::create([
                'user_id' => $worker->id,
                'date' => "2026-06-$day",
                'status' => 'present',
                'overtime_hours' => ($day === 1) ? 5.00 : 0.00
            ]);
        }
        
        // 2 absent days
        Attendance::create(['user_id' => $worker->id, 'date' => '2026-06-25', 'status' => 'absent']);
        Attendance::create(['user_id' => $worker->id, 'date' => '2026-06-26', 'status' => 'absent']);

        // Check workspace pre-fills values correctly
        $response = $this->get(route('payroll.create', ['user' => $worker->id, 'year' => 2026, 'month' => 6]));
        $response->assertStatus(200);
        $response->assertSee('24'); // Attended days
        $response->assertSee('26'); // Required days
        
        // Save the payslip
        // Prorated Salary = (24 / 26) * 2600 = 2400.00
        // Overtime Payout = 5 hours * $20.00 = 100.00
        $response = $this->post(route('payroll.store'), [
            'user_id' => $worker->id,
            'month' => 6,
            'year' => 2026,
            'required_days' => 26,
            'attended_days' => 24,
            'overtime_hours' => 5,
            'overtime_rate' => 20,
            'overtime_amount' => 100.00,
            'prorated_salary' => 2400.00,
            'item_name' => ['Allowance Bonus'],
            'item_type' => ['addition'],
            'item_amount' => [150.00]
        ]);

        $response->assertStatus(302);
        
        // Net salary should be = 2400 (prorated) + 100 (OT) + 150 (allowance) = 2650.00
        $this->assertDatabaseHas('payroll_slips', [
            'user_id' => $worker->id,
            'prorated_salary' => 2400.00,
            'overtime_amount' => 100.00,
            'allowance' => 150.00,
            'net_salary' => 2650.00
        ]);
    }
}
