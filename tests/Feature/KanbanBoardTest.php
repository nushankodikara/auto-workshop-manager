<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\Vehicle;
use App\Models\JobCard;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KanbanBoardTest extends TestCase
{
    use RefreshDatabase;

    protected User $superManager;
    protected Shop $shop;
    protected Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superManager = User::create([
            'name' => 'Super Admin',
            'email' => 'super@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'super-manager'
        ]);

        $this->shop = Shop::create([
            'name' => 'Main Workshop',
            'address' => '123 Test Lane'
        ]);

        $client = Client::create([
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'phone' => '0771112222',
            'address' => 'Colombo'
        ]);

        $this->vehicle = Vehicle::create([
            'client_id' => $client->id,
            'make' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2018,
            'plate_number' => 'CAB-1234'
        ]);
    }

    /**
     * Test default kanban board listing (Today's tickets + previous unfinished tickets).
     */
    public function test_default_kanban_board_filtering()
    {
        // 1. Finished ticket completed yesterday (Should NOT show by default today)
        $jc1 = JobCard::create([
            'vehicle_id' => $this->vehicle->id,
            'shop_id' => $this->shop->id,
            'status' => 'waiting-to-pickup',
            'card_number' => 'TDC-0001'
        ]);
        $jc1->created_at = now()->subDays(2);
        $jc1->completed_at = now()->subDays(1);
        $jc1->save();

        // 2. Unfinished ticket created 3 days ago (Should show because it is unfinished)
        $jc2 = JobCard::create([
            'vehicle_id' => $this->vehicle->id,
            'shop_id' => $this->shop->id,
            'status' => 'on-going',
            'card_number' => 'TDC-0002'
        ]);
        $jc2->created_at = now()->subDays(3);
        $jc2->save();

        // 3. Finished ticket completed today (Should show by default today)
        $jc3 = JobCard::create([
            'vehicle_id' => $this->vehicle->id,
            'shop_id' => $this->shop->id,
            'status' => 'waiting-to-pickup',
            'card_number' => 'TDC-0003'
        ]);
        $jc3->created_at = now();
        $jc3->completed_at = now();
        $jc3->save();

        // Hit the board
        $response = $this->actingAs($this->superManager)->get(route('job-cards.board'));
        $response->assertStatus(200);

        $boardData = $response->viewData('boardData');
        
        // Count total job cards returned in boardData groupings
        $totalJobCards = collect($boardData)->flatMap(fn($collection) => $collection)->pluck('card_number');

        // Should contain TDC-0002 (unfinished) and TDC-0003 (completed today)
        // Should NOT contain TDC-0001 (completed yesterday)
        $this->assertContains('TDC-0002', $totalJobCards);
        $this->assertContains('TDC-0003', $totalJobCards);
        $this->assertNotContains('TDC-0001', $totalJobCards);
    }

    /**
     * Test custom date range filtering on kanban board.
     */
    public function test_custom_date_range_filtering()
    {
        // Ticket completed 5 days ago
        $jc1 = JobCard::create([
            'vehicle_id' => $this->vehicle->id,
            'shop_id' => $this->shop->id,
            'status' => 'waiting-to-pickup',
            'card_number' => 'TDC-1001'
        ]);
        $jc1->created_at = now()->subDays(6);
        $jc1->completed_at = now()->subDays(5);
        $jc1->save();

        // Hit the board with a date range covering 5 days ago
        $response = $this->actingAs($this->superManager)->get(route('job-cards.board', [
            'start_date' => now()->subDays(5)->format('Y-m-d'),
            'end_date' => now()->subDays(5)->format('Y-m-d')
        ]));
        $response->assertStatus(200);

        $boardData = $response->viewData('boardData');
        $totalJobCards = collect($boardData)->flatMap(fn($collection) => $collection)->pluck('card_number');

        // Should contain TDC-1001 (completed in range)
        $this->assertContains('TDC-1001', $totalJobCards);
    }
}
