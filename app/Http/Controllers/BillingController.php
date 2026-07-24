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
        $jobCard->load(['vehicle.client', 'bill.items', 'stockMovements.inventory', 'services',
                        'outsourcingItems.outsourcingCompany', 'miscParts']);

        if ($jobCard->bill && !auth()->user()->isSuperManager()) {
            return redirect()->route('billing.show', $jobCard->id);
        }

        // Fetch stock movements that represent allocated parts
        $allocatedParts = StockMovement::where('job_card_id', $jobCard->id)
            ->where('type', 'out')
            ->with(['inventory', 'purchaseBatch'])
            ->get();

        $partners = \App\Models\OutsourcingCompany::orderBy('name')->get();
        $predefinedServices = \App\Models\PredefinedService::orderBy('name')->get();

        return view('billing.workspace', compact('jobCard', 'allocatedParts', 'partners', 'predefinedServices'));
    }

    /**
     * Generate bill for a Job Card.
     */
    public function store(Request $request, JobCard $jobCard)
    {
        $data = $request->validate([
            'tax' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:draft,paid',
            // Labor items
            'labor_desc' => 'nullable|array',
            'labor_cost' => 'nullable|array',
            'labor_price' => 'nullable|array',
            // Outsourcing items
            'outsourcing_company_id' => 'nullable|array',
            'outsourcing_desc' => 'nullable|array',
            'outsourcing_cost' => 'nullable|array',
            'outsourcing_price' => 'nullable|array',
            // Allocated parts mappings (altered prices)
            'parts_cost' => 'nullable|array',
            'parts_price' => 'nullable|array',
        ]);

        if ($jobCard->bill && !auth()->user()->isSuperManager()) {
            return back()->withErrors(['bill' => 'A bill already exists for this job card. Only super admins can update it.']);
        }

        $bill = DB::transaction(function () use ($jobCard, $data) {
            if ($jobCard->bill) {
                $bill = $jobCard->bill;
                $bill->update([
                    'tax' => $data['tax'] ?? 0.00,
                    'discount_percent' => $data['discount_percent'] ?? 0.00,
                    'status' => $data['status']
                ]);
                // Delete existing items to rebuild them
                $bill->items()->delete();
            } else {
                // Generate unique bill number: INV-YYYYMMDD-XXXX
                $billNumber = 'INV-' . date('Ymd') . '-' . str_pad($jobCard->id, 4, '0', STR_PAD_LEFT);

                $bill = Bill::create([
                    'job_card_id' => $jobCard->id,
                    'bill_number' => $billNumber,
                    'tax' => $data['tax'] ?? 0.00,
                    'discount_percent' => $data['discount_percent'] ?? 0.00,
                    'total_amount' => 0.00, // We will calculate this
                    'status' => $data['status']
                ]);
            }

            $totalAmount = 0.00;

            // 1. Add Part Items (from allocated stock movements)
            $allocatedMovements = StockMovement::where('job_card_id', $jobCard->id)
                ->where('type', 'out')
                ->with(['inventory', 'purchaseBatch'])
                ->get();

            foreach ($allocatedMovements as $mov) {
                $inv = $mov->inventory;
                $qty = abs($mov->quantity); // Make positive
                
                // Fetch prices from request, fall back to batch or parent inventory values
                $unitPrice = isset($data['parts_price'][$mov->id]) ? floatval($data['parts_price'][$mov->id]) : ($mov->purchaseBatch ? $mov->purchaseBatch->selling_price : $inv->selling_price);
                $costPrice = isset($data['parts_cost'][$mov->id]) ? floatval($data['parts_cost'][$mov->id]) : ($mov->cost_price ?? ($mov->purchaseBatch ? $mov->purchaseBatch->cost_price : $inv->cost_price));
                
                $totalPrice = $qty * $unitPrice;

                BillItem::create([
                    'bill_id' => $bill->id,
                    'inventory_id' => $inv->id,
                    'outsourcing_company_id' => null,
                    'type' => 'part',
                    'description' => $inv->name,
                    'quantity' => $qty,
                    'cost_price' => $costPrice,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice
                ]);

                $totalAmount += $totalPrice;
            }

            // 2. Add Labor Items
            if (!empty($data['labor_desc'])) {
                foreach ($data['labor_desc'] as $key => $desc) {
                    if (empty($desc)) continue;

                    $cost = $data['labor_cost'][$key] ?? 0.00;
                    $price = $data['labor_price'][$key] ?? 0.00;

                    BillItem::create([
                        'bill_id' => $bill->id,
                        'inventory_id' => null,
                        'outsourcing_company_id' => null,
                        'type' => 'labor',
                        'description' => $desc,
                        'quantity' => 1.00,
                        'cost_price' => $cost,
                        'unit_price' => $price,
                        'total_price' => $price
                    ]);

                    $totalAmount += $price;
                }
            }

            // 3. Add Outsourcing Items from job card records
            // Job card outsourcing lines are the source of truth; billing workspace shows them pre-filled.
            // We pull directly from job_card_outsourcing so costs are always reflected correctly.
            $jobCardOutsourcingItems = \App\Models\JobCardOutsourcing::where('job_card_id', $jobCard->id)->get();
            foreach ($jobCardOutsourcingItems as $osi) {
                $price = floatval($osi->selling_price);
                BillItem::create([
                    'bill_id'                => $bill->id,
                    'inventory_id'           => null,
                    'outsourcing_company_id' => $osi->outsourcing_company_id,
                    'type'                   => 'outsourcing',
                    'description'            => $osi->description,
                    'quantity'               => 1.00,
                    'cost_price'             => floatval($osi->cost_price),
                    'unit_price'             => $price,
                    'total_price'            => $price,
                ]);
                $totalAmount += $price;
            }

            // Also include any additional outsourcing lines entered manually at billing time
            if (!empty($data['outsourcing_desc'])) {
                foreach ($data['outsourcing_desc'] as $key => $desc) {
                    if (empty($desc)) continue;

                    $companyId = $data['outsourcing_company_id'][$key] ?? null;
                    $cost = $data['outsourcing_cost'][$key] ?? 0.00;
                    $price = $data['outsourcing_price'][$key] ?? 0.00;

                    BillItem::create([
                        'bill_id'                => $bill->id,
                        'inventory_id'           => null,
                        'outsourcing_company_id' => $companyId,
                        'type'                   => 'outsourcing',
                        'description'            => $desc,
                        'quantity'               => 1.00,
                        'cost_price'             => $cost,
                        'unit_price'             => $price,
                        'total_price'            => $price,
                    ]);
                    $totalAmount += $price;
                }
            }

            // 4. Add Misc Parts from job card records
            // Misc parts are billed as 'part' type so their cost_price flows into COGS (5000)
            // and selling_price flows into Parts Revenue (4105) in the double-entry books.
            $miscParts = \App\Models\JobCardMiscPart::where('job_card_id', $jobCard->id)->get();
            foreach ($miscParts as $mp) {
                $price = floatval($mp->selling_price);
                BillItem::create([
                    'bill_id'                => $bill->id,
                    'inventory_id'           => null,
                    'outsourcing_company_id' => null,
                    'type'                   => 'part',  // 'part' ensures COGS is posted in DoubleEntryService
                    'description'            => $mp->name . ' (Misc / Dealer Direct)',
                    'quantity'               => 1.00,
                    'cost_price'             => floatval($mp->cost_price),
                    'unit_price'             => $price,
                    'total_price'            => $price,
                ]);
                $totalAmount += $price;
            }
            // 4. Add Transportation fee from job card
            $transSum = floatval($jobCard->transportations()->sum('amount'));
            if ($transSum == 0 && floatval($jobCard->transportation_fee) > 0) {
                $transSum = floatval($jobCard->transportation_fee);
            }
            $totalAmount += $transSum;

            // 5. Apply Discount Percentage
            $subtotal = $totalAmount;
            if ($bill->discount_percent > 0) {
                $discountAmount = ($subtotal * ($bill->discount_percent / 100));
                $totalAmount -= $discountAmount;
            }

            // 5. Apply Tax
            if ($bill->tax > 0) {
                $taxAmount = ($totalAmount * ($bill->tax / 100));
                $totalAmount += $taxAmount;
            }

            // 6. Update Bill Total
            $bill->total_amount = $totalAmount;
            $bill->save();

            // Sync to double entry bookkeeping system
            \App\Services\DoubleEntryService::postBillTransaction($bill);

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

        // Save last_sms to job card
        $jobCard->update(['last_sms' => $message]);

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

        // Sync to double entry bookkeeping system
        \App\Services\DoubleEntryService::postBillTransaction($bill);

        if ($oldStatus === 'draft' && $bill->status === 'paid') {
            $jobCard = $bill->jobCard;
            $vehicle = $jobCard->vehicle;
            $client = $vehicle->client;
            $currency = config('app.currency', 'Rs.');
            $amountFormatted = $currency . number_format($bill->total_amount, 2);

            $message = "Dear {$client->name}, thank you for your business! Payment of {$amountFormatted} has been received for vehicle {$vehicle->make} {$vehicle->model} (Plate: {$vehicle->plate_number}).";
            $this->smsService->sendSms($client->phone, $message);

            // Save last_sms to job card
            $jobCard->update(['last_sms' => $message]);
        }

        return back()->with('success', "Invoice status updated to: {$bill->status}");
    }

    private function checkSuperManager()
    {
        if (!auth()->user() || !auth()->user()->isSuperManager()) {
            abort(403, 'Unauthorized access.');
        }
    }

    /**
     * Show Tests & Utilities dashboard.
     */
    public function testsIndex()
    {
        $this->checkSuperManager();
        return view('tests.index');
    }

    /**
     * Show Dummy Invoice Generator workspace.
     */
    public function dummyWorkspace()
    {
        $this->checkSuperManager();
        $shops = \App\Models\Shop::orderBy('name')->get();
        return view('billing.dummy_workspace', compact('shops'));
    }

    /**
     * Generate and render the dummy invoice.
     */
    public function generateDummyInvoice(Request $request)
    {
        $this->checkSuperManager();

        $data = $request->validate([
            'shop_name' => 'required|string',
            'shop_address' => 'required|string',
            'customer_name' => 'required|string',
            'customer_phone' => 'required|string',
            'customer_email' => 'nullable|string',
            'customer_address' => 'nullable|string',
            'vehicle_make' => 'required|string',
            'vehicle_model' => 'required|string',
            'vehicle_year' => 'required|integer|min:1900|max:2100',
            'vehicle_plate' => 'required|string',
            'vehicle_vin' => 'nullable|string',
            'vehicle_mileage' => 'nullable|integer|min:0',
            'invoice_number' => 'required|string',
            'invoice_date' => 'required|date',
            'invoice_status' => 'required|in:draft,paid',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'tax_percent' => 'nullable|numeric|min:0',
            'towing_fee' => 'nullable|numeric|min:0',
            'advanced_payments' => 'nullable|numeric|min:0',
            // Line items
            'item_desc' => 'nullable|array',
            'item_type' => 'nullable|array',
            'item_qty' => 'nullable|array',
            'item_price' => 'nullable|array',
        ]);

        $clientObj = (object)[
            'name' => $data['customer_name'],
            'phone' => $data['customer_phone'],
            'email' => $data['customer_email'],
            'address' => $data['customer_address'],
        ];

        $vehicleObj = (object)[
            'make' => $data['vehicle_make'],
            'model' => $data['vehicle_model'],
            'year' => $data['vehicle_year'],
            'plate_number' => $data['vehicle_plate'],
            'vin' => $data['vehicle_vin'],
            'mileage' => $data['vehicle_mileage'],
            'client' => $clientObj,
        ];

        $shopObj = (object)[
            'name' => $data['shop_name'],
            'address' => $data['shop_address'],
        ];

        $billItems = collect();
        if (!empty($data['item_desc'])) {
            foreach ($data['item_desc'] as $key => $desc) {
                if (empty($desc)) continue;
                $qty = floatval($data['item_qty'][$key] ?? 1);
                $unitPrice = floatval($data['item_price'][$key] ?? 0);
                $billItems->push((object)[
                    'description' => $desc,
                    'type' => $data['item_type'][$key] ?? 'labor',
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'total_price' => $qty * $unitPrice,
                ]);
            }
        }

        $subtotal = $billItems->sum('total_price');
        $transSum = floatval($data['towing_fee'] ?? 0);
        $discountPercent = floatval($data['discount_percent'] ?? 0);
        $taxPercent = floatval($data['tax_percent'] ?? 0);
        $advancedPaymentsSum = floatval($data['advanced_payments'] ?? 0);

        $discountAmount = $subtotal * ($discountPercent / 100);
        $totalBeforeTax = $subtotal + $transSum - $discountAmount;
        $taxAmount = $totalBeforeTax * ($taxPercent / 100);
        $totalAmount = $totalBeforeTax + $taxAmount;

        $billObj = (object)[
            'bill_number' => $data['invoice_number'],
            'status' => $data['invoice_status'],
            'created_at' => \Carbon\Carbon::parse($data['invoice_date']),
            'items' => $billItems,
            'discount_percent' => $discountPercent,
            'tax' => $taxPercent,
            'total_amount' => $totalAmount,
        ];

        $jobCardObj = (object)[
            'id' => 0,
            'vehicle' => $vehicleObj,
            'shop' => $shopObj,
            'bill' => $billObj,
            'mileage' => $data['vehicle_mileage'],
            'transportations' => collect(),
            'transportation_fee' => $transSum,
            'advanced_payments_sum' => $advancedPaymentsSum,
        ];

        $isDummy = true;
        $jobCard = $jobCardObj;

        return view('billing.invoice', compact('jobCard', 'isDummy'));
    }
}
