<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Shop;
use App\Models\Client;
use App\Models\Vehicle;
use App\Models\JobCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeePreferredNameTest extends TestCase
{
    use RefreshDatabase;

    protected User $superManager;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-manager'], [
            'label' => 'Super Administrator',
            'allowed_modules' => ['dashboard', 'job_cards', 'inventory', 'clients', 'billing', 'payroll', 'settings']
        ]);

        Role::firstOrCreate(['name' => 'worker'], [
            'label' => 'Technician / Worker',
            'allowed_modules' => ['job_cards']
        ]);

        $this->superManager = User::create([
            'name' => 'Administrator Master',
            'email' => 'admin@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'super-manager'
        ]);
    }

    /**
     * Test display_name attribute falls back to full name when preferred_name is null.
     */
    public function test_display_name_accessor()
    {
        $user1 = User::create([
            'name' => 'Alexander Hamilton',
            'preferred_name' => 'Alex',
            'email' => 'alex@hamilton.com',
            'password' => Hash::make('password'),
            'role' => 'worker',
            'basic_salary' => 50000,
            'total_salary' => 60000,
            'required_days' => 26,
            'overtime_rate' => 300
        ]);

        $user2 = User::create([
            'name' => 'Benjamin Franklin',
            'preferred_name' => null,
            'email' => 'ben@franklin.com',
            'password' => Hash::make('password'),
            'role' => 'worker',
            'basic_salary' => 50000,
            'total_salary' => 60000,
            'required_days' => 26,
            'overtime_rate' => 300
        ]);

        $this->assertEquals('Alex', $user1->display_name);
        $this->assertEquals('Benjamin Franklin', $user2->display_name);
    }

    /**
     * Test creating employee with preferred_name via controller.
     */
    public function test_create_employee_with_preferred_name()
    {
        $response = $this->actingAs($this->superManager)->post(route('employees.store'), [
            'name' => 'Samantha Elizabeth Perera',
            'preferred_name' => 'Sam',
            'email' => 'sam@workshop.com',
            'password' => 'Password123!',
            'role' => 'worker',
            'basic_salary' => 45000,
            'total_salary' => 55000,
            'required_days' => 26,
            'overtime_rate' => 250,
            'contact_number' => '0771122334'
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('users', [
            'name' => 'Samantha Elizabeth Perera',
            'preferred_name' => 'Sam',
            'email' => 'sam@workshop.com'
        ]);
    }

    /**
     * Test updating employee preferred_name.
     */
    public function test_update_employee_preferred_name()
    {
        $user = User::create([
            'name' => 'Christopher Michael',
            'preferred_name' => 'Chris',
            'email' => 'chris@workshop.com',
            'password' => Hash::make('password'),
            'role' => 'worker',
            'basic_salary' => 50000,
            'total_salary' => 60000,
            'required_days' => 26,
            'overtime_rate' => 300
        ]);

        $response = $this->actingAs($this->superManager)->put(route('employees.update', $user->id), [
            'name' => 'Christopher Michael',
            'preferred_name' => 'Topher',
            'email' => 'chris@workshop.com',
            'role' => 'worker',
            'basic_salary' => 50000,
            'total_salary' => 60000,
            'required_days' => 26,
            'overtime_rate' => 300
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'preferred_name' => 'Topher'
        ]);
    }

    /**
     * Test preferred name rendered in Job Card board and show views.
     */
    public function test_job_card_views_display_preferred_name()
    {
        $shop = Shop::create(['name' => 'Main Workshop', 'address' => 'Colombo']);
        $client = Client::create(['name' => 'Test Client', 'phone' => '0770001122']);
        $vehicle = Vehicle::create([
            'client_id' => $client->id,
            'make' => 'Nissan',
            'model' => 'Leaf',
            'year' => 2018,
            'plate_number' => 'CAB-5555'
        ]);

        $technician = User::create([
            'name' => 'Jonathanathan Doe',
            'preferred_name' => 'Johnny',
            'email' => 'johnny@test.com',
            'password' => Hash::make('password'),
            'role' => 'worker',
            'basic_salary' => 50000,
            'total_salary' => 60000,
            'required_days' => 26,
            'overtime_rate' => 300
        ]);

        $jc = JobCard::create([
            'vehicle_id' => $vehicle->id,
            'shop_id' => $shop->id,
            'status' => 'on-going',
            'card_number' => 'TDC-PREF-01'
        ]);

        $jc->workers()->attach($technician->id);

        // Job Card board should display preferred name
        $boardResponse = $this->actingAs($this->superManager)->get(route('job-cards.board'));
        $boardResponse->assertStatus(200);
        $boardResponse->assertSee('Johnny');

        // Job Card show should display preferred name
        $showResponse = $this->actingAs($this->superManager)->get(route('job-cards.show', $jc->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Johnny');
    }
}

