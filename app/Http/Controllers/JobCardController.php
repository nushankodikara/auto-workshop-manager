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
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Models\JobCardService;
use App\Models\PurchaseBatch;

class JobCardController extends Controller
{
    protected FitSmsService $smsService;
    protected EmailService $emailService;

    public function __construct(FitSmsService $smsService, EmailService $emailService)
    {
        $this->smsService = $smsService;
        $this->emailService = $emailService;
    }

    /**
     * Show the Kanban Board.
     */
    public function board(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-d'));
        $endDate = $request->input('end_date', date('Y-m-d'));

        $start = \Carbon\Carbon::parse($startDate)->startOfDay();
        $end = \Carbon\Carbon::parse($endDate)->endOfDay();

        $jobCards = JobCard::with([
            'vehicle.client',
            'shop',
            'workers',
            'bill',
            'services',
            'stockMovements.inventory',
            'stockMovements.purchaseBatch'
        ])
            ->where(function ($query) use ($start, $end) {
                // Condition 1: Unfinished tickets created on or before the end of the range
                $query->where(function ($q) use ($end) {
                    $q->where('status', '!=', 'waiting-to-pickup')
                      ->where('created_at', '<=', $end);
                })
                // Condition 2: Finished tickets completed (or created if completed_at is null) within the range
                ->orWhere(function ($q) use ($start, $end) {
                    $q->where('status', '=', 'waiting-to-pickup')
                      ->where(function ($sub) use ($start, $end) {
                          $sub->whereBetween('completed_at', [$start, $end])
                              ->orWhere(function ($sub2) use ($start, $end) {
                                  $sub2->whereNull('completed_at')
                                       ->whereBetween('created_at', [$start, $end]);
                              });
                      });
                });
            })
            ->get();
        
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
        $workers = User::where('role', 'worker')->where('is_archived', false)->get();
        $managers = User::whereIn('role', ['super-manager', 'manager'])->where('is_archived', false)->get();

        return view('job-cards.board', compact('boardData', 'vehicles', 'shops', 'workers', 'managers', 'startDate', 'endDate'));
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
            'bill.items',
            'services',
            'outsourcingItems.outsourcingCompany',
            'miscParts',
        ]);

        $allWorkers = User::where('role', 'worker')->where('is_archived', false)->get();
        $inventoryItems = Inventory::where('quantity', '>', 0)
            ->with(['purchaseBatches' => function ($q) {
                $q->where('quantity_remaining', '>', 0)->orderBy('purchased_at', 'asc')->orderBy('id', 'asc');
            }])
            ->get();

        $outsourcingCompanies = \App\Models\OutsourcingCompany::orderBy('name')->get();

        return view('job-cards.show', compact('jobCard', 'allWorkers', 'inventoryItems', 'outsourcingCompanies'));
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
            'estimated_cost' => 'nullable|numeric|min:0',
            'mileage' => 'nullable|integer|min:0',
            'workers' => 'nullable|array',
            'workers.*' => 'exists:users,id',
        ]);

        $jobCard = DB::transaction(function () use ($data) {
            $jobCard = JobCard::create([
                'vehicle_id' => $data['vehicle_id'],
                'shop_id' => $data['shop_id'],
                'notes' => $data['notes'] ?? null,
                'estimated_cost' => $data['estimated_cost'] ?? 0.00,
                'status' => 'received-vehicle',
                'mileage' => $data['mileage'] ?? null
            ]);

            // Attach workers if any
            if (!empty($data['workers'])) {
                $jobCard->workers()->sync($data['workers']);
                
                foreach ($data['workers'] as $workerId) {
                    \App\Models\JobCardAssignment::create([
                        'job_card_id' => $jobCard->id,
                        'user_id' => $workerId,
                        'assigned_at' => $jobCard->created_at ?: now()
                    ]);
                }
            }

            // Check and update vehicle mileage if higher
            if (!empty($data['mileage'])) {
                $vehicle = $jobCard->vehicle;
                if ($data['mileage'] > ($vehicle->mileage ?? 0)) {
                    $vehicle->update(['mileage' => $data['mileage']]);
                }
            }

            // Create initial activity log
            Activity::create([
                'job_card_id' => $jobCard->id,
                'user_id' => Auth::id(),
                'action' => 'job_card_created',
                'details' => 'Job Card initialized at status: Received'
            ]);

            return $jobCard;
        });

        // Load vehicle and client relation for status updates alert
        $jobCard->load('vehicle.client');

        $this->sendJobCardStatusNotification($jobCard, 'received-vehicle');

        return back()->with('success', 'Job Card created successfully.');
    }

    /**
     * Update Job Card details.
     */
    public function update(Request $request, JobCard $jobCard)
    {
        if ($jobCard->bill && !Auth::user()->isSuperManager()) {
            return back()->withErrors(['bill' => 'This job card has already been billed. Only super admins can update it.']);
        }

        $data = $request->validate([
            'notes'        => 'nullable|string',
            'estimated_cost' => 'nullable|numeric|min:0',
            'mileage'      => 'nullable|integer|min:0',
            'created_at'   => 'nullable|date_format:Y-m-d\TH:i',
        ]);

        DB::transaction(function () use ($jobCard, $data, $request) {
            $jobCard->update([
                'notes'          => $data['notes'] ?? null,
                'estimated_cost' => $data['estimated_cost'] ?? $jobCard->estimated_cost ?? 0.00,
                'mileage'        => $data['mileage'] ?? null,
            ]);

            // Check and update vehicle mileage if higher
            if (!empty($data['mileage'])) {
                $vehicle = $jobCard->vehicle;
                if ($data['mileage'] > ($vehicle->mileage ?? 0)) {
                    $vehicle->update(['mileage' => $data['mileage']]);
                }
            }

            // Super-admin: correct the ticket created_at timestamp
            if (!empty($data['created_at']) && Auth::user()->isSuperManager()) {
                $oldTime = $jobCard->created_at->format('Y-m-d H:i');
                $newTime = \Carbon\Carbon::parse($data['created_at'])->format('Y-m-d H:i:s');

                // Use raw query to bypass Eloquent's automatic timestamp touch
                DB::table('job_cards')
                    ->where('id', $jobCard->id)
                    ->update(['created_at' => $newTime]);

                Activity::create([
                    'job_card_id' => $jobCard->id,
                    'user_id'     => Auth::id(),
                    'action'      => 'ticket_time_corrected',
                    'details'     => "Ticket start time corrected from {$oldTime} to " . \Carbon\Carbon::parse($newTime)->format('Y-m-d H:i') . " by super admin.",
                ]);
            } else {
                // Log regular update activity
                Activity::create([
                    'job_card_id' => $jobCard->id,
                    'user_id'     => Auth::id(),
                    'action'      => 'job_card_updated',
                    'details'     => 'Job Card details updated: mileage, notes, or cost',
                ]);
            }
        });

        return back()->with('success', 'Job Card updated successfully.');
    }

    /**
     * Quick status update.
     */
    public function updateStatus(Request $request, JobCard $jobCard)
    {
        if ($jobCard->bill && !Auth::user()->isSuperManager()) {
            return back()->withErrors(['bill' => 'This job card has already been billed. Only super admins can change its status.']);
        }

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
            
            $now = now();
            if ($newStatus === 'waiting-to-pickup') {
                $jobCard->completed_at = \Illuminate\Support\Carbon::instance($now);
                
                // End all active assignments
                \App\Models\JobCardAssignment::where('job_card_id', $jobCard->id)
                    ->whereNull('unassigned_at')
                    ->update(['unassigned_at' => $now]);
            }

            // Re-open active assignments if transitioned out of waiting-to-pickup
            if ($oldStatus === 'waiting-to-pickup' && $newStatus !== 'waiting-to-pickup') {
                $jobCard->completed_at = null;
                
                foreach ($jobCard->workers as $worker) {
                    \App\Models\JobCardAssignment::create([
                        'job_card_id' => $jobCard->id,
                        'user_id' => $worker->id,
                        'assigned_at' => $now
                    ]);
                }
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

        // Send status updates alert to client (SMS & Email)
        $this->sendJobCardStatusNotification($jobCard, $newStatus);

        $statusLabels = [
            'received-vehicle' => 'Received',
            'on-going' => 'On-Going',
            'blocked' => 'Blocked/Delayed',
            'testing' => 'Testing',
            'waiting-to-pickup' => 'Ready for Pickup',
        ];
        $statusText = $statusLabels[$newStatus];

        return back()->with('success', "Job status updated to: {$statusText}. Alert sent to client.");
    }

    /**
     * Send status updates alert to client (SMS & Email)
     */
    private function sendJobCardStatusNotification(JobCard $jobCard, string $status)
    {
        $vehicle = $jobCard->vehicle;
        $client = $vehicle->client;
        $appName = config('app.name', 'Auto Workshop Manager');

        if ($status !== 'blocked') {
            $smsMessage = '';
            $emailSubject = '';
            $emailBody = '';

            switch ($status) {
                case 'received-vehicle':
                    $smsMessage = "Dear {$client->name}, your vehicle {$vehicle->make} {$vehicle->model} (Plate: {$vehicle->plate_number}) has been received at {$appName} for: " . ($jobCard->notes ?: 'general inspection') . ". We will keep you updated.";
                    $emailSubject = "Vehicle Received - Job Card #{$jobCard->id}";
                    $emailBody = "Hello {$client->name},\n\nWe have successfully received your vehicle {$vehicle->make} {$vehicle->model} (Plate: {$vehicle->plate_number}) for servicing and diagnostics at {$appName}.\n\nInstructions/Notes:\n" . ($jobCard->notes ?: 'General inspection and maintenance.') . "\n\nTicket Sum: " . config('app.currency') . number_format($jobCard->ticket_sum, 2) . "\n\nWe will notify you as soon as the repair operations commence.\n\nBest regards,\n{$appName} Team";
                    break;

                case 'on-going':
                    $smsMessage = "Dear {$client->name}, repair and maintenance work on your vehicle {$vehicle->make} {$vehicle->model} (Plate: {$vehicle->plate_number}) is now in progress. We will notify you once complete.";
                    $emailSubject = "Repair in Progress - Job Card #{$jobCard->id}";
                    $emailBody = "Hello {$client->name},\n\nThis is to notify you that repair and servicing operations for your vehicle {$vehicle->make} {$vehicle->model} (Plate: {$vehicle->plate_number}) are now actively in progress.\n\nOur technicians are working to complete the tasks as scheduled. We will send you an update once the vehicle undergoes quality testing.\n\nBest regards,\n{$appName} Team";
                    break;

                case 'testing':
                    $smsMessage = "Dear {$client->name}, repairs on your vehicle {$vehicle->make} {$vehicle->model} (Plate: {$vehicle->plate_number}) are complete. It is currently being tested by our mechanics.";
                    $emailSubject = "Quality Testing & Diagnostics - Job Card #{$jobCard->id}";
                    $emailBody = "Hello {$client->name},\n\nThe mechanical and repair work on your vehicle {$vehicle->make} {$vehicle->model} (Plate: {$vehicle->plate_number}) is now complete. The vehicle is currently undergoing quality control testing, road tests, and system diagnostics by our chief mechanics.\n\nWe will notify you immediately once the testing is finalized and the vehicle is cleared for collection.\n\nBest regards,\n{$appName} Team";
                    break;

                case 'waiting-to-pickup':
                    $smsMessage = "Dear {$client->name}, your vehicle {$vehicle->make} {$vehicle->model} (Plate: {$vehicle->plate_number}) is ready to be picked up from {$appName}. Thank you!";
                    $emailSubject = "Ready for Collection - Job Card #{$jobCard->id}";
                    $emailBody = "Hello {$client->name},\n\nWe are pleased to inform you that your vehicle {$vehicle->make} {$vehicle->model} (Plate: {$vehicle->plate_number}) has successfully passed all quality control tests and is ready to be picked up at your convenience.\n\nFinal Cost Summary: " . config('app.currency') . number_format($jobCard->ticket_sum, 2) . "\n\nThank you for choosing {$appName}!\n\nBest regards,\n{$appName} Team";
                    break;
            }

            if (!empty($smsMessage)) {
                $this->smsService->sendSms($client->phone, $smsMessage);
            }

            if (!empty($client->email) && !empty($emailSubject) && !empty($emailBody)) {
                $this->emailService->sendEmail($client->email, $emailSubject, $emailBody);
            }

            // Update last_email and last_sms on job card
            $updates = [];
            if (!empty($smsMessage)) {
                $updates['last_sms'] = $smsMessage;
            }
            if (!empty($emailBody)) {
                $updates['last_email'] = $emailBody;
            }
            if (!empty($updates)) {
                $jobCard->update($updates);
            }
        }
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
        if ($jobCard->bill && !Auth::user()->isSuperManager()) {
            return back()->withErrors(['bill' => 'This job card has already been billed. Only super admins can allocate parts.']);
        }

        $data = $request->validate([
            'inventory_id' => 'required|exists:inventory,id',
            'purchase_batch_id' => 'required|exists:purchase_batches,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255'
        ]);

        $batch = PurchaseBatch::findOrFail($data['purchase_batch_id']);

        if ($batch->inventory_id != $data['inventory_id']) {
            return back()->withErrors(['purchase_batch_id' => 'The selected batch does not belong to the selected part.']);
        }

        if ($batch->quantity_remaining < $data['quantity']) {
            return back()->withErrors(['quantity' => "Insufficient stock in selected batch. Only {$batch->quantity_remaining} {$batch->inventory->unit} available."]);
        }

        DB::transaction(function () use ($jobCard, $batch, $data) {
            $part = $batch->inventory;

            // Deduct stock levels
            $batch->quantity_remaining -= $data['quantity'];
            $batch->save();

            $part->quantity -= $data['quantity'];
            $part->save();

            // Create stock movement (out)
            StockMovement::create([
                'inventory_id' => $part->id,
                'purchase_batch_id' => $batch->id,
                'job_card_id' => $jobCard->id,
                'type' => 'out',
                'quantity' => -$data['quantity'], // Negative representing usage
                'cost_price' => $batch->cost_price,
                'notes' => $data['notes'] ?? "Allocated from batch: {$batch->batch_code}"
            ]);

            // Log activity
            Activity::create([
                'job_card_id' => $jobCard->id,
                'user_id' => Auth::id(),
                'action' => 'parts_allocated',
                'details' => "Allocated {$data['quantity']} {$part->unit} of {$part->name} from batch {$batch->batch_code}"
            ]);
        });

        return back()->with('success', 'Parts successfully allocated to this job card.');
    }

    public function assignWorkers(Request $request, JobCard $jobCard)
    {
        if ($jobCard->bill && !Auth::user()->isSuperManager()) {
            return back()->withErrors(['bill' => 'This job card has already been billed. Only super admins can manage technician assignments.']);
        }

        $data = $request->validate([
            'workers' => 'nullable|array',
            'workers.*' => 'exists:users,id'
        ]);

        $workers = $data['workers'] ?? [];

        DB::transaction(function () use ($jobCard, $workers) {
            $currentWorkers = $jobCard->workers()->pluck('users.id')->toArray();
            $jobCard->workers()->sync($workers);

            // Workers added
            $added = array_diff($workers, $currentWorkers);
            // Workers removed
            $removed = array_diff($currentWorkers, $workers);

            $now = now();

            foreach ($added as $workerId) {
                if ($jobCard->status !== 'waiting-to-pickup') {
                    $hasPrevious = \App\Models\JobCardAssignment::where('job_card_id', $jobCard->id)
                        ->where('user_id', $workerId)
                        ->exists();

                    $assignedAt = $hasPrevious ? $now : ($jobCard->created_at ?: $now);

                    \App\Models\JobCardAssignment::create([
                        'job_card_id' => $jobCard->id,
                        'user_id' => $workerId,
                        'assigned_at' => $assignedAt
                    ]);
                }
            }

            foreach ($removed as $workerId) {
                // Find and close active assignment
                $assignment = \App\Models\JobCardAssignment::where('job_card_id', $jobCard->id)
                    ->where('user_id', $workerId)
                    ->whereNull('unassigned_at')
                    ->first();
                if ($assignment) {
                    $assignment->update(['unassigned_at' => $now]);
                }
            }

            Activity::create([
                'job_card_id' => $jobCard->id,
                'user_id' => Auth::id(),
                'action' => 'workers_assigned',
                'details' => 'Technician assignments updated.'
            ]);
        });

        return back()->with('success', 'Technicians updated.');
    }

    /**
     * Add service/task to Job Card.
     */
    public function addService(Request $request, JobCard $jobCard)
    {
        if ($jobCard->bill && !Auth::user()->isSuperManager()) {
            return back()->withErrors(['bill' => 'This job card has already been billed. Only super admins can add service operations.']);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:500'
        ]);

        DB::transaction(function () use ($jobCard, $data) {
            $service = $jobCard->services()->create($data);

            // Log activity
            Activity::create([
                'job_card_id' => $jobCard->id,
                'user_id' => Auth::id(),
                'action' => 'service_added',
                'details' => "Added service: '{$service->name}' for " . config('app.currency', '$') . number_format((double)$service->price, 2)
            ]);
        });

        return back()->with('success', 'Service operation added successfully.');
    }

    /**
     * Delete service/task from Job Card.
     */
    public function deleteService(JobCardService $service)
    {
        $jobCard = $service->jobCard;
        if ($jobCard && $jobCard->bill && !Auth::user()->isSuperManager()) {
            return back()->withErrors(['bill' => 'This job card has already been billed. Only super admins can delete service operations.']);
        }

        $jobCardId = $service->job_card_id;
        $serviceName = $service->name;

        DB::transaction(function () use ($service, $jobCardId, $serviceName) {
            $service->delete();

            // Log activity
            Activity::create([
                'job_card_id' => $jobCardId,
                'user_id' => Auth::id(),
                'action' => 'service_removed',
                'details' => "Removed service: '{$serviceName}'"
            ]);
        });

        return back()->with('success', 'Service operation removed.');
    }

    /**
     * Update allocated parts quantity and notes on job card.
     */
    public function updateAllocatedPart(Request $request, StockMovement $stockMovement)
    {
        $jobCard = $stockMovement->jobCard;
        if ($jobCard->bill && !Auth::user()->isSuperManager()) {
            return back()->withErrors(['bill' => 'This job card has already been billed. Only super admins can modify allocated parts.']);
        }

        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255'
        ]);

        $batch = $stockMovement->purchaseBatch;
        $part = $stockMovement->inventory;

        if (!$batch || !$part) {
            return back()->withErrors(['error' => 'Associated inventory part or batch not found.']);
        }

        $oldQty = abs($stockMovement->quantity);
        $newQty = intval($data['quantity']);
        $diff = $newQty - $oldQty;

        if ($diff > 0 && $batch->quantity_remaining < $diff) {
            return back()->withErrors(['quantity' => "Insufficient stock in batch. Only {$batch->quantity_remaining} {$part->unit} available."]);
        }

        DB::transaction(function () use ($stockMovement, $jobCard, $batch, $part, $newQty, $oldQty, $diff, $data) {
            // Adjust stock levels
            $batch->quantity_remaining -= $diff;
            $batch->save();

            $part->quantity -= $diff;
            $part->save();

            $stockMovement->update([
                'quantity' => -$newQty, // stored as negative
                'notes' => $data['notes'] ?? $stockMovement->notes
            ]);

            // Log activity
            Activity::create([
                'job_card_id' => $jobCard->id,
                'user_id' => Auth::id(),
                'action' => 'parts_allocated_updated',
                'details' => "Updated allocation for {$part->name}: quantity changed from {$oldQty} to {$newQty}"
            ]);
        });

        return back()->with('success', 'Allocated parts updated successfully.');
    }

    /**
     * Remove allocated parts from job card and return to inventory stock.
     */
    public function deallocateParts(StockMovement $stockMovement)
    {
        $jobCard = $stockMovement->jobCard;
        if ($jobCard->bill && !Auth::user()->isSuperManager()) {
            return back()->withErrors(['bill' => 'This job card has already been billed. Only super admins can modify allocated parts.']);
        }

        DB::transaction(function () use ($stockMovement, $jobCard) {
            $qty = abs($stockMovement->quantity); // since stored as negative
            $batch = $stockMovement->purchaseBatch;
            $part = $stockMovement->inventory;

            if ($batch) {
                $batch->quantity_remaining += $qty;
                $batch->save();
            }

            if ($part) {
                $part->quantity += $qty;
                $part->save();
            }

            // Log activity before deletion
            Activity::create([
                'job_card_id' => $jobCard->id,
                'user_id' => Auth::id(),
                'action' => 'parts_deallocated',
                'details' => "Removed allocation of {$qty} " . ($part->unit ?? 'pcs') . " for " . ($part->name ?? 'Unknown') . ($batch ? " from batch {$batch->batch_code}" : '')
            ]);

            $stockMovement->delete();
        });

        return back()->with('success', 'Allocated parts successfully removed and returned to stock.');
    }

    // ── Outsourcing (Specialist Services) on Job Card ─────────────

    /**
     * Add an outsourced/specialist service line to a Job Card.
     */
    public function addOutsourcing(Request $request, JobCard $jobCard)
    {
        if ($jobCard->bill && !Auth::user()->isSuperManager()) {
            return back()->withErrors(['bill' => 'This job card has already been billed. Only super admins can add outsourcing lines.']);
        }

        $data = $request->validate([
            'outsourcing_company_id' => 'nullable|exists:outsourcing_companies,id',
            'description'            => 'required|string|max:255',
            'cost_price'             => 'required|numeric|min:0',
            'selling_price'          => 'required|numeric|min:0',
        ]);

        $item = \App\Models\JobCardOutsourcing::create([
            'job_card_id'            => $jobCard->id,
            'outsourcing_company_id' => $data['outsourcing_company_id'] ?? null,
            'description'            => $data['description'],
            'cost_price'             => $data['cost_price'],
            'selling_price'          => $data['selling_price'],
        ]);

        Activity::create([
            'job_card_id' => $jobCard->id,
            'user_id'     => Auth::id(),
            'action'      => 'outsourcing_added',
            'details'     => "Outsourcing added: {$item->description} — Selling: " . config('app.currency', 'Rs.') . number_format($item->selling_price, 2),
        ]);

        return back()->with('success', "Outsourcing line '{$item->description}' added.");
    }

    /**
     * Delete an outsourcing line from a Job Card.
     */
    public function deleteOutsourcing(\App\Models\JobCardOutsourcing $outsourcingItem)
    {
        $jobCard = $outsourcingItem->jobCard;

        if ($jobCard->bill && !Auth::user()->isSuperManager()) {
            return back()->withErrors(['bill' => 'This job card has already been billed. Only super admins can remove outsourcing lines.']);
        }

        Activity::create([
            'job_card_id' => $jobCard->id,
            'user_id'     => Auth::id(),
            'action'      => 'outsourcing_removed',
            'details'     => "Outsourcing removed: {$outsourcingItem->description}",
        ]);

        $outsourcingItem->delete();

        return back()->with('success', 'Outsourcing line removed.');
    }

    // ── Misc Parts (Dealer-Direct) on Job Card ────────────────────

    /**
     * Add a misc (dealer-direct) part line to a Job Card.
     * Cost and selling prices are recorded for bookkeeping via billing.
     */
    public function addMiscPart(Request $request, JobCard $jobCard)
    {
        if ($jobCard->bill && !Auth::user()->isSuperManager()) {
            return back()->withErrors(['bill' => 'This job card has already been billed. Only super admins can add misc parts.']);
        }

        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'cost_price'    => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
        ]);

        $part = \App\Models\JobCardMiscPart::create([
            'job_card_id'   => $jobCard->id,
            'name'          => $data['name'],
            'cost_price'    => $data['cost_price'],
            'selling_price' => $data['selling_price'],
        ]);

        Activity::create([
            'job_card_id' => $jobCard->id,
            'user_id'     => Auth::id(),
            'action'      => 'misc_part_added',
            'details'     => "Misc part added: {$part->name} — Cost: " . config('app.currency', 'Rs.') . number_format($part->cost_price, 2) . ", Selling: " . config('app.currency', 'Rs.') . number_format($part->selling_price, 2),
        ]);

        return back()->with('success', "Misc part '{$part->name}' added.");
    }

    /**
     * Delete a misc part from a Job Card.
     */
    public function deleteMiscPart(\App\Models\JobCardMiscPart $miscPart)
    {
        $jobCard = $miscPart->jobCard;

        if ($jobCard->bill && !Auth::user()->isSuperManager()) {
            return back()->withErrors(['bill' => 'This job card has already been billed. Only super admins can remove misc parts.']);
        }

        Activity::create([
            'job_card_id' => $jobCard->id,
            'user_id'     => Auth::id(),
            'action'      => 'misc_part_removed',
            'details'     => "Misc part removed: {$miscPart->name}",
        ]);

        $miscPart->delete();

        return back()->with('success', 'Misc part removed.');
    }
}
