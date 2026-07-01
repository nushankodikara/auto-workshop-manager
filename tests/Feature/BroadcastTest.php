<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\Vehicle;
use App\Models\JobCard;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroadcastTest extends TestCase
{
    use RefreshDatabase;

    protected User $superManager;
    protected User $worker;
    protected Client $clientWithPhone;
    protected Client $clientWithEmail;
    protected Client $clientWithBoth;

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

        // Clients
        $this->clientWithPhone = Client::create([
            'name' => 'John Phone Only',
            'phone' => '0771112222',
            'email' => '',
            'address' => 'Colombo'
        ]);

        $this->clientWithEmail = Client::create([
            'name' => 'Jane Email Only',
            'phone' => '',
            'email' => 'jane@test.com',
            'address' => 'Kandy'
        ]);

        $this->clientWithBoth = Client::create([
            'name' => 'Bob Both',
            'phone' => '0773334444',
            'email' => 'bob@test.com',
            'address' => 'Galle'
        ]);

        $shop = Shop::create([
            'name' => 'Main Workshop',
            'address' => '123 Test Lane'
        ]);

        // Vehicles & Job Cards to set service dates
        // John had a job card 5 days ago (last week)
        $v1 = Vehicle::create([
            'client_id' => $this->clientWithPhone->id,
            'make' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2018,
            'plate_number' => 'CAB-1234'
        ]);
        $jc1 = JobCard::create([
            'vehicle_id' => $v1->id,
            'shop_id' => $shop->id,
            'status' => 'received-vehicle'
        ]);
        $jc1->created_at = now()->subDays(5);
        $jc1->save();

        // Jane had a job card 25 days ago (last month)
        $v2 = Vehicle::create([
            'client_id' => $this->clientWithEmail->id,
            'make' => 'Honda',
            'model' => 'Civic',
            'year' => 2019,
            'plate_number' => 'WP-CAD-5678'
        ]);
        $jc2 = JobCard::create([
            'vehicle_id' => $v2->id,
            'shop_id' => $shop->id,
            'status' => 'received-vehicle'
        ]);
        $jc2->created_at = now()->subDays(25);
        $jc2->save();

        // Bob has no job cards (Never served)
    }

    /**
     * Test access control for broadcast routes.
     */
    public function test_access_control()
    {
        // Workers are unauthorized
        $response = $this->actingAs($this->worker)->get(route('broadcast.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->worker)->post(route('broadcast.send'), [
            'clients' => [$this->clientWithPhone->id],
            'type' => 'sms',
            'message' => 'Hello'
        ]);
        $response->assertStatus(403);

        // Super Manager is authorized
        $this->withoutExceptionHandling();
        $response = $this->actingAs($this->superManager)->get(route('broadcast.index'));
        $response->assertStatus(200);
    }

    /**
     * Test filtering clients by service history timeframe.
     */
    public function test_timeframe_filtering()
    {
        // 1. All timeframe: John, Jane, Bob
        $response = $this->actingAs($this->superManager)->get(route('broadcast.index', ['timeframe' => 'all']));
        $response->assertStatus(200);
        $clients = $response->viewData('clients');
        $this->assertCount(3, $clients);

        // 2. Last Week timeframe: John only
        $response = $this->actingAs($this->superManager)->get(route('broadcast.index', ['timeframe' => 'last_week']));
        $response->assertStatus(200);
        $clients = $response->viewData('clients');
        $this->assertCount(1, $clients);
        $this->assertEquals('John Phone Only', $clients->first()->name);

        // 3. Last Month timeframe: John and Jane
        $response = $this->actingAs($this->superManager)->get(route('broadcast.index', ['timeframe' => 'last_month']));
        $response->assertStatus(200);
        $clients = $response->viewData('clients');
        $this->assertCount(2, $clients);
    }

    /**
     * Test sending SMS broadcast (safe mock mode).
     */
    public function test_send_sms_broadcast()
    {
        // Enable mock settings just in case
        putenv('NOTIFICATION_MOCK=true');

        $response = $this->actingAs($this->superManager)->post(route('broadcast.send'), [
            'clients' => [$this->clientWithPhone->id, $this->clientWithBoth->id, $this->clientWithEmail->id],
            'type' => 'sms',
            'message' => 'Test SMS broadcast campaign body'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // John and Bob have phones. Jane is skipped because she has no phone.
        // So success count = 2, skipped = 1
        $this->assertStringContainsString('Successfully sent via SMS to 2 customers', session('success'));
        $this->assertStringContainsString('Failed or skipped for 1 customers', session('success'));

        // Verify session mock notifications
        $notifications = session('mock_notifications', []);
        $this->assertCount(2, $notifications);
        $this->assertEquals('sms', $notifications[0]['type']);
        $this->assertEquals('94771112222', $notifications[0]['to']);
        $this->assertEquals('Test SMS broadcast campaign body', $notifications[0]['message']);
    }

    /**
     * Test sending Email broadcast (safe mock mode).
     */
    public function test_send_email_broadcast()
    {
        // Enable mock settings just in case
        putenv('NOTIFICATION_MOCK=true');

        $response = $this->actingAs($this->superManager)->post(route('broadcast.send'), [
            'clients' => [$this->clientWithEmail->id, $this->clientWithBoth->id, $this->clientWithPhone->id],
            'type' => 'email',
            'subject' => 'Email Campaign Subject',
            'message' => 'Test Email broadcast campaign body'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Jane and Bob have emails. John is skipped because he has no email.
        // So success count = 2, skipped = 1
        $this->assertStringContainsString('Successfully sent via EMAIL to 2 customers', session('success'));
        $this->assertStringContainsString('Failed or skipped for 1 customers', session('success'));

        // Verify session mock notifications
        $notifications = session('mock_notifications', []);
        $this->assertCount(2, $notifications);
        $this->assertEquals('email', $notifications[0]['type']);
        $this->assertEquals('jane@test.com', $notifications[0]['to']);
        $this->assertEquals('Email Campaign Subject', $notifications[0]['subject']);
        $this->assertEquals('Test Email broadcast campaign body', $notifications[0]['message']);
    }

    /**
     * Test broadcasting SMS and Email campaigns to employees.
     */
    public function test_employee_broadcast_sms_and_email()
    {
        putenv('NOTIFICATION_MOCK=true');

        // Create workers with and without contact details
        $worker1 = User::create([
            'name' => 'Worker One',
            'email' => 'worker1@test.com',
            'password' => bcrypt('Password123!'),
            'role' => 'worker',
            'contact_number' => '0779998888'
        ]);

        $worker2 = User::create([
            'name' => 'Worker Two',
            'email' => 'worker2@test.com',
            'password' => bcrypt('Password123!'),
            'role' => 'worker',
            'contact_number' => ''
        ]);

        // 1. Get index list of employees
        $response = $this->actingAs($this->superManager)->get(route('broadcast.index', ['target' => 'employees']));
        $response->assertStatus(200);
        $response->assertViewHas('employees');
        $this->assertGreaterThanOrEqual(2, count($response->viewData('employees')));

        // 2. Send SMS to employees
        $response = $this->actingAs($this->superManager)->post(route('broadcast.send'), [
            'target' => 'employees',
            'recipients' => [$worker1->id, $worker2->id],
            'type' => 'sms',
            'message' => 'Staff meeting at 5 PM today'
        ]);

        $response->assertRedirect();
        // worker1 has phone, worker2 doesn't. So 1 sent, 1 failed/skipped
        $this->assertStringContainsString('Successfully sent via SMS to 1 employees', session('success'));
        $this->assertStringContainsString('Failed or skipped for 1 employees', session('success'));

        // 3. Send Email to employees
        $response = $this->actingAs($this->superManager)->post(route('broadcast.send'), [
            'target' => 'employees',
            'recipients' => [$worker1->id, $worker2->id],
            'type' => 'email',
            'subject' => 'Staff Meeting Schedule',
            'message' => 'Meeting at 5 PM.'
        ]);

        $response->assertRedirect();
        // Both have emails, so 2 sent, 0 failed
        $this->assertStringContainsString('Successfully sent via EMAIL to 2 employees', session('success'));
    }
}
