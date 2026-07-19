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
        $items = Inventory::latest()->get();

        // Fetch recent stock movements
        $recentMovements = StockMovement::with(['inventory', 'jobCard.vehicle'])
            ->latest()
            ->limit(10)
            ->get();

        return view('inventory.index', compact('items', 'recentMovements'));
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

                \App\Services\DoubleEntryService::postPurchaseBatchTransaction($batch);

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

            \App\Services\DoubleEntryService::postPurchaseBatchTransaction($batch);

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

                \App\Services\DoubleEntryService::postPurchaseBatchTransaction($batch);

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

                    \App\Services\DoubleEntryService::postPurchaseBatchTransaction($batch);

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

    /**
     * Check if authenticated user has access to the inventory module.
     */
    private function checkAccess()
    {
        if (!auth()->user() || !auth()->user()->hasModuleAccess('inventory')) {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * Display inventory demand forecast and order recommendations.
     */
    public function forecast(Request $request)
    {
        $this->checkAccess();

        $days = (int) $request->input('days', 30);
        $safetyFactor = (float) $request->input('safety_factor', 1.0);
        $search = $request->input('search');
        $status = $request->input('status', 'all');
        $sortBy = $request->input('sort_by', 'reorder_qty');

        $query = Inventory::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $allItems = $query->get();
        $forecastData = [];

        foreach ($allItems as $item) {
            $usage = abs(StockMovement::where('inventory_id', $item->id)
                ->where('quantity', '<', 0)
                ->where('created_at', '>=', now()->subDays($days))
                ->sum('quantity'));

            $daysActive = max(1, min($days, max(1, now()->diffInDays($item->created_at))));
            
            // Forecast using time series Holt's Exponential Smoothing method
            $predictedDemand = \App\Services\ForecastingService::forecast30DaysDemand('stock_movements', 'inventory_id', $item->id);
            $dailyUsage = $predictedDemand / 30;
            $targetInventory = ceil($predictedDemand * $safetyFactor);
            
            $requiredLevel = max($targetInventory, (int)$item->low_stock_alert_qty);
            $recommendedOrder = max(0, $requiredLevel - $item->quantity);

            $forecastData[] = (object) [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'cost_price' => $item->cost_price,
                'selling_price' => $item->selling_price,
                'low_stock_alert_qty' => $item->low_stock_alert_qty,
                'days_active' => $daysActive,
                'historical_usage' => $usage,
                'daily_usage' => $dailyUsage,
                'predicted_demand' => $predictedDemand,
                'target_inventory' => $targetInventory,
                'recommended_order' => $recommendedOrder,
                'forecast_status' => $recommendedOrder > 0 ? 'reorder' : 'sufficient'
            ];
        }

        $collection = collect($forecastData);

        // Filter by Status
        if ($status === 'reorder') {
            $collection = $collection->where('recommended_order', '>', 0);
        } elseif ($status === 'sufficient') {
            $collection = $collection->where('recommended_order', '==', 0);
        }

        // Sort by
        if ($sortBy === 'daily_usage') {
            $collection = $collection->sortByDesc('daily_usage');
        } elseif ($sortBy === 'stock_level') {
            $collection = $collection->sortBy('quantity');
        } elseif ($sortBy === 'name') {
            $collection = $collection->sortBy('name');
        } else { // default 'reorder_qty'
            $collection = $collection->sortByDesc('recommended_order');
        }

        // Paginate
        $perPage = 15;
        $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $currentPageItems = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $items = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentPageItems,
            $collection->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return view('inventory.forecast', compact('items', 'days', 'safetyFactor', 'search', 'status', 'sortBy'));
    }

    /**
     * Export demand forecast recommendations as a CSV.
     */
    public function exportForecastCsv(Request $request)
    {
        $this->checkAccess();

        $days = (int) $request->input('days', 30);
        $safetyFactor = (float) $request->input('safety_factor', 1.0);
        $search = $request->input('search');
        $status = $request->input('status', 'all');

        $query = Inventory::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('name')->get();
        $fileName = 'inventory_forecast_' . date('Ymd_His') . '.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($items, $days, $safetyFactor, $status) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'SKU', 
                'Part Name', 
                'Current Stock', 
                'Unit', 
                'Low Stock Threshold', 
                'Historical Usage (' . $days . ' days)', 
                'Avg Daily Usage', 
                'Predicted Demand (30 days)', 
                'Target Stock Level', 
                'Recommended Order Qty'
            ]);

            foreach ($items as $item) {
                $usage = abs(StockMovement::where('inventory_id', $item->id)
                    ->where('quantity', '<', 0)
                    ->where('created_at', '>=', now()->subDays($days))
                    ->sum('quantity'));

                $daysActive = max(1, min($days, max(1, now()->diffInDays($item->created_at))));
                
                // Forecast using time series Holt's Exponential Smoothing method
                $predictedDemand = \App\Services\ForecastingService::forecast30DaysDemand('stock_movements', 'inventory_id', $item->id);
                $dailyUsage = $predictedDemand / 30;
                $targetInventory = ceil($predictedDemand * $safetyFactor);
                
                $requiredLevel = max($targetInventory, (int)$item->low_stock_alert_qty);
                $recommendedOrder = max(0, $requiredLevel - $item->quantity);

                // Apply status filter if not 'all'
                if ($status === 'reorder' && $recommendedOrder <= 0) {
                    continue;
                }
                if ($status === 'sufficient' && $recommendedOrder > 0) {
                    continue;
                }

                fputcsv($file, [
                    $item->sku,
                    $item->name,
                    $item->quantity,
                    $item->unit,
                    $item->low_stock_alert_qty,
                    $usage,
                    round($dailyUsage, 2),
                    round($predictedDemand, 2),
                    $targetInventory,
                    $recommendedOrder
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
