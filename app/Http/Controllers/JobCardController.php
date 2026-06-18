<?php

namespace App\Http\Controllers;

use App\Models\JobCard;
use App\Models\Vehicle;
use App\Models\Shop;
use App\Models\User;
use App\Models\Comment;
use App\Models\Activity;
use App\Models\Inventory;
use App\Models\StockMovement;
use App\Services\FitSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JobCardController extends Controller
{
    protected FitSmsService $smsService;

    public function __construct(FitSmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Show the Kanban Board.
     */
    public function board()
    {
        $jobCards = JobCard::with(['vehicle.client', 'shop', 'workers'])->get();
        
        // Group by status
        $boardData = [
            'received-vehicle' => $jobCards->where('status', 'received-vehicle'),
            'on-going' => $jobCards->where('status', 'on-going'),
            'blocked' => $jobCards->where('status', 'blocked'),
            'testing' => $jobCards->where('status', 'testing'),
            'waiting-to-pickup' => $jobCards->where('status', 'waiting-to-pickup'),
        ];

        $vehicles = Vehicle::with('client')->latest()->get();
        $shops = Shop::all();
        $workers = User::where('role', 'worker')->get();
        $managers = User::whereIn('role', ['super-manager', 'manager'])->get();

        return view('job-cards.board', compact('boardData', 'vehicles', 'shops', 'workers', 'managers'));
    }

    /**
     * Show detailed Job Card.
     */
    public function show(JobCard $jobCard)
    {
        $jobCard->load([
            'vehicle.client',
            'shop',
            'workers',
            'comments.user',
            'activities.user',
            'stockMovements.inventory',
            'bill.items'
        ]);

        $allWorkers = User::where('role', 'worker')->get();
        $inventoryItems = Inventory::where('quantity', '>', 0)->get();

        return view('job-cards.show', compact('jobCard', 'allWorkers', 'inventoryItems'));
    }

    /**
     * Store a new Job Card.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'shop_id' => 'required|exists:shops,id',
            'notes' => 'nullable|string',
            'estimated_cost' => 'required|numeric|min:0',
            'workers' => 'nullable|array',
            'workers.*' => 'exists:users,id',
        ]);

        DB::transaction(function () use ($data) {
            $jobCard = JobCard::create([
                'vehicle_id' => $data['vehicle_id'],
                'shop_id' => $data['shop_id'],
                'notes' => $data['notes'] ?? null,
                'estimated_cost' => $data['estimated_cost'],
                'status' => 'received-vehicle'
            ]);

            // Attach workers if any
            if (!empty($data['workers'])) {
                $jobCard->workers()->sync($data['workers']);
            }

            // Create initial activity log
            Activity::create([
                'job_card_id' => $jobCard->id,
                'user_id' => Auth::id(),
                'action' => 'job_card_created',
                'details' => 'Job Card initialized at status: Received'
            ]);
        });

        return back()->with('success', 'Job Card created successfully.');
    }

    /**
     * Quick status update.
     */
    public function updateStatus(Request $request, JobCard $jobCard)
    {
        $data = $request->validate([
            'status' => 'required|in:received-vehicle,on-going,blocked,testing,waiting-to-pickup'
        ]);

        $oldStatus = $jobCard->status;
        $newStatus = $data['status'];

        if ($oldStatus === $newStatus) {
            return back();
        }

        DB::transaction(function () use ($jobCard, $oldStatus, $newStatus) {
            $jobCard->status = $newStatus;
            
            if ($newStatus === 'waiting-to-pickup') {
                $jobCard->completed_at = now();
            }

            $jobCard->save();

            // Log activity
            Activity::create([
                'job_card_id' => $jobCard->id,
                'user_id' => Auth::id(),
                'action' => 'status_changed',
                'details' => "Status updated from '{$oldStatus}' to '{$newStatus}'"
            ]);
        });

        // Send FitSMS alert to client if status updates
        $vehicle = $jobCard->vehicle;
        $client = $vehicle->client;
        $statusLabels = [
            'received-vehicle' => 'Received',
            'on-going' => 'On-Going',
            'blocked' => 'Blocked/Delayed',
            'testing' => 'Testing',
            'waiting-to-pickup' => 'Ready for Pickup',
        ];

        $statusText = $statusLabels[$newStatus];
        $appName = config('app.name', 'Workshop Manager');
        $smsMessage = "Dear {$client->name}, your vehicle {$vehicle->make} {$vehicle->model} (Plate: {$vehicle->plate_number}) status is now updated to: {$statusText}. Thank you for choosing {$appName}.";

        $this->smsService->sendSms($client->phone, $smsMessage);

        return back()->with('success', "Job status updated to: {$statusText}. Alert sent to client.");
    }

    /**
     * Add comment.
     */
    public function addComment(Request $request, JobCard $jobCard)
    {
        $data = $request->validate([
            'content' => 'required|string|max:1000'
        ]);

        Comment::create([
            'job_card_id' => $jobCard->id,
            'user_id' => Auth::id(),
            'content' => $data['content']
        ]);

        return back()->with('success', 'Comment posted.');
    }

    /**
     * Allocate inventory parts to job card.
     */
    public function allocateParts(Request $request, JobCard $jobCard)
    {
        $data = $request->validate([
            'inventory_id' => 'required|exists:inventory,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255'
        ]);

        $part = Inventory::findOrFail($data['inventory_id']);

        if ($part->quantity < $data['quantity']) {
            return back()->withErrors(['quantity' => "Insufficient stock. Only {$part->quantity} {$part->unit} available."]);
        }

        DB::transaction(function () use ($jobCard, $part, $data) {
            // Deduct stock levels
            $part->quantity -= $data['quantity'];
            $part->save();

            // Create stock movement (out)
            StockMovement::create([
                'inventory_id' => $part->id,
                'job_card_id' => $jobCard->id,
                'type' => 'out',
                'quantity' => -$data['quantity'], // Negative representing usage
                'notes' => $data['notes'] ?? "Allocated to Job Card #{$jobCard->id}"
            ]);

            // Log activity
            Activity::create([
                'job_card_id' => $jobCard->id,
                'user_id' => Auth::id(),
                'action' => 'parts_allocated',
                'details' => "Allocated {$data['quantity']} {$part->unit} of {$part->name}"
            ]);
        });

        return back()->with('success', 'Parts successfully allocated to this job card.');
    }

    /**
     * Update worker assignments.
     */
    public function assignWorkers(Request $request, JobCard $jobCard)
    {
        $data = $request->validate([
            'workers' => 'nullable|array',
            'workers.*' => 'exists:users,id'
        ]);

        $jobCard->workers()->sync($data['workers'] ?? []);

        Activity::create([
            'job_card_id' => $jobCard->id,
            'user_id' => Auth::id(),
            'action' => 'workers_assigned',
            'details' => 'Technician assignments updated.'
        ]);

        return back()->with('success', 'Technicians updated.');
    }
}
