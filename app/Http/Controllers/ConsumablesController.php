<?php

namespace App\Http\Controllers;

use App\Models\Consumable;
use App\Models\ConsumablePurchase;
use App\Models\ConsumableUsage;
use App\Models\Activity;
use App\Services\DoubleEntryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConsumablesController extends Controller
{
    private function checkAccess()
    {
        if (!auth()->user() || !auth()->user()->hasModuleAccess('inventory')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index(Request $request)
    {
        $this->checkAccess();
        
        $search = $request->input('search');
        $query = Consumable::query();
        
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }
        
        $consumables = $query->orderBy('name')->get();
        
        return view('consumables.index', compact('consumables', 'search'));
    }

    public function store(Request $request)
    {
        $this->checkAccess();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255|unique:consumables,sku',
            'unit' => 'required|string|max:50',
            'description' => 'nullable|string|max:1000',
        ]);

        $consumable = Consumable::create($data);

        return redirect()->route('consumables.show', $consumable->id)->with('success', 'Consumable supply registered successfully.');
    }

    public function show(Consumable $consumable)
    {
        $this->checkAccess();
        
        $consumable->load(['purchases', 'usages']);
        
        return view('consumables.show', compact('consumable'));
    }

    public function storePurchase(Request $request, Consumable $consumable)
    {
        $this->checkAccess();

        $data = $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'cost_price' => 'required|numeric|min:0.01',
            'supplier' => 'nullable|string|max:255',
            'purchased_at' => 'required|date',
            'payment_method' => 'required|in:cash,card,bank_transfer',
        ]);

        DB::transaction(function () use ($consumable, $data) {
            $batchCode = 'CONS-' . date('Ymd', strtotime($data['purchased_at'])) . '-' . str_pad($consumable->id, 4, '0', STR_PAD_LEFT);

            $purchase = ConsumablePurchase::create([
                'consumable_id' => $consumable->id,
                'batch_code' => $batchCode,
                'quantity' => $data['quantity'],
                'cost_price' => $data['cost_price'],
                'supplier' => $data['supplier'],
                'purchased_at' => $data['purchased_at'],
                'payment_method' => $data['payment_method'],
            ]);

            // Adjust consumable quantity
            $consumable->increment('quantity', $data['quantity']);

            // Sync to double entry bookkeeping system
            DoubleEntryService::postConsumablePurchase($purchase);

            // Log activity
            Activity::create([
                'user_id' => Auth::id(),
                'action' => 'consumable_purchased',
                'details' => "Purchased {$data['quantity']} {$consumable->unit} of '{$consumable->name}' for " . config('app.currency', 'Rs.') . number_format($data['cost_price'], 2)
            ]);
        });

        return back()->with('success', 'Consumable purchase recorded and posted to double-entry ledger.');
    }

    public function deletePurchase(ConsumablePurchase $purchase)
    {
        $this->checkAccess();
        $consumable = $purchase->consumable;

        DB::transaction(function () use ($consumable, $purchase) {
            // Adjust quantity down
            $consumable->decrement('quantity', $purchase->quantity);

            // Log activity before deleting
            Activity::create([
                'user_id' => Auth::id(),
                'action' => 'consumable_purchase_deleted',
                'details' => "Deleted purchase batch {$purchase->batch_code} for '{$consumable->name}'"
            ]);

            // Delete purchase (boot event deletes journal entry)
            $purchase->delete();
        });

        return back()->with('success', 'Consumable purchase deleted and ledger transaction voided.');
    }

    public function storeUsage(Request $request, Consumable $consumable)
    {
        $this->checkAccess();

        $data = $request->validate([
            'quantity_consumed' => 'required|numeric|min:0.01',
            'recorded_at' => 'required|date',
            'notes' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($consumable, $data) {
            ConsumableUsage::create([
                'consumable_id' => $consumable->id,
                'quantity_consumed' => $data['quantity_consumed'],
                'recorded_at' => $data['recorded_at'],
                'notes' => $data['notes'],
            ]);

            // Decrement consumable quantity
            $consumable->decrement('quantity', $data['quantity_consumed']);

            // Log activity
            Activity::create([
                'user_id' => Auth::id(),
                'action' => 'consumable_usage_logged',
                'details' => "Logged consumption of {$data['quantity_consumed']} {$consumable->unit} from '{$consumable->name}'"
            ]);
        });

        return back()->with('success', 'Consumption / stocktake adjustment recorded.');
    }

    public function deleteUsage(ConsumableUsage $usage)
    {
        $this->checkAccess();
        $consumable = $usage->consumable;

        DB::transaction(function () use ($consumable, $usage) {
            // Restore quantity
            $consumable->increment('quantity', $usage->quantity_consumed);

            // Log activity
            Activity::create([
                'user_id' => Auth::id(),
                'action' => 'consumable_usage_deleted',
                'details' => "Deleted consumption record of {$usage->quantity_consumed} {$consumable->unit} for '{$consumable->name}'"
            ]);

            $usage->delete();
        });

        return back()->with('success', 'Consumption log deleted and stock restored.');
    }

    public function forecast(Request $request)
    {
        $this->checkAccess();

        $days = (int) $request->input('days', 30);
        $safetyFactor = (float) $request->input('safety_factor', 1.0);
        $search = $request->input('search');

        $query = Consumable::query();

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
        }

        $allConsumables = $query->orderBy('name')->get();
        $forecastData = [];

        foreach ($allConsumables as $item) {
            // Calculate usage: try usages sum first, fallback to purchases sum
            $usage = (double) $item->usages()->where('recorded_at', '>=', now()->subDays($days))->sum('quantity_consumed');
            $usingFallback = false;
            
            if ($usage <= 0) {
                $usage = (double) $item->purchases()->where('purchased_at', '>=', now()->subDays($days))->sum('quantity');
                $usingFallback = true;
            }

            $daysActive = max(1, min($days, max(1, now()->diffInDays($item->created_at))));
            $dailyUsage = $usage / $daysActive;
            $predictedDemand = $dailyUsage * 30;
            $targetInventory = ceil($predictedDemand * $safetyFactor);
            
            $recommendedOrder = max(0.00, $targetInventory - $item->quantity);

            // Monthly purchase totals this month for dashboard display
            $purchasesThisMonth = (double) $item->purchases()
                ->where('purchased_at', '>=', now()->startOfMonth())
                ->sum('quantity');

            $costThisMonth = (double) $item->purchases()
                ->where('purchased_at', '>=', now()->startOfMonth())
                ->sum('cost_price');

            $forecastData[] = (object) [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'days_active' => $daysActive,
                'historical_usage' => $usage,
                'using_fallback' => $usingFallback,
                'daily_usage' => $dailyUsage,
                'predicted_demand' => $predictedDemand,
                'target_inventory' => $targetInventory,
                'recommended_order' => $recommendedOrder,
                'purchases_this_month' => $purchasesThisMonth,
                'cost_this_month' => $costThisMonth,
                'forecast_status' => $recommendedOrder > 0 ? 'reorder' : 'sufficient'
            ];
        }

        $items = collect($forecastData);

        return view('consumables.forecast', compact('items', 'days', 'safetyFactor', 'search'));
    }
}
