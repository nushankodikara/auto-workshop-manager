<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TrackerSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->app->detectEnvironment(fn() => 'testing');
        
        // Define shared secret in config dynamically for testing
        config(['services.tracker.api_key' => 'test-secret-key']);
    }

    /**
     * Test new client sync with missing or invalid key.
     */
    public function test_new_client_requires_valid_api_key()
    {
        // 1. No key
        $response = $this->postJson('/api/tracker/new-client', [
            'name' => 'John Doe',
            'phone' => '0771234567',
            'tracker_user_id' => 'tracker-uuid-1',
        ]);
        $response->assertStatus(401);

        // 2. Invalid key
        $response = $this->postJson('/api/tracker/new-client', [
            'name' => 'John Doe',
            'phone' => '0771234567',
            'tracker_user_id' => 'tracker-uuid-1',
        ], [
            'Authorization' => 'Bearer wrong-key'
        ]);
        $response->assertStatus(401);
    }

    /**
     * Test syncing a client that doesn't exist yet in Laravel (should create new Client).
     */
    public function test_new_client_sync_creates_client_when_not_found()
    {
        Http::fake();

        $response = $this->postJson('/api/tracker/new-client', [
            'name' => 'Jane Smith',
            'phone' => '0771234567',
            'email' => 'jane@test.com',
            'tracker_user_id' => 'tracker-uuid-jane',
        ], [
            'Authorization' => 'Bearer test-secret-key'
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_new' => true,
        ]);

        $this->assertDatabaseHas('clients', [
            'name' => 'Jane Smith',
            'phone' => '0771234567',
            'email' => 'jane@test.com',
            'tracker_user_id' => 'tracker-uuid-jane',
        ]);

        // Verify the client details push was triggered to Tracker
        Http::assertSent(function ($request) {
            return $request->url() === 'https://tdc-tracker.netlify.app/api/sync/ingest-clients' &&
                   $request['clients'][0]['name'] === 'Jane Smith' &&
                   $request['clients'][0]['phone'] === '0771234567';
        });
    }

    /**
     * Test syncing a client that already exists in Laravel (should link and update tracker_user_id).
     */
    public function test_new_client_sync_updates_client_when_phone_matches()
    {
        Http::fake();

        $existingClient = Client::create([
            'name' => 'Jane Smith Original',
            'phone' => '0771234567',
            'email' => null,
            'address' => '123 Oruwala',
        ]);

        $response = $this->postJson('/api/tracker/new-client', [
            'name' => 'Jane Smith Updated',
            'phone' => '0771234567',
            'email' => 'jane.new@test.com',
            'tracker_user_id' => 'tracker-uuid-jane-existing',
        ], [
            'Authorization' => 'Bearer test-secret-key'
        ]);

        $response->assertStatus(200);

        // Name is unchanged, but tracker_user_id and email are updated
        $this->assertDatabaseHas('clients', [
            'id' => $existingClient->id,
            'name' => 'Jane Smith Original', // Name is kept original in ERP
            'email' => 'jane.new@test.com', // Email is populated
            'tracker_user_id' => 'tracker-uuid-jane-existing',
        ]);
    }

    /**
     * Test update odometer API.
     */
    public function test_update_odometer_sync()
    {
        $client = Client::create([
            'name' => 'Jane Smith',
            'phone' => '0771234567',
        ]);

        $vehicle = Vehicle::create([
            'client_id' => $client->id,
            'make' => 'Toyota',
            'model' => 'Axio',
            'year' => 2018,
            'plate_number' => 'WP CAD-5678',
            'mileage' => 50000,
        ]);

        // 1. Sync higher odometer (should update)
        $response = $this->postJson('/api/tracker/update-odometer', [
            'plate_number' => 'WP CAD-5678',
            'odometer' => 55000,
        ], [
            'Authorization' => 'Bearer test-secret-key'
        ]);

        $response->assertStatus(200);
        $this->assertEquals(55000, $vehicle->fresh()->mileage);

        // 2. Sync lower odometer (should NOT update/decrease)
        $response = $this->postJson('/api/tracker/update-odometer', [
            'plate_number' => 'WP CAD-5678',
            'odometer' => 53000,
        ], [
            'Authorization' => 'Bearer test-secret-key'
        ]);

        $response->assertStatus(200);
        $this->assertEquals(55000, $vehicle->fresh()->mileage);
    }
}
