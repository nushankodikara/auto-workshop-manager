<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\Vehicle;
use App\Models\Shop;
use App\Models\JobCard;
use App\Models\Bill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superManager;
    protected Client $client;
    protected Shop $shop;
    protected Vehicle $vehicle;
    protected JobCard $jobCard;

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

        // Create a client
        $this->client = Client::create([
            'name' => 'Jane Doe',
            'email' => 'jane@test.com',
            'phone' => '94771234567',
            'address' => 'Colombo, Sri Lanka'
        ]);

        // Create a shop
        $this->shop = Shop::create([
            'name' => 'Main Center',
            'address' => 'Colombo'
        ]);

        // Create a vehicle
        $this->vehicle = Vehicle::create([
            'client_id' => $this->client->id,
            'make' => 'Toyota',
            'model' => 'Aqua',
            'year' => 2015,
            'plate_number' => 'WP CAD-4321',
            'mileage' => 45000,
        ]);

        // Create a job card
        $this->jobCard = JobCard::create([
            'vehicle_id' => $this->vehicle->id,
            'shop_id' => $this->shop->id,
            'status' => 'received-vehicle',
            'notes' => 'Oil replacement',
            'estimated_cost' => 120.00
        ]);
    }

    /**
     * Test status transitions trigger mock notifications (SMS & Email).
     */
    public function test_status_transitions_trigger_notifications()
    {
        $this->actingAs($this->superManager);

        // 1. Transition to on-going
        session()->forget('mock_notifications');
        $response = $this->patch(route('job-cards.update-status', $this->jobCard->id), [
            'status' => 'on-going'
        ]);

        $response->assertStatus(302);
        $mockNotifications = session('mock_notifications');
        $this->assertNotEmpty($mockNotifications);

        // Assert SMS exists
        $sms = collect($mockNotifications)->firstWhere('type', 'sms');
        $this->assertNotNull($sms);
        $this->assertStringContainsString('Jane Doe', $sms['message']);
        $this->assertStringContainsString('WP CAD-4321', $sms['message']);
        $this->assertStringContainsString('in progress', $sms['message']);

        // Assert Email exists
        $email = collect($mockNotifications)->firstWhere('type', 'email');
        $this->assertNotNull($email);
        $this->assertEquals('jane@test.com', $email['to']);
        $this->assertStringContainsString('Repair in Progress', $email['subject']);

        // 2. Transition to blocked - should NOT trigger notifications
        session()->forget('mock_notifications');
        $this->patch(route('job-cards.update-status', $this->jobCard->id), [
            'status' => 'blocked'
        ]);
        $this->assertNull(session('mock_notifications'));

        // 3. Transition to testing
        session()->forget('mock_notifications');
        $this->patch(route('job-cards.update-status', $this->jobCard->id), [
            'status' => 'testing'
        ]);
        $mockNotifications = session('mock_notifications');
        $this->assertNotEmpty($mockNotifications);
        $sms = collect($mockNotifications)->firstWhere('type', 'sms');
        $this->assertStringContainsString('tested by our mechanics', $sms['message']);

        // 4. Transition to waiting-to-pickup
        session()->forget('mock_notifications');
        $this->patch(route('job-cards.update-status', $this->jobCard->id), [
            'status' => 'waiting-to-pickup'
        ]);
        $mockNotifications = session('mock_notifications');
        $this->assertNotEmpty($mockNotifications);
        $sms = collect($mockNotifications)->firstWhere('type', 'sms');
        $this->assertStringContainsString('ready to be picked up', $sms['message']);
    }

    /**
     * Test creating a bill triggers quotation (draft) and payment receipt (paid) SMS alerts.
     */
    public function test_billing_invoice_triggers_sms_alerts()
    {
        $this->actingAs($this->superManager);

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        // 1. Create a draft invoice (Quotation)
        session()->forget('mock_notifications');
        $response = $this->post(route('billing.store', $this->jobCard->id), [
            'tax' => 10,
            'status' => 'draft',
            'labor_desc' => ['General tuning'],
            'labor_price' => [150.00]
        ]);

        $response->assertStatus(302);
        
        $mockNotifications = session('mock_notifications');
        $this->assertNotEmpty($mockNotifications);
        $sms = collect($mockNotifications)->firstWhere('type', 'sms');
        $this->assertNotNull($sms);
        $this->assertStringContainsString('estimate/quotation', $sms['message']);
        $this->assertStringContainsString('Rs.165.00', $sms['message']); // 150 + 10% tax = 165

        // Find the created bill
        $bill = Bill::where('job_card_id', $this->jobCard->id)->first();
        $this->assertNotNull($bill);
        $this->assertEquals('draft', $bill->status);

        // 2. Finalize draft invoice to paid
        session()->forget('mock_notifications');
        $response = $this->patch(route('billing.update-status', $bill->id), [
            'status' => 'paid'
        ]);

        $response->assertStatus(302);
        $mockNotifications = session('mock_notifications');
        $this->assertNotEmpty($mockNotifications);
        $sms = collect($mockNotifications)->firstWhere('type', 'sms');
        $this->assertNotNull($sms);
        $this->assertStringContainsString('thank you for your business', $sms['message']);
        $this->assertStringContainsString('Rs.165.00', $sms['message']);
    }

    /**
     * Test job card creation triggers received notification.
     */
    public function test_job_card_creation_triggers_received_notification()
    {
        $this->actingAs($this->superManager);

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        session()->forget('mock_notifications');
        $response = $this->post(route('job-cards.store'), [
            'vehicle_id' => $this->vehicle->id,
            'shop_id' => $this->shop->id,
            'notes' => 'Engine inspection',
            'estimated_cost' => 200.00,
            'mileage' => 46000
        ]);

        $response->assertStatus(302);
        
        $mockNotifications = session('mock_notifications');
        $this->assertNotEmpty($mockNotifications);

        $sms = collect($mockNotifications)->firstWhere('type', 'sms');
        $this->assertNotNull($sms);
        $this->assertStringContainsString('Jane Doe', $sms['message']);
        $this->assertStringContainsString('WP CAD-4321', $sms['message']);
        $this->assertStringContainsString('has been received', $sms['message']);
        $this->assertStringContainsString('Engine inspection', $sms['message']);

        $email = collect($mockNotifications)->firstWhere('type', 'email');
        $this->assertNotNull($email);
        $this->assertEquals('jane@test.com', $email['to']);
        $this->assertStringContainsString('Vehicle Received', $email['subject']);
        $this->assertStringContainsString('Rs.200.00', $email['message']);
    }
}
