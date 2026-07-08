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

        $search = $request->input('search');

        $quotations = Quotation::when($search, function ($query, $search) {
            return $query->where('quotation_number', 'like', "%{$search}%")
                         ->orWhere('customer_name', 'like', "%{$search}%");
        })
        ->orderBy('created_at', 'desc')
        ->paginate(15);

        return view('quotations.index', compact('quotations', 'search'));
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
     * Remove the specified quotation from storage.
     */
    public function destroy(Quotation $quotation)
    {
        $this->checkAccess();

        $quotation->delete();

        return redirect()->route('quotations.index')->with('success', 'Quotation deleted successfully.');
    }
}
