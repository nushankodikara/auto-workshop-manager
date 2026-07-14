@extends('layouts.app')

@section('title', 'Inventory Demand Forecast')

@section('content')
<div class="space-y-6">

    <!-- Sub-navigation Tabs -->
    <div class="flex border-b border-slate-200 dark:border-slate-800">
        <a href="{{ route('inventory.index') }}" 
           class="px-5 py-3 font-semibold text-sm border-b-2 border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-350 transition">
            Current Stock Inventory
        </a>
        <a href="{{ route('inventory.forecast') }}" 
           class="px-5 py-3 font-semibold text-sm border-b-2 border-primary text-primary transition flex items-center gap-1.5">
            <i data-lucide="trending-up" class="w-4 h-4"></i>
            <span>Demand Forecast (Next Month)</span>
        </a>
    </div>

    <!-- Interactive Filters & Configuration Card -->
    <div class="app-card rounded-2xl p-6 shadow-sm">
        <form action="{{ route('inventory.forecast') }}" method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Search -->
                <div>
                    <label for="search" class="block text-xs font-bold uppercase tracking-wider text-slate-450 dark:text-slate-400 mb-2">Search Part</label>
                    <div class="relative">
                        <input type="text" name="search" id="search" value="{{ $search }}" placeholder="Name or SKU..."
                               class="w-full pl-9 pr-4 py-2 app-input rounded-lg text-slate-905 dark:text-slate-205 focus:outline-none focus:border-primary text-sm font-semibold">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-3"></i>
                    </div>
                </div>

                <!-- Historical Window Selection -->
                <div>
                    <label for="days" class="block text-xs font-bold uppercase tracking-wider text-slate-450 dark:text-slate-400 mb-2">Data Period (Days)</label>
                    <select name="days" id="days" onchange="this.form.submit()"
                            class="w-full px-3 py-2 app-input rounded-lg text-slate-905 dark:text-slate-205 focus:outline-none focus:border-primary text-sm cursor-pointer font-semibold">
                        <option value="30" {{ $days == 30 ? 'selected' : '' }}>Past 30 Days (Default)</option>
                        <option value="60" {{ $days == 60 ? 'selected' : '' }}>Past 60 Days</option>
                        <option value="90" {{ $days == 90 ? 'selected' : '' }}>Past 90 Days</option>
                        <option value="180" {{ $days == 180 ? 'selected' : '' }}>Past 180 Days</option>
                    </select>
                </div>

                <!-- Safety Stock Factor -->
                <div>
                    <label for="safety_factor" class="block text-xs font-bold uppercase tracking-wider text-slate-450 dark:text-slate-400 mb-2">
                        Safety Stock Factor: <span id="safety-factor-val" class="font-mono text-primary font-bold">{{ number_format($safetyFactor, 1) }}x</span>
                    </label>
                    <div class="flex items-center gap-3">
                        <input type="range" name="safety_factor" id="safety_factor" min="1.0" max="2.5" step="0.1" value="{{ $safetyFactor }}"
                               oninput="document.getElementById('safety-factor-val').innerText = parseFloat(this.value).toFixed(1) + 'x'"
                               onchange="this.form.submit()"
                               class="w-full accent-primary cursor-pointer h-2 bg-slate-200 dark:bg-slate-800 rounded-lg appearance-none">
                    </div>
                </div>

                <!-- Reorder Filter -->
                <div>
                    <label for="status" class="block text-xs font-bold uppercase tracking-wider text-slate-450 dark:text-slate-400 mb-2">Reorder Filter</label>
                    <select name="status" id="status" onchange="this.form.submit()"
                            class="w-full px-3 py-2 app-input rounded-lg text-slate-905 dark:text-slate-205 focus:outline-none focus:border-primary text-sm cursor-pointer font-semibold">
                        <option value="all" {{ $status == 'all' ? 'selected' : '' }}>All Items</option>
                        <option value="reorder" {{ $status == 'reorder' ? 'selected' : '' }}>Reorder Needed Only</option>
                        <option value="sufficient" {{ $status == 'sufficient' ? 'selected' : '' }}>Stock Sufficient Only</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-2 border-t border-slate-100 dark:border-slate-800/60">
                <!-- Sort Options -->
                <div class="flex items-center gap-3">
                    <span class="text-xs text-slate-400 font-bold uppercase">Sort By:</span>
                    <select name="sort_by" onchange="this.form.submit()"
                            class="px-2 py-1 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-850 rounded-lg text-xs text-slate-600 dark:text-slate-305 focus:outline-none focus:border-primary cursor-pointer">
                        <option value="reorder_qty" {{ $sortBy == 'reorder_qty' ? 'selected' : '' }}>Recommended Order Qty</option>
                        <option value="daily_usage" {{ $sortBy == 'daily_usage' ? 'selected' : '' }}>Daily Usage Rate</option>
                        <option value="stock_level" {{ $sortBy == 'stock_level' ? 'selected' : '' }}>Current Stock Level</option>
                        <option value="name" {{ $sortBy == 'name' ? 'selected' : '' }}>Part Name</option>
                    </select>
                </div>

                <!-- Action buttons -->
                <div class="flex items-center gap-2 self-end">
                    <button type="submit" class="px-4 py-1.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-1.5">
                        <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                        <span>Apply Filters</span>
                    </button>
                    <a href="{{ route('inventory.forecast.export', request()->query()) }}" 
                       class="px-4 py-1.5 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg text-xs flex items-center gap-1.5 shadow-sm">
                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                        <span>Export CSV</span>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Summary Widgets -->
    @php
        $totalItems = $items->total();
        $reorderNeededCount = 0;
        $totalReorderCost = 0.00;
        foreach($items as $i) {
            if ($i->recommended_order > 0) {
                $reorderNeededCount++;
                $totalReorderCost += ($i->recommended_order * $i->cost_price);
            }
        }
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Widget 1: Parts Evaluated -->
        <div class="app-card rounded-2xl p-6 shadow-sm border border-slate-200/50 dark:border-slate-850 flex items-center gap-4">
            <div class="p-3.5 rounded-xl bg-primary/10 text-primary">
                <i data-lucide="package" class="w-6 h-6"></i>
            </div>
            <div>
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Parts Evaluated</div>
                <div class="text-2xl font-bold mt-0.5 text-slate-800 dark:text-slate-100">{{ $totalItems }} items</div>
            </div>
        </div>

        <!-- Widget 2: Reorder Needed -->
        <div class="app-card rounded-2xl p-6 shadow-sm border border-slate-200/50 dark:border-slate-850 flex items-center gap-4">
            <div class="p-3.5 rounded-xl {{ $reorderNeededCount > 0 ? 'bg-amber-500/10 text-amber-500' : 'bg-green-500/10 text-green-500' }}">
                <i data-lucide="{{ $reorderNeededCount > 0 ? 'alert-triangle' : 'check-circle' }}" class="w-6 h-6"></i>
            </div>
            <div>
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Reorders Required</div>
                <div class="text-2xl font-bold mt-0.5 {{ $reorderNeededCount > 0 ? 'text-amber-500' : 'text-green-500' }}">
                    {{ $reorderNeededCount }} items
                </div>
            </div>
        </div>

        <!-- Widget 3: Est Purchase Cost -->
        <div class="app-card rounded-2xl p-6 shadow-sm border border-slate-200/50 dark:border-slate-850 flex items-center gap-4">
            <div class="p-3.5 rounded-xl bg-emerald-500/10 text-emerald-500">
                <i data-lucide="coins" class="w-6 h-6"></i>
            </div>
            <div>
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Est. Replenishment Cost</div>
                <div class="text-2xl font-bold mt-0.5 text-slate-800 dark:text-slate-100 font-mono">
                    {{ config('app.currency', 'Rs.') }}{{ number_format($totalReorderCost, 2) }}
                </div>
            </div>
        </div>
    </div>

    <!-- Prediction Table Card -->
    <div class="app-card rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-100/60 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                        <th class="py-4 px-6">Part & SKU</th>
                        <th class="py-4 px-6 text-center">Current Stock</th>
                        <th class="py-4 px-6 text-center">Usage Rate</th>
                        <th class="py-4 px-6 text-center">Projected Demand</th>
                        <th class="py-4 px-6 text-center">Safety Target</th>
                        <th class="py-4 px-6 text-center">Recommended Order</th>
                        <th class="py-4 px-6 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-850/60">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-100/40 dark:hover:bg-slate-900/40 transition">
                            <!-- Part Details -->
                            <td class="py-4 px-6">
                                <div class="font-semibold text-slate-850 dark:text-slate-200 text-sm">{{ $item->name }}</div>
                                <div class="text-slate-500 font-mono text-[10px] mt-0.5">{{ $item->sku }}</div>
                            </td>

                            <!-- Current Stock -->
                            <td class="py-4 px-6 text-center font-semibold text-slate-705 dark:text-slate-300">
                                @if($item->low_stock_alert_qty > 0 && $item->quantity <= $item->low_stock_alert_qty)
                                    <span class="px-2 py-0.5 rounded bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 font-bold">
                                        {{ $item->quantity }} {{ $item->unit }}
                                    </span>
                                @else
                                    <span>{{ $item->quantity }} {{ $item->unit }}</span>
                                @endif
                                @if($item->low_stock_alert_qty > 0)
                                    <div class="text-[9px] text-slate-450 dark:text-slate-500 mt-0.5">Threshold: {{ $item->low_stock_alert_qty }}</div>
                                @endif
                            </td>

                            <!-- Usage Rate -->
                            <td class="py-4 px-6 text-center text-slate-600 dark:text-slate-400 font-mono">
                                <span class="font-medium">{{ number_format($item->historical_usage, 1) }} {{ $item->unit }}</span>
                                <div class="text-[9px] text-slate-450 dark:text-slate-500 mt-0.5 font-sans">
                                    {{ number_format($item->daily_usage, 2) }}/day ({{ $item->days_active }}d active)
                                </div>
                            </td>

                            <!-- Projected Demand (30 Days) -->
                            <td class="py-4 px-6 text-center text-slate-650 dark:text-slate-300 font-mono">
                                {{ number_format($item->predicted_demand, 1) }} {{ $item->unit }}
                            </td>

                            <!-- Safety Target Level -->
                            <td class="py-4 px-6 text-center text-slate-600 dark:text-slate-400 font-mono">
                                {{ $item->target_inventory }} {{ $item->unit }}
                            </td>

                            <!-- Recommended Order Quantity -->
                            <td class="py-4 px-6 text-center">
                                @if($item->recommended_order > 0)
                                    <div class="inline-block">
                                        <span class="px-2.5 py-1 rounded bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/25 font-bold text-sm">
                                            +{{ $item->recommended_order }} {{ $item->unit }}
                                        </span>
                                        <div class="text-[9px] text-slate-450 dark:text-slate-500 mt-1 font-mono">
                                            Est. Cost: {{ config('app.currency', 'Rs.') }}{{ number_format($item->recommended_order * $item->cost_price, 2) }}
                                        </div>
                                    </div>
                                @else
                                    <span class="px-2.5 py-1 rounded bg-green-500/10 text-green-700 dark:text-green-400 border border-green-500/25 font-semibold">
                                        Sufficient
                                    </span>
                                @endif
                            </td>

                            <!-- Quick Action Buttons -->
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('inventory.show', $item->id) }}" 
                                       class="p-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-900 dark:hover:bg-slate-800 rounded text-slate-600 dark:text-slate-300 transition" 
                                       title="View Item Ledger">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    </a>
                                    
                                    <button onclick="openAddBatchModal({{ $item->id }}, '{{ addslashes($item->name) }}', {{ $item->cost_price }}, {{ $item->selling_price }})"
                                            class="p-1.5 bg-green-500/15 hover:bg-green-600 hover:text-white rounded text-green-600 transition cursor-pointer"
                                            title="Replenish (Add Batch)">
                                        <i data-lucide="package-plus" class="w-3.5 h-3.5"></i>
                                    </button>

                                    <button onclick="openAdjustmentModal({{ $item->id }}, '{{ addslashes($item->name) }}', '{{ $item->unit }}')"
                                            class="p-1.5 bg-primary/15 hover:bg-primary hover:text-white rounded text-primary transition cursor-pointer"
                                            title="Adjust Stock">
                                        <i data-lucide="sliders" class="w-3.5 h-3.5"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500 text-sm">
                                No items found matching the selected criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div>
        {{ $items->appends(request()->query())->links() }}
    </div>

</div>

<!-- Stock Adjustment Modal -->
<div id="adjustment-modal" class="fixed inset-0 z-50 flex items-center justify-center p-6 hidden">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-slate-950/75" onclick="closeAdjustmentModal()"></div>
    
    <!-- Modal Card -->
    <div class="app-card w-full max-w-md rounded-2xl relative z-10 overflow-hidden shadow-2xl">
        <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-955/40">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-1.5" id="adjustment-modal-title">
                <i data-lucide="sliders" class="w-4 h-4 text-primary"></i>
                <span>Adjust Stock</span>
            </h2>
            <button onclick="closeAdjustmentModal()" class="text-slate-500 hover:text-slate-400 font-bold p-2">✕</button>
        </div>

        <form id="adjustment-form" method="POST" class="p-6 space-y-4 text-xs">
            @csrf
            @method('PATCH')

            <div>
                <label for="adjustment_type" class="block text-slate-500 mb-1 font-semibold">Adjustment Type</label>
                <select name="adjustment_type" id="adjustment_type" required
                        class="w-full px-3 py-2 app-input rounded-lg text-slate-905 dark:text-slate-205 focus:outline-none focus:border-primary cursor-pointer">
                    <option value="in">Add Stock (+)</option>
                    <option value="out">Remove Stock (-)</option>
                    <option value="adjustment">Direct Override (=)</option>
                </select>
            </div>

            <div>
                <label for="adj_quantity" class="block text-slate-500 mb-1 font-semibold">Quantity (<span id="adj-unit-label">pcs</span>)</label>
                <input type="number" name="quantity" id="adj_quantity" required min="1" value="1"
                       class="w-full px-3 py-2 app-input rounded-lg text-slate-905 dark:text-slate-205 focus:outline-none focus:border-primary">
            </div>

            <div>
                <label for="adj_notes" class="block text-slate-500 mb-1 font-semibold">Transaction Notes</label>
                <textarea name="notes" id="adj_notes" rows="2" placeholder="Manual stock delivery or inventory count check..."
                          class="w-full px-3 py-2 app-input rounded-lg text-slate-905 dark:text-slate-205 focus:outline-none focus:border-primary"></textarea>
            </div>

            <!-- Buttons -->
            <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex gap-2 justify-end">
                <button type="button" onclick="closeAdjustmentModal()"
                        class="py-2 px-3 bg-slate-200 dark:bg-slate-850 hover:bg-slate-350 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold rounded-lg">
                    Cancel
                </button>
                <button type="submit" class="py-2 px-4 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg">
                    Record Adjustment
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Replenish Stock / Add Purchase Batch Modal -->
<div id="add-batch-modal" class="fixed inset-0 z-50 flex items-center justify-center p-6 hidden">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-slate-950/75" onclick="closeAddBatchModal()"></div>
    
    <!-- Modal Card -->
    <div class="app-card w-full max-w-md rounded-2xl relative z-10 overflow-hidden shadow-2xl">
        <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-955/40">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-1.5" id="add-batch-modal-title">
                <i data-lucide="package-plus" class="w-4 h-4 text-primary"></i>
                <span>Replenish Stock (Add Batch)</span>
            </h2>
            <button onclick="closeAddBatchModal()" class="text-slate-500 hover:text-slate-400 font-bold p-2">✕</button>
        </div>

        <form id="add-batch-form" method="POST" class="p-6 space-y-4 text-xs">
            @csrf

            <div>
                <label for="batch_code" class="block text-slate-500 mb-1 font-semibold">Batch Code / ID</label>
                <input type="text" name="batch_code" id="batch_code" required placeholder="e.g. BAT-20260619-A"
                       class="w-full px-3 py-2 app-input rounded-lg text-slate-905 dark:text-slate-205 focus:outline-none focus:border-primary font-mono">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="batch_quantity" class="block text-slate-500 mb-1 font-semibold">Quantity Received</label>
                    <input type="number" name="quantity" id="batch_quantity" required min="1" value="1"
                           class="w-full px-3 py-2 app-input rounded-lg text-slate-905 dark:text-slate-205 focus:outline-none focus:border-primary font-mono">
                </div>
                <div>
                    <label for="batch_purchased_at" class="block text-slate-500 mb-1 font-semibold">Purchase Date</label>
                    <input type="date" name="purchased_at" id="batch_purchased_at" required value="{{ date('Y-m-d') }}"
                           class="w-full px-3 py-2 app-input rounded-lg text-slate-905 dark:text-slate-205 focus:outline-none focus:border-primary">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="batch_cost_price" class="block text-slate-500 mb-1 font-semibold">Cost Price (Rs.)</label>
                    <input type="number" step="0.01" name="cost_price" id="batch_cost_price" required value="0.00"
                           class="w-full px-3 py-2 app-input rounded-lg text-slate-905 dark:text-slate-205 focus:outline-none focus:border-primary font-mono">
                </div>
                <div>
                    <label for="batch_selling_price" class="block text-slate-500 mb-1 font-semibold">Selling Price (Rs.)</label>
                    <input type="number" step="0.01" name="selling_price" id="batch_selling_price" required value="0.00"
                           class="w-full px-3 py-2 app-input rounded-lg text-slate-905 dark:text-slate-205 focus:outline-none focus:border-primary font-mono">
                </div>
            </div>

            <div>
                <label for="batch_supplier" class="block text-slate-500 mb-1 font-semibold">Supplier Name</label>
                <input type="text" name="supplier" id="batch_supplier" placeholder="e.g. Lanka Parts Wholesale"
                       class="w-full px-3 py-2 app-input rounded-lg text-slate-905 dark:text-slate-205 focus:outline-none focus:border-primary">
            </div>

            <!-- Buttons -->
            <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex gap-2 justify-end">
                <button type="button" onclick="closeAddBatchModal()"
                        class="py-2 px-3 bg-slate-200 dark:bg-slate-850 hover:bg-slate-350 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold rounded-lg">
                    Cancel
                </button>
                <button type="submit" class="py-2 px-4 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg">
                    Add Batch
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAdjustmentModal(itemId, itemName, unit) {
        const modal = document.getElementById('adjustment-modal');
        const form = document.getElementById('adjustment-form');
        const title = document.getElementById('adjustment-modal-title');
        const unitLabel = document.getElementById('adj-unit-label');

        form.action = `/inventory/${itemId}/adjust`;
        title.innerText = `Adjust Stock - ${itemName}`;
        unitLabel.innerText = unit;

        modal.classList.remove('hidden');
    }

    function closeAdjustmentModal() {
        document.getElementById('adjustment-modal').classList.add('hidden');
    }

    function openAddBatchModal(itemId, itemName, currentCost, currentSelling) {
        const modal = document.getElementById('add-batch-modal');
        const form = document.getElementById('add-batch-form');
        const title = document.getElementById('add-batch-modal-title');
        
        form.action = `/inventory/${itemId}/batch`;
        title.innerText = `Add Purchase Batch - ${itemName}`;
        
        // Auto-fill a suggested batch code
        const dateStr = new Date().toISOString().slice(0,10).replace(/-/g,"");
        document.getElementById('batch_code').value = `BAT-${dateStr}-${itemId}`;
        
        // Pre-fill latest prices
        document.getElementById('batch_cost_price').value = currentCost;
        document.getElementById('batch_selling_price').value = currentSelling;

        modal.classList.remove('hidden');
    }

    function closeAddBatchModal() {
        document.getElementById('add-batch-modal').classList.add('hidden');
    }
</script>
@endsection
