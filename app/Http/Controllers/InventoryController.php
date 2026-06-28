<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * List inventory items.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        
        $items = Inventory::when($search, function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
        })->latest()->paginate(15);

        // Fetch recent stock movements
        $recentMovements = StockMovement::with(['inventory', 'jobCard.vehicle'])
            ->latest()
            ->limit(10)
            ->get();

        return view('inventory.index', compact('items', 'recentMovements', 'search'));
    }

    /**
     * Show detailed inventory item.
     */
    public function show(Inventory $item)
    {
        $item->load(['purchaseBatches' => function ($q) {
            $q->orderBy('purchased_at', 'desc')->orderBy('id', 'desc');
        }, 'stockMovements' => function ($q) {
            $q->with('jobCard.vehicle')->latest();
        }]);

        // Get price history from purchase batches
        $priceHistory = $item->purchaseBatches()
            ->orderBy('purchased_at', 'asc')
            ->get(['purchased_at', 'cost_price', 'selling_price', 'batch_code']);

        return view('inventory.show', compact('item', 'priceHistory'));
    }

    /**
     * Store a new inventory item.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:50|unique:inventory,sku',
            'quantity' => 'required|integer|min:0',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:20',
            'low_stock_alert_qty' => 'nullable|integer|min:0',
        ]);

        $data['low_stock_alert_qty'] = $data['low_stock_alert_qty'] ?? 0;

        DB::transaction(function () use ($data) {
            $item = Inventory::create($data);

            // Create initial batch if quantity > 0
            if ($item->quantity > 0) {
                $batchCode = 'BAT-INIT-' . str_pad($item->id, 3, '0', STR_PAD_LEFT);
                $batch = \App\Models\PurchaseBatch::create([
                    'inventory_id' => $item->id,
                    'batch_code' => $batchCode,
                    'quantity_received' => $item->quantity,
                    'quantity_remaining' => $item->quantity,
                    'cost_price' => $item->cost_price,
                    'selling_price' => $item->selling_price,
                    'supplier' => 'Initial Stock Setup',
                    'purchased_at' => date('Y-m-d')
                ]);

                StockMovement::create([
                    'inventory_id' => $item->id,
                    'purchase_batch_id' => $batch->id,
                    'type' => 'in',
                    'quantity' => $item->quantity,
                    'cost_price' => $item->cost_price,
                    'notes' => 'Initial stock setup'
                ]);
            }
        });

        return back()->with('success', 'Inventory item created successfully.');
    }

    /**
     * Update an inventory item.
     */
    public function update(Request $request, Inventory $item)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => "required|string|max:50|unique:inventory,sku,{$item->id}",
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:20',
            'low_stock_alert_qty' => 'nullable|integer|min:0',
        ]);

        $data['low_stock_alert_qty'] = $data['low_stock_alert_qty'] ?? 0;

        $item->update($data);

        // If the update was triggered from the item details page, redirect there instead of back
        if ($request->header('referer') && str_contains($request->header('referer'), "/inventory/{$item->id}")) {
            return redirect()->route('inventory.show', $item)->with('success', 'Inventory item updated successfully.');
        }

        return back()->with('success', 'Inventory item updated successfully.');
    }

    /**
     * Replenish stock by adding a new purchase batch.
     */
    public function addBatch(Request $request, Inventory $item)
    {
        $data = $request->validate([
            'batch_code' => 'required|string|max:50',
            'quantity' => 'required|integer|min:1',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'purchased_at' => 'required|date',
        ]);

        DB::transaction(function () use ($item, $data) {
            // Create purchase batch record
            $batch = \App\Models\PurchaseBatch::create([
                'inventory_id' => $item->id,
                'batch_code' => $data['batch_code'],
                'quantity_received' => $data['quantity'],
                'quantity_remaining' => $data['quantity'],
                'cost_price' => $data['cost_price'],
                'selling_price' => $data['selling_price'],
                'supplier' => $data['supplier'] ?? null,
                'purchased_at' => $data['purchased_at'],
            ]);

            // Update parent inventory quantity and latest prices
            $item->quantity += $data['quantity'];
            $item->cost_price = $data['cost_price'];
            $item->selling_price = $data['selling_price'];
            $item->save();

            // Create stock movement
            StockMovement::create([
                'inventory_id' => $item->id,
                'purchase_batch_id' => $batch->id,
                'type' => 'in',
                'quantity' => $data['quantity'],
                'cost_price' => $data['cost_price'],
                'notes' => "Replenished stock via Batch: {$data['batch_code']}"
            ]);
        });

        return back()->with('success', 'Purchase batch added successfully.');
    }

    /**
     * Adjust stock level manually.
     */
    public function adjustStock(Request $request, Inventory $item)
    {
        $data = $request->validate([
            'adjustment_type' => 'required|in:in,out,adjustment',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255',
            'purchase_batch_id' => 'nullable|exists:purchase_batches,id',
        ]);

        $qtyChange = $data['quantity'];
        if ($data['adjustment_type'] === 'out') {
            $qtyChange = -$qtyChange;
        }

        DB::transaction(function () use ($item, $data, $qtyChange) {
            if ($data['adjustment_type'] === 'in') {
                // Generic batch adjustment
                $batchCode = 'BAT-ADJ-' . time();
                $batch = \App\Models\PurchaseBatch::create([
                    'inventory_id' => $item->id,
                    'batch_code' => $batchCode,
                    'quantity_received' => $data['quantity'],
                    'quantity_remaining' => $data['quantity'],
                    'cost_price' => $item->cost_price,
                    'selling_price' => $item->selling_price,
                    'supplier' => 'Stock Adjustment Inflow',
                    'purchased_at' => date('Y-m-d')
                ]);

                $item->quantity += $data['quantity'];
                $item->save();

                StockMovement::create([
                    'inventory_id' => $item->id,
                    'purchase_batch_id' => $batch->id,
                    'type' => 'in',
                    'quantity' => $data['quantity'],
                    'cost_price' => $item->cost_price,
                    'notes' => $data['notes'] ?? 'Manual stock addition'
                ]);

            } elseif ($data['adjustment_type'] === 'out') {
                $qtyToDeduct = $data['quantity'];
                
                if (!empty($data['purchase_batch_id'])) {
                    $batches = \App\Models\PurchaseBatch::where('id', $data['purchase_batch_id'])->get();
                } else {
                    // FIFO fallback
                    $batches = \App\Models\PurchaseBatch::where('inventory_id', $item->id)
                        ->where('quantity_remaining', '>', 0)
                        ->orderBy('purchased_at', 'asc')
                        ->orderBy('id', 'asc')
                        ->get();
                }

                foreach ($batches as $batch) {
                    if ($qtyToDeduct <= 0) break;
                    
                    $deduct = min($qtyToDeduct, $batch->quantity_remaining);
                    $batch->quantity_remaining -= $deduct;
                    $batch->save();

                    StockMovement::create([
                        'inventory_id' => $item->id,
                        'purchase_batch_id' => $batch->id,
                        'type' => 'out',
                        'quantity' => -$deduct,
                        'cost_price' => $batch->cost_price,
                        'notes' => $data['notes'] ?? 'Manual stock reduction'
                    ]);

                    $qtyToDeduct -= $deduct;
                }

                $item->quantity = max(0, $item->quantity - $data['quantity']);
                $item->save();

            } elseif ($data['adjustment_type'] === 'adjustment') {
                $oldQty = $item->quantity;
                $newQty = $data['quantity'];
                $item->quantity = $newQty;
                $item->save();

                $diff = $newQty - $oldQty;

                if ($diff > 0) {
                    $batchCode = 'BAT-ADJ-' . time();
                    $batch = \App\Models\PurchaseBatch::create([
                        'inventory_id' => $item->id,
                        'batch_code' => $batchCode,
                        'quantity_received' => $diff,
                        'quantity_remaining' => $diff,
                        'cost_price' => $item->cost_price,
                        'selling_price' => $item->selling_price,
                        'supplier' => 'Stock Adjustment Override Inflow',
                        'purchased_at' => date('Y-m-d')
                    ]);

                    StockMovement::create([
                        'inventory_id' => $item->id,
                        'purchase_batch_id' => $batch->id,
                        'type' => 'adjustment',
                        'quantity' => $diff,
                        'cost_price' => $item->cost_price,
                        'notes' => $data['notes'] ?? 'Manual stock adjustment override (+)'
                    ]);
                } elseif ($diff < 0) {
                    $qtyToDeduct = abs($diff);
                    $batches = \App\Models\PurchaseBatch::where('inventory_id', $item->id)
                        ->where('quantity_remaining', '>', 0)
                        ->orderBy('purchased_at', 'asc')
                        ->orderBy('id', 'asc')
                        ->get();

                    foreach ($batches as $batch) {
                        if ($qtyToDeduct <= 0) break;
                        
                        $deduct = min($qtyToDeduct, $batch->quantity_remaining);
                        $batch->quantity_remaining -= $deduct;
                        $batch->save();

                        StockMovement::create([
                            'inventory_id' => $item->id,
                            'purchase_batch_id' => $batch->id,
                            'type' => 'adjustment',
                            'quantity' => -$deduct,
                            'cost_price' => $batch->cost_price,
                            'notes' => $data['notes'] ?? 'Manual stock adjustment override (-)'
                        ]);

                        $qtyToDeduct -= $deduct;
                    }
                }
            }
        });

        return back()->with('success', 'Stock level adjusted successfully.');
    }

    /**
     * Delete an inventory item.
     */
    public function destroy(Inventory $item)
    {
        $item->delete();
        return back()->with('success', 'Inventory item deleted.');
    }
}
