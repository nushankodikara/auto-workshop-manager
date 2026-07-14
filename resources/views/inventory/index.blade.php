@extends('layouts.app')

@section('title', 'Parts Inventory')

@section('content')
<div class="space-y-6">

    <!-- Sub-navigation Tabs -->
    <div class="flex border-b border-slate-200 dark:border-slate-800">
        <a href="{{ route('inventory.index') }}" 
           class="px-5 py-3 font-semibold text-sm border-b-2 border-primary text-primary transition">
            Current Stock Inventory
        </a>
        <a href="{{ route('inventory.forecast') }}" 
           class="px-5 py-3 font-semibold text-sm border-b-2 border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-350 transition flex items-center gap-1.5">
            <i data-lucide="trending-up" class="w-4 h-4"></i>
            <span>Demand Forecast (Next Month)</span>
        </a>
    </div>

    <!-- Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-end gap-4">
        <button onclick="document.getElementById('create-part-drawer').classList.remove('hidden')"
                class="px-4 py-2 bg-primary hover:bg-primary-hover text-white font-medium rounded-lg text-sm transition flex items-center gap-1.5 shadow-sm cursor-pointer ml-auto sm:ml-0">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>Add New Part</span>
        </button>
    </div>

    <!-- Inventory Workspace Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left: Inventory List Table -->
        <div class="lg:col-span-2 space-y-6">
            <div class="app-card rounded-2xl overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="datatable w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-100/60 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                            <th class="py-4 px-6">Part Name</th>
                            <th class="py-4 px-6">SKU</th>
                            <th class="py-4 px-6">Available Stock</th>
                            <th class="py-4 px-6">Cost Price</th>
                            <th class="py-4 px-6">Selling Price</th>
                            <th class="py-4 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-850/60">
                        @forelse($items as $item)
                            <tr class="hover:bg-slate-100/40 dark:hover:bg-slate-900/40 transition">
                                <td class="py-4 px-6 font-semibold text-slate-850 dark:text-slate-200">
                                    {{ $item->name }}
                                </td>
                                <td class="py-4 px-6 text-slate-500 font-mono text-xs">{{ $item->sku }}</td>
                                <td class="py-4 px-6">
                                    @if($item->low_stock_alert_qty > 0 && $item->quantity <= $item->low_stock_alert_qty)
                                        <span class="px-2 py-0.5 rounded bg-red-500/10 text-red-650 dark:text-red-400 border border-red-500/20 text-xs font-semibold">
                                            {{ $item->quantity }} {{ $item->unit }} (Low)
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-green-500/10 text-green-700 dark:text-green-400 border border-green-500/20 text-xs font-semibold">
                                            {{ $item->quantity }} {{ $item->unit }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-slate-500 dark:text-slate-400 font-mono">
                                    {{ config('app.currency', 'Rs.') }}{{ number_format($item->cost_price, 2) }}
                                </td>
                                <td class="py-4 px-6 text-slate-700 dark:text-slate-200 font-semibold font-mono">
                                    {{ config('app.currency', 'Rs.') }}{{ number_format($item->selling_price, 2) }}
                                </td>
                                <td class="py-4 px-6 text-right flex items-center justify-end gap-2">
                                    <!-- View Details link -->
                                    <a href="{{ route('inventory.show', $item) }}"
                                       class="text-xs font-bold text-slate-650 dark:text-slate-350 bg-slate-500/10 border border-slate-500/20 px-2.5 py-1 rounded transition hover:bg-slate-600 hover:text-white">
                                        View Details
                                    </a>
                                    <!-- Add Batch trigger -->
                                    <button onclick="openAddBatchModal({{ $item->id }}, '{{ addslashes($item->name) }}', {{ $item->cost_price }}, {{ $item->selling_price }})"
                                            class="text-xs font-bold text-green-600 bg-green-500/10 border border-green-500/20 px-2.5 py-1 rounded transition hover:bg-green-600 hover:text-white cursor-pointer">
                                        Add Batch
                                    </button>
                                    <!-- Adjust Stock trigger -->
                                    <button onclick="openAdjustmentModal({{ $item->id }}, '{{ addslashes($item->name) }}', '{{ $item->unit }}')"
                                            class="text-xs font-bold text-primary bg-primary/10 border border-primary/20 px-2.5 py-1 rounded transition hover:bg-primary hover:text-white cursor-pointer">
                                        Adjust Stock
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-500">
                                    No parts registered in inventory.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <!-- Right: Recent Stock Movements Logs -->
        <div class="app-card rounded-2xl p-6 shadow-xs h-fit">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 mb-4 flex items-center gap-2">
                <i data-lucide="activity" class="w-4 h-4 text-primary"></i>
                <span>Stock Transaction Logs</span>
            </h3>

            <div class="space-y-4 max-h-[500px] overflow-y-auto pr-2">
                @forelse($recentMovements as $mov)
                    <div class="p-3 bg-slate-50 dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-800 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-slate-800 dark:text-slate-200 capitalize">{{ $mov->inventory->name }}</span>
                            <span class="font-bold {{ $mov->quantity >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $mov->quantity >= 0 ? '+' : '' }}{{ $mov->quantity }} {{ $mov->inventory->unit }}
                            </span>
                        </div>
                        <div class="text-[10px] text-slate-500 mt-1 capitalize leading-relaxed">
                            Type: {{ $mov->type }} • {{ $mov->notes ?? 'Manual update' }}
                        </div>
                        <div class="text-[9px] text-slate-450 mt-2 text-right">
                            {{ $mov->created_at->diffForHumans() }}
                        </div>
                    </div>
                @empty
                    <div class="text-slate-500 text-xs text-center py-4">
                        No transactions recorded.
                    </div>
                @endforelse
            </div>
        </div>

    </div>

</div>

<!-- Sidebar Drawer Modal: Add Part -->
<div id="create-part-drawer" class="fixed inset-0 z-50 overflow-hidden hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute inset-0 bg-slate-950/75 transition-opacity" onclick="document.getElementById('create-part-drawer').classList.add('hidden')"></div>

        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div class="pointer-events-auto w-screen max-w-md">
                <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-xl">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-955/40">
                        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <i data-lucide="package-plus" class="w-5 h-5 text-primary"></i>
                            <span>Add Inventory Part</span>
                        </h2>
                        <button onclick="document.getElementById('create-part-drawer').classList.add('hidden')" class="text-slate-500 hover:text-slate-400 font-bold p-2">✕</button>
                    </div>

                    <form action="{{ route('inventory.store') }}" method="POST" class="flex-1 p-6 space-y-5">
                        @csrf

                        <!-- Part Name -->
                        <div>
                            <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Part Name</label>
                            <input type="text" name="name" id="name" required placeholder="Engine Oil 5W-30"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <!-- SKU -->
                        <div>
                            <label for="sku" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">SKU Code</label>
                            <input type="text" name="sku" id="sku" required placeholder="OIL-5W30-1L"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <!-- Initial Quantity -->
                        <div>
                            <label for="quantity" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Initial Quantity</label>
                            <input type="number" name="quantity" id="quantity" required min="0" value="0"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <!-- Cost Price -->
                        <div>
                            <label for="cost_price" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Cost Price ({{ config('app.currency', 'Rs.') }})</label>
                            <input type="number" step="0.01" name="cost_price" id="cost_price" required value="0.00"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                        </div>

                        <!-- Selling Price -->
                        <div>
                            <label for="selling_price" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Selling Price ({{ config('app.currency', 'Rs.') }})</label>
                            <input type="number" step="0.01" name="selling_price" id="selling_price" required value="0.00"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                        </div>

                        <!-- Unit -->
                        <div>
                            <label for="unit" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Measurement Unit</label>
                            <input type="text" name="unit" id="unit" required placeholder="pcs, liters, meters" value="pcs"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <!-- Low Stock Alert Quantity -->
                        <div>
                            <label for="low_stock_alert_qty" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Low Stock Alert Qty</label>
                            <input type="number" name="low_stock_alert_qty" id="low_stock_alert_qty" min="0" value="0" required
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                            <span class="text-[10px] text-slate-450 dark:text-slate-500 mt-1 block">Define stock alert limit. If 0, alerts are disabled.</span>
                        </div>

                        <!-- Buttons -->
                        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex gap-3">
                            <button type="submit"
                                    class="flex-1 py-2.5 px-4 bg-primary hover:bg-primary-hover text-white font-medium rounded-lg transition text-sm">
                                Register Part
                            </button>
                            <button type="button" onclick="document.getElementById('create-part-drawer').classList.add('hidden')"
                                    class="py-2.5 px-4 bg-slate-200 dark:bg-slate-850 hover:bg-slate-350 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium rounded-lg transition text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Simple Stock Adjustment Modal -->
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
                        class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary cursor-pointer">
                    <option value="in">Add Stock (+)</option>
                    <option value="out">Remove Stock (-)</option>
                    <option value="adjustment">Direct Override (=)</option>
                </select>
            </div>

            <div>
                <label for="adj_quantity" class="block text-slate-500 mb-1 font-semibold">Quantity (<span id="adj-unit-label">pcs</span>)</label>
                <input type="number" name="quantity" id="adj_quantity" required min="1" value="1"
                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary">
            </div>

            <div>
                <label for="adj_notes" class="block text-slate-500 mb-1 font-semibold">Transaction Notes</label>
                <textarea name="notes" id="adj_notes" rows="2" placeholder="Manual stock delivery or inventory count check..."
                          class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary"></textarea>
            </div>

            <!-- Buttons -->
            <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex gap-2 justify-end">
                <button type="button" onclick="closeAdjustmentModal()"
                        class="py-2 px-3 bg-slate-200 dark:bg-slate-850 hover:bg-slate-300 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold rounded-lg">
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
                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary font-mono">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="batch_quantity" class="block text-slate-500 mb-1 font-semibold">Quantity Received</label>
                    <input type="number" name="quantity" id="batch_quantity" required min="1" value="1"
                           class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary font-mono">
                </div>
                <div>
                    <label for="batch_purchased_at" class="block text-slate-500 mb-1 font-semibold">Purchase Date</label>
                    <input type="date" name="purchased_at" id="batch_purchased_at" required value="{{ date('Y-m-d') }}"
                           class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="batch_cost_price" class="block text-slate-500 mb-1 font-semibold">Cost Price (Rs.)</label>
                    <input type="number" step="0.01" name="cost_price" id="batch_cost_price" required value="0.00"
                           class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary font-mono">
                </div>
                <div>
                    <label for="batch_selling_price" class="block text-slate-500 mb-1 font-semibold">Selling Price (Rs.)</label>
                    <input type="number" step="0.01" name="selling_price" id="batch_selling_price" required value="0.00"
                           class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary font-mono">
                </div>
            </div>

            <div>
                <label for="batch_supplier" class="block text-slate-500 mb-1 font-semibold">Supplier Name</label>
                <input type="text" name="supplier" id="batch_supplier" placeholder="e.g. Lanka Parts Wholesale"
                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary">
            </div>

            <!-- Buttons -->
            <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex gap-2 justify-end">
                <button type="button" onclick="closeAddBatchModal()"
                        class="py-2 px-3 bg-slate-200 dark:bg-slate-850 hover:bg-slate-300 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold rounded-lg">
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
