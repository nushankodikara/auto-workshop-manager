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
        $search = trim($request->input('search', ''));
        $hasSearch = !empty($search);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Default to today if neither date is explicitly passed and no search query is active
        if (!$request->has('start_date') && !$request->has('end_date') && !$hasSearch) {
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d');
        }

        $unpaid = $request->boolean('unpaid');

        $start = $startDate ? \Carbon\Carbon::parse($startDate)->startOfDay() : null;
        $end = $endDate ? \Carbon\Carbon::parse($endDate)->endOfDay() : null;

        // Search scope matching card ID / card_number, vehicle plate, model/make, or client name
        $applySearch = function ($query) use ($search) {
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $cleanId = ltrim(ltrim($search, '#'), '0');
                    if (is_numeric($cleanId) && $cleanId > 0) {
                        $q->orWhere('job_cards.id', intval($cleanId));
                    }
                    $q->orWhere('job_cards.id', 'like', "%{$search}%")
                      ->orWhere('job_cards.card_number', 'like', "%{$search}%")
                      ->orWhereHas('vehicle', function ($vq) use ($search) {
                          $vq->where('plate_number', 'like', "%{$search}%")
                             ->orWhere('model', 'like', "%{$search}%")
                             ->orWhere('make', 'like', "%{$search}%")
                             ->orWhereHas('client', function ($cq) use ($search) {
                                 $cq->where('name', 'like', "%{$search}%");
                             });
                      });
                });
            }
        };

        if ($unpaid) {
            $query = JobCard::with([
                'vehicle.client',
                'shop',
                'workers',
                'bill',
                'services',
                'stockMovements.inventory',
                'stockMovements.purchaseBatch'
            ])->whereNotIn('status', ['received-vehicle', 'on-going']);

            $applySearch($query);

            $jobCards = $query->get()->filter(function ($jc) {
                if ($jc->bill) {
                    return $jc->bill->status !== 'paid';
                }
                return $jc->ticket_sum > 0;
            });
        } else {
            $query = JobCard::with([
                'vehicle.client',
                'shop',
                'workers',
                'bill',
                'services',
                'stockMovements.inventory',
                'stockMovements.purchaseBatch'
            ]);

            if ($start && $end) {
                $query->where(function ($dateQuery) use ($start, $end) {
                    // Condition 1: Unfinished tickets created on or before the end of the range
                    $dateQuery->where(function ($q) use ($end) {
                        $q->whereNotIn('status', ['waiting-to-pickup', 'abandoned'])
                          ->where('created_at', '<=', $end);
                    })
                    // Condition 2: Closed tickets (Ready or Abandoned) completed (or created if completed_at is null) within the range
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->whereIn('status', ['waiting-to-pickup', 'abandoned'])
                          ->where(function ($sub) use ($start, $end) {
                              $sub->whereBetween('completed_at', [$start, $end])
                                  ->orWhere(function ($sub2) use ($start, $end) {
                                      $sub2->whereNull('completed_at')
                                           ->whereBetween('created_at', [$start, $end]);
                                  });
                          });
                    });
                });
            }

            $applySearch($query);

            $jobCards = $query->get();
        }
        
        // Group by status
        $boardData = [
            'received-vehicle' => $jobCards->where('status', 'received-vehicle'),
            'on-going' => $jobCards->where('status', 'on-going'),
            'blocked' => $jobCards->where('status', 'blocked'),
            'testing' => $jobCards->where('status', 'testing'),
            'waiting-to-pickup' => $jobCards->where('status', 'waiting-to-pickup'),
            'abandoned' => $jobCards->where('status', 'abandoned'),
        ];

        $vehicles = Vehicle::with('client')->latest()->get();
        $shops = Shop::all();
        $workers = User::where('role', 'worker')->where('is_archived', false)->get();
        $managers = User::whereIn('role', ['super-manager', 'manager'])->where('is_archived', false)->get();

        return view('job-cards.board', compact('boardData', 'vehicles', 'shops', 'workers', 'managers', 'startDate', 'endDate', 'unpaid', 'search'));
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
            'advancedPayments',
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
            'transportation_fee' => 'nullable|numeric|min:0',
            'transportation_type' => 'nullable|in:provided,hire',
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
                'transportation_fee' => $data['transportation_fee'] ?? 0.00,
                'transportation_type' => $data['transportation_type'] ?? 'provided',
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
            'transportation_fee' => 'nullable|numeric|min:0',
            'transportation_type' => 'nullable|in:provided,hire',
            'mileage'      => 'nullable|integer|min:0',
            'created_at'   => 'nullable|date_format:Y-m-d\TH:i',
        ]);

        DB::transaction(function () use ($jobCard, $data, $request) {
            $jobCard->update([
                'notes'          => $data['notes'] ?? null,
                'estimated_cost' => $data['estimated_cost'] ?? $jobCard->estimated_cost ?? 0.00,
                'transportation_fee' => $data['transportation_fee'] ?? $jobCard->transportation_fee ?? 0.00,
                'transportation_type' => $data['transportation_type'] ?? $jobCard->transportation_type ?? 'provided',
                'mileage'        => $data['mileage'] ?? null,
            ]);

            // Re-post bill if it exists to reconcile
            if ($jobCard->bill) {
                \App\Services\DoubleEntryService::postBillTransaction($jobCard->bill);
            }

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
            'status' => 'required|in:received-vehicle,on-going,blocked,testing,waiting-to-pickup,abandoned'
        ]);

        $oldStatus = $jobCard->status;
        $newStatus = $data['status'];

        if ($oldStatus === $newStatus) {
            return back();
        }

        // Only super-managers (super admins) can mark tickets as abandoned or modify abandoned tickets
        if (($newStatus === 'abandoned' || $oldStatus === 'abandoned') && !Auth::user()->isSuperManager()) {
            return back()->withErrors(['status' => 'Only super admins can mark a job card as abandoned or update an abandoned ticket.']);
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
            } elseif ($newStatus === 'abandoned') {
                $jobCard->completed_at = \Illuminate\Support\Carbon::instance($now);

                // End all active assignments
                \App\Models\JobCardAssignment::where('job_card_id', $jobCard->id)
                    ->whereNull('unassigned_at')
                    ->update(['unassigned_at' => $now]);

                // Recognize actual costs incurred and eliminate any bills/revenues/profits
                \App\Services\DoubleEntryService::postJobCardAbandonedTransaction($jobCard);
            }

            // Re-open active assignments if transitioned out of waiting-to-pickup or abandoned
            if (in_array($oldStatus, ['waiting-to-pickup', 'abandoned']) && !in_array($newStatus, ['waiting-to-pickup', 'abandoned'])) {
                $jobCard->completed_at = null;
                
                if ($oldStatus === 'abandoned') {
                    // Revert abandoned write-off entry
                    \App\Services\DoubleEntryService::reverseJobCardAbandonedTransaction($jobCard);
                }

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

        // Send status updates alert to client (SMS & Email) - not sent for abandoned
        if ($newStatus !== 'abandoned') {
            $this->sendJobCardStatusNotification($jobCard, $newStatus);
        }

        $statusLabels = [
            'received-vehicle' => 'Received',
            'on-going' => 'On-Going',
            'blocked' => 'Blocked/Delayed',
            'testing' => 'Testing',
            'waiting-to-pickup' => 'Ready for Pickup',
            'abandoned' => 'Abandoned',
        ];
        $statusText = $statusLabels[$newStatus];

        if ($newStatus === 'abandoned') {
            return back()->with('success', "Job card marked as Abandoned by Super Admin. Incurred costs have been recorded to the ledger as expenses and bill revenues diminished.");
        }

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
     * Recalculate bill total amount (including transportation, items, discounts, tax)
     * and re-post double-entry ledger transactions when job card entries are updated.
     */
    protected function syncJobCardBill(JobCard $jobCard): void
    {
        if (!$jobCard->bill) {
            return;
        }

        $bill = $jobCard->bill;
        $itemsSum = floatval($bill->items()->sum('total_price'));
        $transSum = floatval($jobCard->transportations()->sum('amount'));
        if ($transSum == 0 && floatval($jobCard->transportation_fee) > 0) {
            $transSum = floatval($jobCard->transportation_fee);
        }

        $subtotal = $itemsSum + $transSum;
        if ($bill->discount_percent > 0) {
            $subtotal -= $subtotal * (floatval($bill->discount_percent) / 100);
        }
        if ($bill->tax > 0) {
            $subtotal += $subtotal * (floatval($bill->tax) / 100);
        }

        $bill->total_amount = $subtotal;
        $bill->saveQuietly();

        \App\Services\DoubleEntryService::postBillTransaction($bill);
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
     * Update service/task on Job Card.
     */
    public function updateService(Request $request, JobCardService $service)
    {
        $jobCard = $service->jobCard;
        if ($jobCard && $jobCard->bill && !Auth::user()->isSuperManager()) {
            return back()->withErrors(['bill' => 'This job card has already been billed. Only super admins can modify service operations.']);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:500'
        ]);

        DB::transaction(function () use ($service, $jobCard, $data) {
            $oldName = $service->name;

            $service->update($data);

            if ($jobCard && $jobCard->bill) {
                $billItem = $jobCard->bill->items()->where('type', 'labor')->where('description', $oldName)->first();
                if ($billItem) {
                    $billItem->update([
                        'description' => $service->name,
                        'unit_price' => $service->price,
                        'total_price' => $service->price,
                    ]);
                }
                $this->syncJobCardBill($jobCard);
            }

            // Log activity
            Activity::create([
                'job_card_id' => $service->job_card_id,
                'user_id' => Auth::id(),
                'action' => 'service_updated',
                'details' => "Updated service: '{$service->name}' for " . config('app.currency', '$') . number_format((double)$service->price, 2)
            ]);
        });

        return back()->with('success', 'Service operation updated successfully.');
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

        DB::transaction(function () use ($service, $jobCardId, $serviceName, $jobCard) {
            $service->delete();

            if ($jobCard && $jobCard->bill) {
                $billItem = $jobCard->bill->items()->where('type', 'labor')->where('description', $serviceName)->first();
                if ($billItem) {
                    $billItem->delete();
                }
                $this->syncJobCardBill($jobCard);
            }

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
     * Update an outsourcing line on a Job Card.
     */
    public function updateOutsourcing(Request $request, \App\Models\JobCardOutsourcing $outsourcingItem)
    {
        $jobCard = $outsourcingItem->jobCard;

        if ($jobCard && $jobCard->bill && !Auth::user()->isSuperManager()) {
            return back()->withErrors(['bill' => 'This job card has already been billed. Only super admins can modify outsourcing lines.']);
        }

        $data = $request->validate([
            'outsourcing_company_id' => 'nullable|exists:outsourcing_companies,id',
            'description'            => 'required|string|max:255',
            'cost_price'             => 'required|numeric|min:0',
            'selling_price'          => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($outsourcingItem, $jobCard, $data) {
            $oldDesc = $outsourcingItem->description;

            $outsourcingItem->update([
                'outsourcing_company_id' => $data['outsourcing_company_id'] ?? null,
                'description'            => $data['description'],
                'cost_price'             => $data['cost_price'],
                'selling_price'          => $data['selling_price'],
            ]);

            if ($jobCard && $jobCard->bill) {
                $billItem = $jobCard->bill->items()->where('type', 'outsourcing')->where('description', $oldDesc)->first();
                if ($billItem) {
                    $billItem->update([
                        'outsourcing_company_id' => $outsourcingItem->outsourcing_company_id,
                        'description'            => $outsourcingItem->description,
                        'cost_price'             => $outsourcingItem->cost_price,
                        'unit_price'             => $outsourcingItem->selling_price,
                        'total_price'            => $outsourcingItem->selling_price,
                    ]);
                }
                $this->syncJobCardBill($jobCard);
            }

            Activity::create([
                'job_card_id' => $jobCard->id,
                'user_id'     => Auth::id(),
                'action'      => 'outsourcing_updated',
                'details'     => "Outsourcing updated: {$outsourcingItem->description} — Selling: " . config('app.currency', 'Rs.') . number_format($outsourcingItem->selling_price, 2) . ", Cost: " . config('app.currency', 'Rs.') . number_format($outsourcingItem->cost_price, 2),
            ]);
        });

        return back()->with('success', "Outsourcing line '{$outsourcingItem->description}' updated.");
    }

    /**
     * Delete an outsourcing line from a Job Card.
     */
    public function deleteOutsourcing(\App\Models\JobCardOutsourcing $outsourcingItem)
    {
        $jobCard = $outsourcingItem->jobCard;

        if ($jobCard && $jobCard->bill && !Auth::user()->isSuperManager()) {
            return back()->withErrors(['bill' => 'This job card has already been billed. Only super admins can remove outsourcing lines.']);
        }

        DB::transaction(function () use ($outsourcingItem, $jobCard) {
            $desc = $outsourcingItem->description;

            $outsourcingItem->delete();

            if ($jobCard && $jobCard->bill) {
                $billItem = $jobCard->bill->items()->where('type', 'outsourcing')->where('description', $desc)->first();
                if ($billItem) {
                    $billItem->delete();
                }
                $this->syncJobCardBill($jobCard);
            }

            Activity::create([
                'job_card_id' => $jobCard->id,
                'user_id'     => Auth::id(),
                'action'      => 'outsourcing_removed',
                'details'     => "Outsourcing removed: {$desc}",
            ]);
        });

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
     * Update a misc part on a Job Card.
     */
    public function updateMiscPart(Request $request, \App\Models\JobCardMiscPart $miscPart)
    {
        $jobCard = $miscPart->jobCard;

        if ($jobCard && $jobCard->bill && !Auth::user()->isSuperManager()) {
            return back()->withErrors(['bill' => 'This job card has already been billed. Only super admins can modify misc parts.']);
        }

        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'cost_price'    => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($miscPart, $jobCard, $data) {
            $oldName = $miscPart->name;

            $miscPart->update([
                'name'          => $data['name'],
                'cost_price'    => $data['cost_price'],
                'selling_price' => $data['selling_price'],
            ]);

            if ($jobCard && $jobCard->bill) {
                $billItem = $jobCard->bill->items()->where('type', 'part')->where('description', 'like', $oldName . '%')->first();
                if ($billItem) {
                    $billItem->update([
                        'description' => $miscPart->name . ' (Misc / Dealer Direct)',
                        'cost_price'  => $miscPart->cost_price,
                        'unit_price'  => $miscPart->selling_price,
                        'total_price' => $miscPart->selling_price,
                    ]);
                }
                $this->syncJobCardBill($jobCard);
            }

            Activity::create([
                'job_card_id' => $jobCard->id,
                'user_id'     => Auth::id(),
                'action'      => 'misc_part_updated',
                'details'     => "Misc part updated: {$miscPart->name} — Cost: " . config('app.currency', 'Rs.') . number_format($miscPart->cost_price, 2) . ", Selling: " . config('app.currency', 'Rs.') . number_format($miscPart->selling_price, 2),
            ]);
        });

        return back()->with('success', "Misc part '{$miscPart->name}' updated.");
    }

    /**
     * Delete a misc part from a Job Card.
     */
    public function deleteMiscPart(\App\Models\JobCardMiscPart $miscPart)
    {
        $jobCard = $miscPart->jobCard;

        if ($jobCard && $jobCard->bill && !Auth::user()->isSuperManager()) {
            return back()->withErrors(['bill' => 'This job card has already been billed. Only super admins can remove misc parts.']);
        }

        DB::transaction(function () use ($jobCard, $miscPart) {
            $partName = $miscPart->name;
            $miscPart->delete();

            if ($jobCard && $jobCard->bill) {
                $billItem = $jobCard->bill->items()->where('type', 'part')->where('description', 'like', $partName . '%')->first();
                if ($billItem) {
                    $billItem->delete();
                }
                $this->syncJobCardBill($jobCard);
            }

            Activity::create([
                'job_card_id' => $jobCard->id,
                'user_id'     => Auth::id(),
                'action'      => 'misc_part_removed',
                'details'     => "Misc part removed: {$partName}",
            ]);
        });

        return back()->with('success', 'Misc part removed.');
    }

    /**
     * Record an advanced payment on a Job Card.
     */
    public function addAdvancedPayment(Request $request, JobCard $jobCard)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,card,bank_transfer',
            'transaction_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:255',
            'paid_at' => 'required|date',
        ]);

        $payment = DB::transaction(function () use ($jobCard, $data) {
            $payment = \App\Models\JobCardAdvancedPayment::create([
                'job_card_id' => $jobCard->id,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'transaction_reference' => $data['transaction_reference'],
                'notes' => $data['notes'],
                'paid_at' => \Carbon\Carbon::parse($data['paid_at']),
                'created_by' => Auth::id(),
            ]);

            // Sync to double entry bookkeeping system
            \App\Services\DoubleEntryService::postAdvancedPayment($payment);

            // Re-post bill if it exists to reconcile
            if ($jobCard->bill) {
                $this->syncJobCardBill($jobCard);
            }

            Activity::create([
                'job_card_id' => $jobCard->id,
                'user_id' => Auth::id(),
                'action' => 'advanced_payment_added',
                'details' => "Advanced payment of " . config('app.currency', 'Rs.') . number_format($payment->amount, 2) . " received via " . strtoupper($payment->payment_method)
            ]);

            return $payment;
        });

        return back()->with('success', 'Advanced payment recorded successfully.');
    }

    /**
     * Update an advanced payment on a Job Card.
     */
    public function updateAdvancedPayment(Request $request, \App\Models\JobCardAdvancedPayment $payment)
    {
        $jobCard = $payment->jobCard;

        if ($jobCard && $jobCard->bill && !Auth::user()->isSuperManager()) {
            return back()->withErrors(['bill' => 'This job card has already been billed. Only super admins can modify advanced payments.']);
        }

        $data = $request->validate([
            'amount'                => 'required|numeric|min:0.01',
            'payment_method'        => 'required|in:cash,card,bank_transfer',
            'transaction_reference' => 'nullable|string|max:255',
            'notes'                 => 'nullable|string|max:255',
            'paid_at'               => 'required|date',
        ]);

        DB::transaction(function () use ($payment, $jobCard, $data) {
            $payment->update([
                'amount'                => $data['amount'],
                'payment_method'        => $data['payment_method'],
                'transaction_reference' => $data['transaction_reference'],
                'notes'                 => $data['notes'],
                'paid_at'               => \Carbon\Carbon::parse($data['paid_at']),
            ]);

            // Sync to double entry bookkeeping system
            \App\Services\DoubleEntryService::postAdvancedPayment($payment);

            // Re-post bill if it exists to reconcile
            if ($jobCard && $jobCard->bill) {
                $this->syncJobCardBill($jobCard);
            }

            Activity::create([
                'job_card_id' => $jobCard->id,
                'user_id'     => Auth::id(),
                'action'      => 'advanced_payment_updated',
                'details'     => "Advanced payment updated to " . config('app.currency', 'Rs.') . number_format($payment->amount, 2) . " received via " . strtoupper($payment->payment_method)
            ]);
        });

        return back()->with('success', 'Advanced payment updated successfully.');
    }

    /**
     * Delete an advanced payment from a Job Card.
     */
    public function deleteAdvancedPayment(\App\Models\JobCardAdvancedPayment $payment)
    {
        $jobCard = $payment->jobCard;

        DB::transaction(function () use ($jobCard, $payment) {
            Activity::create([
                'job_card_id' => $jobCard->id,
                'user_id' => Auth::id(),
                'action' => 'advanced_payment_removed',
                'details' => "Advanced payment of " . config('app.currency', 'Rs.') . number_format($payment->amount, 2) . " removed"
            ]);

            // Delete associated journal entry if any
            \App\Models\JournalEntry::where('reference', 'ADV-PAY-' . $payment->id)->delete();

            $payment->delete();

            // Re-post bill if it exists to reconcile
            if ($jobCard->bill) {
                $this->syncJobCardBill($jobCard);
            }
        });

        return back()->with('success', 'Advanced payment deleted.');
    }

    /**
     * Add a Towing / Transportation log line to a Job Card.
     */
    public function addTransportation(Request $request, JobCard $jobCard)
    {
        if ($jobCard->bill && !Auth::user()->isSuperManager()) {
            return back()->withErrors(['bill' => 'This job card has already been billed. Only super admins can modify transportation details.']);
        }

        $data = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:provided,hire',
        ]);

        DB::transaction(function () use ($jobCard, $data) {
            $trans = \App\Models\JobCardTransportation::create([
                'job_card_id' => $jobCard->id,
                'description' => $data['description'],
                'amount' => $data['amount'],
                'type' => $data['type'],
            ]);

            // Re-post bill if it exists to reconcile
            if ($jobCard->bill) {
                $this->syncJobCardBill($jobCard);
            }

            Activity::create([
                'job_card_id' => $jobCard->id,
                'user_id' => Auth::id(),
                'action' => 'transportation_added',
                'details' => "Transportation log added: {$trans->description} (" . ($trans->type === 'provided' ? 'Company Provided' : 'Third-Party Hire') . ") - " . config('app.currency', 'Rs.') . number_format($trans->amount, 2)
            ]);
        });

        return back()->with('success', 'Transportation details recorded successfully.');
    }

    /**
     * Update a Towing / Transportation log line on a Job Card.
     */
    public function updateTransportation(Request $request, \App\Models\JobCardTransportation $transportation)
    {
        $jobCard = $transportation->jobCard;

        if ($jobCard && $jobCard->bill && !Auth::user()->isSuperManager()) {
            return back()->withErrors(['bill' => 'This job card has already been billed. Only super admins can modify transportation details.']);
        }

        $data = $request->validate([
            'description' => 'required|string|max:255',
            'amount'      => 'required|numeric|min:0.01',
            'type'        => 'required|in:provided,hire',
        ]);

        DB::transaction(function () use ($jobCard, $transportation, $data) {
            $transportation->update([
                'description' => $data['description'],
                'amount'      => $data['amount'],
                'type'        => $data['type'],
            ]);

            // Re-post bill if it exists to reconcile (including -HIRE posting or deletion)
            if ($jobCard && $jobCard->bill) {
                $this->syncJobCardBill($jobCard);
            }

            Activity::create([
                'job_card_id' => $jobCard->id,
                'user_id'     => Auth::id(),
                'action'      => 'transportation_updated',
                'details'     => "Transportation log updated: {$transportation->description} (" . ($transportation->type === 'provided' ? 'Company Provided' : 'Third-Party Hire') . ") - " . config('app.currency', 'Rs.') . number_format($transportation->amount, 2)
            ]);
        });

        return back()->with('success', 'Transportation details updated successfully.');
    }

    /**
     * Delete a Towing / Transportation log line.
     */
    public function deleteTransportation(\App\Models\JobCardTransportation $transportation)
    {
        $jobCard = $transportation->jobCard;

        if ($jobCard && $jobCard->bill && !Auth::user()->isSuperManager()) {
            return back()->withErrors(['bill' => 'This job card has already been billed. Only super admins can modify transportation details.']);
        }

        DB::transaction(function () use ($jobCard, $transportation) {
            Activity::create([
                'job_card_id' => $jobCard->id,
                'user_id' => Auth::id(),
                'action' => 'transportation_removed',
                'details' => "Transportation log removed: {$transportation->description} - " . config('app.currency', 'Rs.') . number_format($transportation->amount, 2)
            ]);

            $transportation->delete();

            // Re-post bill if it exists to reconcile
            if ($jobCard->bill) {
                $this->syncJobCardBill($jobCard);
            }
        });

        return back()->with('success', 'Transportation log deleted successfully.');
    }
}
