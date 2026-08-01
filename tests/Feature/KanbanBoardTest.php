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

    /**
     * Test that only unpaid cards from all time in non-active status (blocked, testing, ready) are shown when the unpaid filter is active.
     */
    public function test_kanban_board_shows_only_unpaid_cards_from_all_time_when_filter_active()
    {
        // 1. Unpaid card in waiting-to-pickup status, created 10 days ago (Should be visible because it's unpaid and all-time)
        $unpaidReady = JobCard::create([
            'vehicle_id' => $this->vehicle->id,
            'shop_id' => $this->shop->id,
            'status' => 'waiting-to-pickup',
            'card_number' => 'TDC-9001'
        ]);
        $unpaidReady->created_at = now()->subDays(10);
        $unpaidReady->completed_at = now()->subDays(10);
        $unpaidReady->save();

        $bill1 = \App\Models\Bill::create([
            'job_card_id' => $unpaidReady->id,
            'bill_number' => 'INV-9001',
            'total_amount' => 1000,
            'status' => 'draft' // Unpaid
        ]);

        // 2. Paid card in waiting-to-pickup status (Should be hidden because it is paid)
        $paidReady = JobCard::create([
            'vehicle_id' => $this->vehicle->id,
            'shop_id' => $this->shop->id,
            'status' => 'waiting-to-pickup',
            'card_number' => 'TDC-9002'
        ]);
        $paidReady->created_at = now();
        $paidReady->completed_at = now();
        $paidReady->save();

        $bill2 = \App\Models\Bill::create([
            'job_card_id' => $paidReady->id,
            'bill_number' => 'INV-9002',
            'total_amount' => 1000,
            'status' => 'paid' // Paid
        ]);

        // 3. Unpaid card in received-vehicle status (Should be hidden because it is in active status)
        $unpaidReceived = JobCard::create([
            'vehicle_id' => $this->vehicle->id,
            'shop_id' => $this->shop->id,
            'status' => 'received-vehicle',
            'card_number' => 'TDC-9003'
        ]);
        $unpaidReceived->created_at = now();
        $unpaidReceived->save();

        $bill3 = \App\Models\Bill::create([
            'job_card_id' => $unpaidReceived->id,
            'bill_number' => 'INV-9003',
            'total_amount' => 500,
            'status' => 'draft' // Unpaid
        ]);

        // 4. Job Card with no bill generated yet in waiting-to-pickup status (Should be hidden because bill does not exist)
        $noBillReady = JobCard::create([
            'vehicle_id' => $this->vehicle->id,
            'shop_id' => $this->shop->id,
            'status' => 'waiting-to-pickup',
            'card_number' => 'TDC-9004'
        ]);
        $noBillReady->created_at = now();
        $noBillReady->completed_at = now();
        $noBillReady->save();

        // Hit the board with unpaid filter active (ignoring date range)
        $response = $this->actingAs($this->superManager)->get(route('job-cards.board', [
            'unpaid' => '1',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d')
        ]));
        $response->assertStatus(200);

        $boardData = $response->viewData('boardData');
        $totalJobCards = collect($boardData)->flatMap(fn($collection) => $collection)->pluck('card_number');

        // assertions
        $this->assertContains('TDC-9001', $totalJobCards);    // Kept (unpaid, ready, created 10 days ago)
        $this->assertNotContains('TDC-9002', $totalJobCards); // Excluded (paid & ready)
        $this->assertNotContains('TDC-9003', $totalJobCards); // Excluded (active status)
        $this->assertNotContains('TDC-9004', $totalJobCards); // Excluded (no bill exists)
    }

    /**
     * Test that all cards are displayed when the unpaid filter is not active.
     */
    public function test_kanban_board_includes_all_cards_when_filter_inactive()
    {
        $unpaidReady = JobCard::create([
            'vehicle_id' => $this->vehicle->id,
            'shop_id' => $this->shop->id,
            'status' => 'waiting-to-pickup',
            'card_number' => 'TDC-9011'
        ]);
        $unpaidReady->created_at = now();
        $unpaidReady->completed_at = now();
        $unpaidReady->save();

        $bill = \App\Models\Bill::create([
            'job_card_id' => $unpaidReady->id,
            'bill_number' => 'INV-9011',
            'total_amount' => 1000,
            'status' => 'draft'
        ]);

        // Hit board without active filter
        $response = $this->actingAs($this->superManager)->get(route('job-cards.board'));
        $response->assertStatus(200);

        $boardData = $response->viewData('boardData');
        $totalJobCards = collect($boardData)->flatMap(fn($collection) => $collection)->pluck('card_number');

        $this->assertContains('TDC-9011', $totalJobCards); // Included
    }
}
