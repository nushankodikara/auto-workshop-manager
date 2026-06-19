<?php

namespace App\Http\Controllers;

use App\Models\JobCard;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\StockMovement;
use App\Services\FitSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillingController extends Controller
{
    protected FitSmsService $smsService;

    public function __construct(FitSmsService $smsService)
    {
        $this->smsService = $smsService;
    }
    /**
     * Show billing workspace for a Job Card.
     */
    public function showWorkspace(JobCard $jobCard)
    {
        $jobCard->load(['vehicle.client', 'bill.items', 'stockMovements.inventory', 'services']);

        // Fetch unused stock movements that represent allocated parts
        $allocatedParts = [];
        if (!$jobCard->bill) {
            $allocatedParts = StockMovement::where('job_card_id', $jobCard->id)
                ->where('type', 'out')
                ->with('inventory')
                ->get();
        }

        return view('billing.workspace', compact('jobCard', 'allocatedParts'));
    }

    /**
     * Generate bill for a Job Card.
     */
    public function store(Request $request, JobCard $jobCard)
    {
        $data = $request->validate([
            'tax' => 'nullable|numeric|min:0',
            'status' => 'required|in:draft,paid',
            // Labor items
            'labor_desc' => 'nullable|array',
            'labor_price' => 'nullable|array',
            // Allocated parts mappings
            'parts' => 'nullable|array', // inventory_id => price
        ]);

        if ($jobCard->bill) {
            return back()->withErrors(['bill' => 'A bill already exists for this job card.']);
        }

        $bill = DB::transaction(function () use ($jobCard, $data) {
            // Generate unique bill number: INV-YYYYMMDD-XXXX
            $billNumber = 'INV-' . date('Ymd') . '-' . str_pad($jobCard->id, 4, '0', STR_PAD_LEFT);

            $bill = Bill::create([
                'job_card_id' => $jobCard->id,
                'bill_number' => $billNumber,
                'tax' => $data['tax'] ?? 0.00,
                'total_amount' => 0.00, // We will calculate this
                'status' => $data['status']
            ]);

            $totalAmount = 0.00;

            // 1. Add Part Items (from allocated stock movements)
            $allocatedMovements = StockMovement::where('job_card_id', $jobCard->id)
                ->where('type', 'out')
                ->with('inventory')
                ->get();

            foreach ($allocatedMovements as $mov) {
                $inv = $mov->inventory;
                $qty = abs($mov->quantity); // Make positive
                $unitPrice = $inv->price;
                $totalPrice = $qty * $unitPrice;

                BillItem::create([
                    'bill_id' => $bill->id,
                    'inventory_id' => $inv->id,
                    'type' => 'part',
                    'description' => $inv->name,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice
                ]);

                $totalAmount += $totalPrice;
            }

            // 2. Add Labor Items
            if (!empty($data['labor_desc'])) {
                foreach ($data['labor_desc'] as $key => $desc) {
                    if (empty($desc)) continue;

                    $price = $data['labor_price'][$key] ?? 0.00;

                    BillItem::create([
                        'bill_id' => $bill->id,
                        'inventory_id' => null,
                        'type' => 'labor',
                        'description' => $desc,
                        'quantity' => 1.00,
                        'unit_price' => $price,
                        'total_price' => $price
                    ]);

                    $totalAmount += $price;
                }
            }

            // 3. Apply Tax
            if ($bill->tax > 0) {
                $taxAmount = ($totalAmount * ($bill->tax / 100));
                $totalAmount += $taxAmount;
            }

            // 4. Update Bill Total
            $bill->total_amount = $totalAmount;
            $bill->save();

            return $bill;
        });

        // Send SMS alerts to client (Quotation / Paid)
        $vehicle = $jobCard->vehicle;
        $client = $vehicle->client;
        $appName = config('app.name', 'Auto Workshop Manager');
        $currency = config('app.currency', 'Rs.');
        $amountFormatted = $currency . number_format($bill->total_amount, 2);

        if ($bill->status === 'draft') {
            // Quotation SMS
            $message = "Dear {$client->name}, your service estimate/quotation for {$vehicle->make} {$vehicle->model} (Plate: {$vehicle->plate_number}) is ready. The total estimated amount is {$amountFormatted} (inclusive of tax). Thank you for choosing {$appName}.";
            $this->smsService->sendSms($client->phone, $message);
        } else {
            // Payment Received SMS
            $message = "Dear {$client->name}, thank you for your business! Payment of {$amountFormatted} has been received for vehicle {$vehicle->make} {$vehicle->model} (Plate: {$vehicle->plate_number}).";
            $this->smsService->sendSms($client->phone, $message);
        }

        return redirect()->route('billing.show', $jobCard->id)->with('success', 'Invoice generated successfully.');
    }

    /**
     * Show generated invoice.
     */
    public function show(JobCard $jobCard)
    {
        $jobCard->load(['vehicle.client', 'shop', 'bill.items']);
        
        if (!$jobCard->bill) {
            return redirect()->route('billing.workspace', $jobCard->id);
        }

        return view('billing.invoice', compact('jobCard'));
    }

    /**
     * Update bill status (e.g. from draft to paid).
     */
    public function updateStatus(Request $request, Bill $bill)
    {
        $data = $request->validate([
            'status' => 'required|in:draft,paid'
        ]);

        $oldStatus = $bill->status;
        $bill->update($data);

        if ($oldStatus === 'draft' && $bill->status === 'paid') {
            $jobCard = $bill->jobCard;
            $vehicle = $jobCard->vehicle;
            $client = $vehicle->client;
            $currency = config('app.currency', 'Rs.');
            $amountFormatted = $currency . number_format($bill->total_amount, 2);

            $message = "Dear {$client->name}, thank you for your business! Payment of {$amountFormatted} has been received for vehicle {$vehicle->make} {$vehicle->model} (Plate: {$vehicle->plate_number}).";
            $this->smsService->sendSms($client->phone, $message);
        }

        return back()->with('success', "Invoice status updated to: {$bill->status}");
    }
}
