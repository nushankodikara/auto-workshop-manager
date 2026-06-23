<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\PayrollSlip;
use App\Models\JobCard;
use App\Models\JobCardAssignment;
use Carbon\Carbon;
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

    /**
     * Test attendance logging with half_day and n/a statuses.
     */
    public function test_can_log_attendance_with_half_day_and_na()
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

        // 1. Bulk Store with half_day
        $response = $this->post(route('payroll.attendance.store'), [
            'date' => '2026-06-18',
            'attendance' => [
                $worker->id => 'half_day'
            ],
            'overtime' => [
                $worker->id => 1.5
            ]
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('attendances', [
            'user_id' => $worker->id,
            'date' => '2026-06-18 00:00:00',
            'status' => 'half_day',
            'overtime_hours' => 1.50
        ]);

        // 2. Overwrite / Bulk Store with n/a (should delete)
        $response = $this->post(route('payroll.attendance.store'), [
            'date' => '2026-06-18',
            'attendance' => [
                $worker->id => 'n/a'
            ]
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $worker->id,
            'date' => '2026-06-18 00:00:00'
        ]);

        // 3. Employee-specific Monthly Store with half_day
        $response = $this->post(route('payroll.attendance.employee.store', $worker->id), [
            'year' => 2026,
            'month' => 6,
            'status' => [
                10 => 'half_day',
                11 => 'present'
            ],
            'overtime' => [
                10 => 2.0,
                11 => 0.0
            ]
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('attendances', [
            'user_id' => $worker->id,
            'date' => '2026-06-10 00:00:00',
            'status' => 'half_day',
            'overtime_hours' => 2.0
        ]);
        $this->assertDatabaseHas('attendances', [
            'user_id' => $worker->id,
            'date' => '2026-06-11 00:00:00',
            'status' => 'present',
            'overtime_hours' => 0.0
        ]);

        // 4. Employee-specific Monthly Store with n/a to remove attendance
        $response = $this->post(route('payroll.attendance.employee.store', $worker->id), [
            'year' => 2026,
            'month' => 6,
            'status' => [
                10 => 'n/a',
                11 => 'present'
            ]
        ]);

        $response->assertRedirect();
        // Date 10 should be deleted (n/a)
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $worker->id,
            'date' => '2026-06-10 00:00:00'
        ]);
        // Date 11 should still be present
        $this->assertDatabaseHas('attendances', [
            'user_id' => $worker->id,
            'date' => '2026-06-11 00:00:00',
            'status' => 'present'
        ]);
    }

    /**
     * Test payroll calculation and storing when decimal attended days are present (using half-days).
     */
    public function test_can_calculate_salary_with_half_day_attendance()
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

        // Log 22 full present days, and 3 half days
        // Total attended days = 22 + (3 * 0.5) = 23.5 days
        for ($day = 1; $day <= 22; $day++) {
            Attendance::create([
                'user_id' => $worker->id,
                'date' => "2026-06-$day",
                'status' => 'present',
                'overtime_hours' => 0.00
            ]);
        }
        for ($day = 23; $day <= 25; $day++) {
            Attendance::create([
                'user_id' => $worker->id,
                'date' => "2026-06-$day",
                'status' => 'half_day',
                'overtime_hours' => 1.00 // 3 hours overtime total from half-days
            ]);
        }

        // Check workspace pre-fills values correctly
        $response = $this->get(route('payroll.create', ['user' => $worker->id, 'year' => 2026, 'month' => 6]));
        $response->assertStatus(200);
        $response->assertSee('23.5'); // Calculated attended days
        $response->assertSee('26'); // Required days

        // Store the payslip
        // Prorated Salary = (23.5 / 26) * 2600 = 2350.00
        // Overtime Payout = 3 hours * $20.00 = 60.00
        // Net salary should be 2350.00 + 60.00 = 2410.00
        $response = $this->post(route('payroll.store'), [
            'user_id' => $worker->id,
            'month' => 6,
            'year' => 2026,
            'required_days' => 26,
            'attended_days' => 23.5,
            'overtime_hours' => 3,
            'overtime_rate' => 20,
            'overtime_amount' => 60.00,
            'prorated_salary' => 2350.00,
            'item_name' => [],
            'item_type' => [],
            'item_amount' => []
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('payroll_slips', [
            'user_id' => $worker->id,
            'attended_days' => 23.50,
            'prorated_salary' => 2350.00,
            'overtime_amount' => 60.00,
            'net_salary' => 2410.00
        ]);
    }

    /**
     * Test job card assignment timestamps logic.
     */
    public function test_job_card_worker_assignment_timestamps()
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
            'role' => 'worker'
        ]);

        $client = \App\Models\Client::create([
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'phone' => '1234567890',
            'address' => '123 Test St'
        ]);

        $shop = \App\Models\Shop::create([
            'name' => 'Main Workshop',
            'address' => '456 Workshop Way'
        ]);

        $vehicle = \App\Models\Vehicle::create([
            'client_id' => $client->id,
            'make' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2020,
            'plate_number' => 'ABC-1234',
            'vin' => '1234567890ABCDEF',
            'mileage' => 15000,
        ]);

        $jobCardCreatedAt = Carbon::now()->subHours(6);
        $jobCard = JobCard::create([
            'vehicle_id' => $vehicle->id,
            'shop_id' => $shop->id,
            'notes' => 'Test Job Card',
            'estimated_cost' => 15000.00,
            'status' => 'received-vehicle'
        ]);
        $jobCard->created_at = $jobCardCreatedAt;
        $jobCard->save();

        $this->actingAs($admin);

        // 1. Assign worker for the first time
        $this->post(route('job-cards.workers', $jobCard->id), [
            'workers' => [$worker->id]
        ]);

        $this->assertDatabaseHas('job_card_worker', [
            'job_card_id' => $jobCard->id,
            'user_id' => $worker->id
        ]);

        $assignment1 = JobCardAssignment::where('job_card_id', $jobCard->id)
            ->where('user_id', $worker->id)
            ->first();

        $this->assertNotNull($assignment1);
        // First assignment should default to jobCard created_at
        $this->assertEquals($jobCardCreatedAt->toDateTimeString(), $assignment1->assigned_at->toDateTimeString());
        $this->assertNull($assignment1->unassigned_at);

        // 2. Unassign the worker
        $this->post(route('job-cards.workers', $jobCard->id), [
            'workers' => []
        ]);

        $this->assertDatabaseMissing('job_card_worker', [
            'job_card_id' => $jobCard->id,
            'user_id' => $worker->id
        ]);

        $assignment1->refresh();
        $this->assertNotNull($assignment1->unassigned_at);

        // 3. Reassign the worker
        $reassignTime = Carbon::now()->addMinutes(5);
        Carbon::setTestNow($reassignTime);

        $this->post(route('job-cards.workers', $jobCard->id), [
            'workers' => [$worker->id]
        ]);

        $assignments = JobCardAssignment::where('job_card_id', $jobCard->id)
            ->where('user_id', $worker->id)
            ->orderBy('id', 'asc')
            ->get();

        $this->assertCount(2, $assignments);
        // The first assignment remains intact and closed
        $this->assertEquals($jobCardCreatedAt->toDateTimeString(), $assignments[0]->assigned_at->toDateTimeString());
        $this->assertNotNull($assignments[0]->unassigned_at);

        // The second assignment starts at the reassign time (now)
        $this->assertEquals($reassignTime->toDateTimeString(), $assignments[1]->assigned_at->toDateTimeString());
        $this->assertNull($assignments[1]->unassigned_at);

        Carbon::setTestNow(); // Reset test time
    }
}
