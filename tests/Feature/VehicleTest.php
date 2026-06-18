<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\Vehicle;
use App\Models\Shop;
use App\Models\JobCard;
use App\Models\JobCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use RefreshDatabase;

    protected User $superManager;
    protected User $worker;
    protected Client $client;
    protected Shop $shop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->detectEnvironment(fn() => 'testing');

        // Create a super-manager
        $this->superManager = User::create([
            'name' => 'Super Manager',
            'email' => 'super@test.com',
            'password' => bcrypt('Password123!'),
            'role' => 'super-manager'
        ]);

        // Create a worker
        $this->worker = User::create([
            'name' => 'Tech Worker',
            'email' => 'worker@test.com',
            'password' => bcrypt('Password123!'),
            'role' => 'worker'
        ]);

        // Create a client
        $this->client = Client::create([
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'phone' => '1234567890',
            'address' => '123 Test St'
        ]);

        // Create a shop
        $this->shop = Shop::create([
            'name' => 'Main Workshop',
            'address' => '456 Workshop Way'
        ]);
    }

    /**
     * Test Vehicle CRUD: Store and Update.
     */
    public function test_can_create_and_update_vehicle_records()
    {
        $this->actingAs($this->superManager);

        // 1. Store a Vehicle
        $response = $this->post(route('vehicles.store'), [
            'client_id' => $this->client->id,
            'make' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2020,
            'plate_number' => 'ABC-1234',
            'vin' => '1234567890ABCDEF',
            'mileage' => 15000,
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('vehicles', [
            'client_id' => $this->client->id,
            'make' => 'Toyota',
            'model' => 'Corolla',
            'plate_number' => 'ABC-1234',
            'mileage' => 15000,
        ]);

        $vehicle = Vehicle::where('plate_number', 'ABC-1234')->first();

        // 2. Update the Vehicle
        $response = $this->put(route('vehicles.update', $vehicle->id), [
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2021,
            'plate_number' => 'ABC-1234-UPD',
            'vin' => '1234567890ABCDEF-UPD',
            'mileage' => 16500,
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'model' => 'Camry',
            'plate_number' => 'ABC-1234-UPD',
            'mileage' => 16500,
        ]);

        // 3. View vehicle history page
        $response = $this->get(route('vehicles.history', $vehicle->id));
        $response->assertStatus(200);
        $response->assertSee('Toyota');
        $response->assertSee('Camry');
    }

    /**
     * Test Job Card mileage logic: higher mileage updates the vehicle, lower mileage does not.
     */
    public function test_job_card_mileage_updates_vehicle_highest_odometer()
    {
        $this->actingAs($this->superManager);

        // Create vehicle with initial mileage of 50,000
        $vehicle = Vehicle::create([
            'client_id' => $this->client->id,
            'make' => 'Honda',
            'model' => 'Civic',
            'year' => 2018,
            'plate_number' => 'XYZ-9876',
            'mileage' => 50000,
        ]);

        // 1. Create a Job Card with higher mileage (52,000)
        $this->post(route('job-cards.store'), [
            'vehicle_id' => $vehicle->id,
            'shop_id' => $this->shop->id,
            'notes' => 'First service',
            'estimated_cost' => 150.00,
            'mileage' => 52000,
        ]);

        $vehicle->refresh();
        $this->assertEquals(52000, $vehicle->mileage);

        // Find the newly created job card
        $jobCard = JobCard::where('vehicle_id', $vehicle->id)->first();
        $this->assertEquals(52000, $jobCard->mileage);

        // 2. Update Job Card with higher mileage (55,000)
        $this->put(route('job-cards.update', $jobCard->id), [
            'notes' => 'Updated first service notes',
            'estimated_cost' => 200.00,
            'mileage' => 55000,
        ]);

        $vehicle->refresh();
        $this->assertEquals(55000, $vehicle->mileage);

        // 3. Update Job Card with lower mileage (53,000) - should NOT decrease vehicle mileage
        $this->put(route('job-cards.update', $jobCard->id), [
            'notes' => 'Updated again',
            'estimated_cost' => 200.00,
            'mileage' => 53000,
        ]);

        $vehicle->refresh();
        $this->assertEquals(55000, $vehicle->mileage); // Remains at 55000
        $jobCard->refresh();
        $this->assertEquals(53000, $jobCard->mileage); // Job card itself is updated
    }

    /**
     * Test Job Card services addition and deletion.
     */
    public function test_can_add_and_delete_job_card_services()
    {
        $this->actingAs($this->superManager);

        $vehicle = Vehicle::create([
            'client_id' => $this->client->id,
            'make' => 'Nissan',
            'model' => 'Leaf',
            'year' => 2019,
            'plate_number' => 'LEAF-101',
            'mileage' => 30000,
        ]);

        $jobCard = JobCard::create([
            'vehicle_id' => $vehicle->id,
            'shop_id' => $this->shop->id,
            'notes' => 'Regular maintenance',
            'estimated_cost' => 100.00,
            'status' => 'received-vehicle',
        ]);

        // 1. Add service 1
        $response = $this->post(route('job-cards.add-service', $jobCard->id), [
            'name' => 'Wheel Alignment',
            'price' => 45.00,
            'description' => 'Four-wheel alignment alignment checking'
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('job_card_services', [
            'job_card_id' => $jobCard->id,
            'name' => 'Wheel Alignment',
            'price' => 45.00
        ]);

        // 2. Add service 2
        $this->post(route('job-cards.add-service', $jobCard->id), [
            'name' => 'AC Filter Replacement',
            'price' => 25.00,
            'description' => 'Cabin filter replacement'
        ]);

        $this->assertCount(2, $jobCard->services);

        // 3. Verify Billing Workspace has services prefilled
        $response = $this->get(route('billing.workspace', $jobCard->id));
        $response->assertStatus(200);
        $response->assertSee('Wheel Alignment');
        $response->assertSee('AC Filter Replacement');

        // 4. Delete service 1
        $serviceToDelete = $jobCard->services()->where('name', 'Wheel Alignment')->first();
        $response = $this->delete(route('job-cards.delete-service', $serviceToDelete->id));

        $response->assertStatus(302);
        $this->assertDatabaseMissing('job_card_services', [
            'id' => $serviceToDelete->id
        ]);
        $this->assertCount(1, $jobCard->fresh()->services);
    }

    /**
     * Test Custom Query insights access control and SQL validation console.
     */
    public function test_insights_sql_console_restricted_and_validated()
    {
        // 1. Worker should get forbidden status code (403)
        $this->actingAs($this->worker);
        $response = $this->get(route('dashboard.insights'));
        $response->assertStatus(403);

        // 2. Super Manager can access dashboard insights
        $this->actingAs($this->superManager);
        $response = $this->get(route('dashboard.insights'));
        $response->assertStatus(200);

        // 3. Post a valid read-only SELECT query
        $response = $this->post(route('dashboard.insights'), [
            'sql_query' => 'SELECT name, email, role FROM users'
        ]);
        $response->assertStatus(200);
        $response->assertSee('Super Manager');
        $response->assertSee('super@test.com');
        $response->assertSee('Tech Worker');
        $response->assertSee('worker@test.com');

        // 4. Post a write/destructive query - should trigger security error
        $response = $this->post(route('dashboard.insights'), [
            'sql_query' => 'UPDATE users SET name = "Hacked!" WHERE id = 1'
        ]);
        $response->assertStatus(200);
        $response->assertSee('Security Block: Only read-only SELECT queries are allowed in this dashboard.');

        // 5. Post a SELECT query with forbidden words (e.g. JOIN with subquery DELETE or simple UPDATE inside comment)
        $response = $this->post(route('dashboard.insights'), [
            'sql_query' => 'SELECT name FROM users; DROP TABLE users;'
        ]);
        $response->assertStatus(200);
        $response->assertSee('Security Block: Destructive keywords (INSERT, UPDATE, DELETE, etc.) are prohibited.');
    }
}
