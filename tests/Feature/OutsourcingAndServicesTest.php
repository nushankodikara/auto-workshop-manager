<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\OutsourcingCompany;
use App\Models\PredefinedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutsourcingAndServicesTest extends TestCase
{
    use RefreshDatabase;

    protected User $superManager;
    protected User $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superManager = User::create([
            'name' => 'Super Manager',
            'email' => 'super@test.com',
            'password' => bcrypt('Password123!'),
            'role' => 'super-manager'
        ]);

        $this->worker = User::create([
            'name' => 'Tech Worker',
            'email' => 'worker@test.com',
            'password' => bcrypt('Password123!'),
            'role' => 'worker'
        ]);
    }

    /**
     * Test Outsourcing CRUD access control and operations.
     */
    public function test_outsourcing_partner_management()
    {
        // 1. Worker is forbidden from viewing or creating
        $response = $this->actingAs($this->worker)->get(route('outsourcing.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->worker)->post(route('outsourcing.store'), [
            'name' => 'Colombo Crank Rebuilders'
        ]);
        $response->assertStatus(403);

        // 2. Super Manager can view the register page
        $response = $this->actingAs($this->superManager)->get(route('outsourcing.index'));
        $response->assertStatus(200);

        // 3. Super Manager can create a partner
        $response = $this->actingAs($this->superManager)->post(route('outsourcing.store'), [
            'name' => 'Colombo Crank Rebuilders',
            'phone' => '0112345678',
            'email' => 'crank@rebuild.com',
            'address' => 'Colombo Road'
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('outsourcing_companies', [
            'name' => 'Colombo Crank Rebuilders',
            'phone' => '0112345678'
        ]);

        $partner = OutsourcingCompany::first();

        // 4. Super Manager can edit partner details
        $response = $this->actingAs($this->superManager)->put(route('outsourcing.update', $partner->id), [
            'name' => 'Colombo Crank Rebuilders (Updated)',
            'phone' => '0119998887',
            'email' => 'crank@rebuild.com',
            'address' => 'Colombo Road'
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('outsourcing_companies', [
            'id' => $partner->id,
            'name' => 'Colombo Crank Rebuilders (Updated)',
            'phone' => '0119998887'
        ]);

        // 5. Super Manager can delete partner
        $response = $this->actingAs($this->superManager)->delete(route('outsourcing.destroy', $partner->id));
        $response->assertRedirect();
        $this->assertDatabaseMissing('outsourcing_companies', [
            'id' => $partner->id
        ]);
    }

    /**
     * Test Predefined Services CRUD access control and operations.
     */
    public function test_predefined_services_management()
    {
        // 1. Worker is forbidden
        $response = $this->actingAs($this->worker)->get(route('services.index'));
        $response->assertStatus(403);

        // 2. Super Manager can access
        $response = $this->actingAs($this->superManager)->get(route('services.index'));
        $response->assertStatus(200);

        // 3. Super Manager can create predefined service
        $response = $this->actingAs($this->superManager)->post(route('services.store'), [
            'name' => 'Full Engine Scan',
            'description' => 'ECU code scanner diagnosis',
            'cost_price' => 1500.00,
            'selling_price' => 2500.00
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('predefined_services', [
            'name' => 'Full Engine Scan',
            'cost_price' => 1500.00,
            'selling_price' => 2500.00
        ]);

        $service = PredefinedService::first();

        // 4. Super Manager can update service details
        $response = $this->actingAs($this->superManager)->put(route('services.update', $service->id), [
            'name' => 'Full Engine Scan (PRO)',
            'description' => 'ECU code scanner diagnosis plus live logging',
            'cost_price' => 1800.00,
            'selling_price' => 3000.00
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('predefined_services', [
            'id' => $service->id,
            'name' => 'Full Engine Scan (PRO)',
            'cost_price' => 1800.00,
            'selling_price' => 3000.00
        ]);

        // 5. Super Manager can delete service
        $response = $this->actingAs($this->superManager)->delete(route('services.destroy', $service->id));
        $response->assertRedirect();
        $this->assertDatabaseMissing('predefined_services', [
            'id' => $service->id
        ]);
    }
}
