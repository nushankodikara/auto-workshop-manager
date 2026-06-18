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
     * Store a new inventory item.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:50|unique:inventory,sku',
            'quantity' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:20',
        ]);

        DB::transaction(function () use ($data) {
            $item = Inventory::create($data);

            // Log initial stock movement if quantity > 0
            if ($item->quantity > 0) {
                StockMovement::create([
                    'inventory_id' => $item->id,
                    'type' => 'in',
                    'quantity' => $item->quantity,
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
            'price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:20',
        ]);

        $item->update($data);

        return back()->with('success', 'Inventory item updated successfully.');
    }

    /**
     * Adjust stock level.
     */
    public function adjustStock(Request $request, Inventory $item)
    {
        $data = $request->validate([
            'adjustment_type' => 'required|in:in,out,adjustment',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255',
        ]);

        $qtyChange = $data['quantity'];
        if ($data['adjustment_type'] === 'out') {
            $qtyChange = -$qtyChange;
        }

        DB::transaction(function () use ($item, $data, $qtyChange) {
            // Update item quantity
            if ($data['adjustment_type'] === 'adjustment') {
                // For direct adjustment override
                $oldQty = $item->quantity;
                $item->quantity = $data['quantity'];
                $item->save();

                $diff = $data['quantity'] - $oldQty;

                StockMovement::create([
                    'inventory_id' => $item->id,
                    'type' => 'adjustment',
                    'quantity' => $diff,
                    'notes' => $data['notes'] ?? 'Manual stock adjustment override'
                ]);
            } else {
                // For adding stock (in) or subtracting (out)
                $item->quantity += $qtyChange;
                $item->save();

                StockMovement::create([
                    'inventory_id' => $item->id,
                    'type' => $data['adjustment_type'],
                    'quantity' => $qtyChange,
                    'notes' => $data['notes'] ?? 'Manual stock update'
                ]);
            }
        });

        return back()->with('success', 'Stock level updated successfully.');
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
