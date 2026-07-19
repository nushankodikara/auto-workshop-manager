<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\QuotationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class QuotationController extends Controller
{
    private function checkAccess()
    {
        if (!Auth::user() || !Auth::user()->hasModuleAccess('quotations')) {
            abort(403, 'Unauthorized module access.');
        }
    }

    /**
     * Display a listing of quotations.
     */
    public function index(Request $request)
    {
        $this->checkAccess();

        $quotations = Quotation::orderBy('created_at', 'desc')->get();

        return view('quotations.index', compact('quotations'));
    }

    /**
     * Show the form for creating a new quotation.
     */
    public function create()
    {
        $this->checkAccess();

        $predefinedServices = \App\Models\PredefinedService::orderBy('name')->get();
        $predefinedParts = \App\Models\Inventory::where('quantity', '>', 0)->orderBy('name')->get();

        return view('quotations.create', compact('predefinedServices', 'predefinedParts'));
    }

    /**
     * Store a newly created quotation in storage.
     */
    public function store(Request $request)
    {
        $this->checkAccess();

        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_address' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'tax' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.type' => 'required|string|in:part,labor,outsourcing,other',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $quotation = DB::transaction(function () use ($data) {
            // Generate unique quotation number: QT-YYYYMMDD-XXXX
            $quotationNumber = 'QT-' . date('Ymd') . '-' . str_pad(Quotation::count() + 1, 4, '0', STR_PAD_LEFT);

            $quotation = Quotation::create([
                'quotation_number' => $quotationNumber,
                'customer_name' => $data['customer_name'],
                'customer_address' => $data['customer_address'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'],
                'tax' => $data['tax'] ?? 0.00,
                'discount_percent' => $data['discount_percent'] ?? 0.00,
                'total_amount' => 0.00, // We will update this after adding items
            ]);

            $subtotal = 0.00;

            foreach ($data['items'] as $item) {
                $qty = floatval($item['quantity']);
                $unitPrice = floatval($item['unit_price']);
                $totalPrice = $qty * $unitPrice;

                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'type' => $item['type'],
                    'description' => $item['description'],
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice
                ]);

                $subtotal += $totalPrice;
            }

            // Apply Discount
            $totalAmount = $subtotal;
            if ($quotation->discount_percent > 0) {
                $discountAmount = ($subtotal * ($quotation->discount_percent / 100));
                $totalAmount -= $discountAmount;
            }

            // Apply Tax
            if ($quotation->tax > 0) {
                $taxAmount = ($totalAmount * ($quotation->tax / 100));
                $totalAmount += $taxAmount;
            }

            $quotation->total_amount = $totalAmount;
            $quotation->save();

            return $quotation;
        });

        return redirect()->route('quotations.show', $quotation->id)->with('success', 'Quotation drafted successfully.');
    }

    /**
     * Display the specified quotation.
     */
    public function show(Quotation $quotation)
    {
        $this->checkAccess();

        $quotation->load('items');
        
        // Load default workshop details from settings or active configuration
        $shop = (object)[
            'name' => 'Total Drive Care',
            'address' => '295/A, Abhayathissa MW, Oruwala, Athurugiriya.'
        ];

        return view('quotations.show', compact('quotation', 'shop'));
    }

    /**
     * Show the form for editing the specified quotation.
     */
    public function edit(Quotation $quotation)
    {
        $this->checkAccess();

        $quotation->load('items');
        $predefinedServices = \App\Models\PredefinedService::orderBy('name')->get();
        $predefinedParts = \App\Models\Inventory::where('quantity', '>', 0)->orderBy('name')->get();

        return view('quotations.edit', compact('quotation', 'predefinedServices', 'predefinedParts'));
    }

    /**
     * Update the specified quotation in storage and capture a revision snapshot.
     */
    public function update(Request $request, Quotation $quotation)
    {
        $this->checkAccess();

        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_address' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'tax' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'revision_reason' => 'required|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.type' => 'required|string|in:part,labor,outsourcing,other',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $quotation->load('items');

        DB::transaction(function () use ($quotation, $data) {
            // 1. Calculate next revision number
            $nextRevNumber = $quotation->revisions()->count() + 1;

            // 2. Prepare JSON snapshot of the current state of the quotation
            $snapshot = [
                'customer_name' => $quotation->customer_name,
                'customer_address' => $quotation->customer_address,
                'customer_phone' => $quotation->customer_phone,
                'customer_email' => $quotation->customer_email,
                'tax' => floatval($quotation->tax),
                'discount_percent' => floatval($quotation->discount_percent),
                'total_amount' => floatval($quotation->total_amount),
                'items' => $quotation->items->map(function ($item) {
                    return [
                        'type' => $item->type,
                        'description' => $item->description,
                        'quantity' => floatval($item->quantity),
                        'unit_price' => floatval($item->unit_price),
                        'total_price' => floatval($item->total_price)
                    ];
                })->toArray()
            ];

            // 3. Save Revision Snapshot
            \App\Models\QuotationRevision::create([
                'quotation_id' => $quotation->id,
                'revision_number' => $nextRevNumber,
                'revised_by' => Auth::id(),
                'reason' => $data['revision_reason'],
                'total_amount' => $quotation->total_amount,
                'metadata' => $snapshot
            ]);

            // 4. Delete old line items
            $quotation->items()->delete();

            // 5. Update parent fields
            $quotation->update([
                'customer_name' => $data['customer_name'],
                'customer_address' => $data['customer_address'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'],
                'tax' => $data['tax'] ?? 0.00,
                'discount_percent' => $data['discount_percent'] ?? 0.00,
            ]);

            // 6. Create new line items & calculate subtotal
            $subtotal = 0.00;
            foreach ($data['items'] as $item) {
                $qty = floatval($item['quantity']);
                $unitPrice = floatval($item['unit_price']);
                $totalPrice = $qty * $unitPrice;

                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'type' => $item['type'],
                    'description' => $item['description'],
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice
                ]);

                $subtotal += $totalPrice;
            }

            // Apply Discount
            $totalAmount = $subtotal;
            if ($quotation->discount_percent > 0) {
                $discountAmount = ($subtotal * ($quotation->discount_percent / 100));
                $totalAmount -= $discountAmount;
            }

            // Apply Tax
            if ($quotation->tax > 0) {
                $taxAmount = ($totalAmount * ($quotation->tax / 100));
                $totalAmount += $taxAmount;
            }

            // Save new total amount
            $quotation->total_amount = $totalAmount;
            $quotation->save();
        });

        return redirect()->route('quotations.show', $quotation->id)->with('success', 'Quotation revised and updated successfully.');
    }

    /**
     * Remove the specified quotation from storage.
     */
    public function destroy(Quotation $quotation)
    {
        $this->checkAccess();

        $quotation->delete();

        return redirect()->route('quotations.index')->with('success', 'Quotation deleted successfully.');
    }
}
