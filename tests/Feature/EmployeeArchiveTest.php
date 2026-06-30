<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected User $superManager;
    protected User $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superManager = User::create([
            'name' => 'Super Admin',
            'email' => 'super@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'super-manager'
        ]);

        $this->worker = User::create([
            'name' => 'John Worker',
            'email' => 'worker@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'worker',
            'basic_salary' => 50000.00,
            'required_days' => 20,
            'overtime_rate' => 500.00
        ]);
    }

    /**
     * Test employee archiving logic and exclusion from listings.
     */
    public function test_employee_archiving_and_exclusion()
    {
        // Initially worker is active (not archived)
        $this->worker->refresh();
        $this->assertFalse($this->worker->is_archived);

        // 1. Archive the worker
        $response = $this->actingAs($this->superManager)->post(route('employees.archive', $this->worker->id));
        $response->assertRedirect(route('payroll.index'));
        $response->assertSessionHas('success');

        $this->worker->refresh();
        $this->assertTrue($this->worker->is_archived);

        // 2. Verify worker does not appear in active listings on HR page
        $response = $this->actingAs($this->superManager)->get(route('payroll.index'));
        $response->assertStatus(200);

        $activeUsers = $response->viewData('users');
        $archivedUsers = $response->viewData('archivedUsers');

        $this->assertNotContains($this->worker->id, $activeUsers->pluck('id'));
        $this->assertContains($this->worker->id, $archivedUsers->pluck('id'));

        // 3. Verify worker does not appear in job card worker assignments
        $shop = Shop::create(['name' => 'Test Shop', 'address' => 'Test Address']);
        $response = $this->actingAs($this->superManager)->get(route('job-cards.board'));
        $response->assertStatus(200);

        $workers = $response->viewData('workers');
        $this->assertNotContains($this->worker->id, $workers->pluck('id'));
    }

    /**
     * Test employee unarchiving / restoration.
     */
    public function test_employee_unarchiving()
    {
        // Start archived
        $this->worker->update(['is_archived' => true]);

        // Unarchive
        $response = $this->actingAs($this->superManager)->post(route('employees.unarchive', $this->worker->id));
        $response->assertRedirect(route('payroll.index'));

        $this->worker->refresh();
        $this->assertFalse($this->worker->is_archived);
    }

    /**
     * Test employee profile Yearly Attendance Calendar display.
     */
    public function test_yearly_attendance_calendar_display()
    {
        // Mark attendance: John is present on 2026-06-15 and absent on 2026-06-16
        $date1 = '2026-06-15';
        $date2 = '2026-06-16';

        Attendance::create([
            'user_id' => $this->worker->id,
            'date' => $date1,
            'status' => 'present'
        ]);

        Attendance::create([
            'user_id' => $this->worker->id,
            'date' => $date2,
            'status' => 'absent'
        ]);

        // Get profile page
        $response = $this->actingAs($this->superManager)->get(route('employees.show', [
            'user' => $this->worker->id,
            'year' => 2026
        ]));
        
        $response->assertStatus(200);

        // Verify yearly attendance view data contains records for those dates
        $yearlyAttendance = $response->viewData('yearlyAttendance');
        $this->assertNotNull($yearlyAttendance);
        $this->assertArrayHasKey($date1, $yearlyAttendance);
        $this->assertArrayHasKey($date2, $yearlyAttendance);

        $this->assertEquals('present', $yearlyAttendance->get($date1)->status);
        $this->assertEquals('absent', $yearlyAttendance->get($date2)->status);
    }
}
