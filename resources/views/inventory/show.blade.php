@extends('layouts.app')

@section('title', $item->name . ' - Part Details')

@section('content')
<div class="space-y-6">

    <!-- Breadcrumbs / Back button -->
    <div>
        <a href="{{ route('inventory.index') }}" class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-slate-700 dark:text-slate-450 dark:hover:text-slate-200 transition">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            <span>Back to Parts Inventory</span>
        </a>
    </div>

    <!-- Header Summary Card -->
    <div class="app-card rounded-2xl p-6 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-xl md:text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $item->name }}</h1>
                @if($item->low_stock_alert_qty > 0 && $item->quantity <= $item->low_stock_alert_qty)
                    <span class="px-2 py-0.5 rounded-full bg-red-500/10 text-red-650 dark:text-red-400 border border-red-500/20 text-xs font-semibold">
                        Low Stock Alert
                    </span>
                @else
                    <span class="px-2 py-0.5 rounded-full bg-green-500/10 text-green-750 dark:text-green-400 border border-green-500/20 text-xs font-semibold">
                        Stock OK
                    </span>
                @endif
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                SKU: <span class="font-mono text-slate-750 dark:text-slate-200 font-semibold">{{ $item->sku }}</span> • Unit: {{ $item->unit }}
            </p>
        </div>

        <div class="grid grid-cols-3 gap-6 md:gap-8 border-t md:border-t-0 md:border-l border-slate-200 dark:border-slate-800 pt-4 md:pt-0 md:pl-8 shrink-0">
            <div>
                <span class="block text-[10px] uppercase font-bold text-slate-450 dark:text-slate-500 tracking-wider">Available Stock</span>
                <span class="text-xl font-extrabold text-slate-800 dark:text-slate-100 font-mono">{{ $item->quantity }}</span>
            </div>
            <div>
                <span class="block text-[10px] uppercase font-bold text-slate-450 dark:text-slate-500 tracking-wider">Latest Cost</span>
                <span class="text-xl font-extrabold text-slate-800 dark:text-slate-100 font-mono">{{ config('app.currency', 'Rs.') }}{{ number_format($item->cost_price, 2) }}</span>
            </div>
            <div>
                <span class="block text-[10px] uppercase font-bold text-slate-450 dark:text-slate-500 tracking-wider">Selling Price</span>
                <span class="text-xl font-extrabold text-primary font-mono">{{ config('app.currency', 'Rs.') }}{{ number_format($item->selling_price, 2) }}</span>
            </div>
        </div>
    </div>

    <!-- Main Workspace Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left 2 Cols: Price History & Batches -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Price Fluctuation History -->
            <div class="app-card rounded-2xl p-6 shadow-xs">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 mb-4 flex items-center gap-2">
                    <i data-lucide="trending-up" class="w-4 h-4 text-primary"></i>
                    <span>Price Fluctuation History</span>
                </h3>

                @if(count($priceHistory) < 2)
                    <div class="py-10 text-center text-slate-500 dark:text-slate-400 text-xs">
                        <i data-lucide="info" class="w-8 h-8 mx-auto mb-2 opacity-50"></i>
                        <p>No price changes recorded yet. Price trends appear after recording multiple batches.</p>
                    </div>
                @else
                    @php
                        $svgWidth = 600;
                        $svgHeight = 200;
                        $padding = 30;

                        $costs = $priceHistory->pluck('cost_price');
                        $sells = $priceHistory->pluck('selling_price');
                        $minVal = min($costs->min(), $sells->min()) * 0.95;
                        $maxVal = max($costs->max(), $sells->max()) * 1.05;
                        if ($maxVal == $minVal) {
                            $maxVal += 10;
                            $minVal = max(0, $minVal - 10);
                        }

                        $count = count($priceHistory);
                        $costPoints = [];
                        $sellPoints = [];

                        foreach ($priceHistory as $index => $point) {
                            $x = $padding + (($svgWidth - 2 * $padding) / ($count - 1)) * $index;
                            
                            $costY = $svgHeight - $padding - (($point->cost_price - $minVal) / ($maxVal - $minVal)) * ($svgHeight - 2 * $padding);
                            $costPoints[] = "$x,$costY";

                            $sellY = $svgHeight - $padding - (($point->selling_price - $minVal) / ($maxVal - $minVal)) * ($svgHeight - 2 * $padding);
                            $sellPoints[] = "$x,$sellY";
                        }
                        
                        $costPath = "M " . implode(" L ", $costPoints);
                        $sellPath = "M " . implode(" L ", $sellPoints);
                    @endphp
                    
                    <div class="relative">
                        <!-- Chart Legend -->
                        <div class="flex items-center gap-4 text-xs font-semibold justify-end mb-3">
                            <span class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-violet-650 dark:bg-violet-400 block"></span>
                                <span class="text-slate-650 dark:text-slate-350">Cost Price</span>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-emerald-600 dark:bg-emerald-400 block"></span>
                                <span class="text-slate-650 dark:text-slate-350">Selling Price</span>
                            </span>
                        </div>

                        <!-- Custom SVG Chart -->
                        <div class="w-full h-auto bg-slate-100/40 dark:bg-slate-900/40 rounded-xl p-2 border border-slate-200 dark:border-slate-800">
                            <svg viewBox="0 0 {{ $svgWidth }} {{ $svgHeight }}" class="w-full overflow-visible">
                                <!-- Horizontal Grid Lines -->
                                @for($i = 0; $i <= 4; $i++)
                                    @php
                                        $y = $padding + (($svgHeight - 2 * $padding) / 4) * $i;
                                        $val = $maxVal - (($maxVal - $minVal) / 4) * $i;
                                    @endphp
                                    <line x1="{{ $padding }}" y1="{{ $y }}" x2="{{ $svgWidth - $padding }}" y2="{{ $y }}" class="stroke-slate-200 dark:stroke-slate-800" stroke-width="1" stroke-dasharray="4" />
                                    <text x="{{ $padding - 5 }}" y="{{ $y + 4 }}" text-anchor="end" class="fill-slate-400 dark:fill-slate-500 font-mono text-[9px]">{{ number_format($val, 0) }}</text>
                                @endfor

                                <!-- Cost Line and Points -->
                                <path d="{{ $costPath }}" fill="none" class="stroke-violet-650 dark:stroke-violet-400" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                @foreach($costPoints as $idx => $pt)
                                    @php list($x, $y) = explode(',', $pt); @endphp
                                    <circle cx="{{ $x }}" cy="{{ $y }}" r="4.5" class="fill-white stroke-violet-650 dark:stroke-violet-400" stroke-width="2" />
                                @endforeach

                                <!-- Sell Line and Points -->
                                <path d="{{ $sellPath }}" fill="none" class="stroke-emerald-600 dark:stroke-emerald-400" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                @foreach($sellPoints as $idx => $pt)
                                    @php list($x, $y) = explode(',', $pt); @endphp
                                    <circle cx="{{ $x }}" cy="{{ $y }}" r="4.5" class="fill-white stroke-emerald-600 dark:stroke-emerald-400" stroke-width="2" />
                                @endforeach
                            </svg>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Batches List -->
            <div class="app-card rounded-2xl overflow-hidden shadow-xs">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-955/40">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                        <i data-lucide="layers" class="w-4 h-4 text-primary"></i>
                        <span>Stock Batches Log (FIFO)</span>
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-100/60 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                                <th class="py-4 px-6">Batch Code</th>
                                <th class="py-4 px-6">Date Received</th>
                                <th class="py-4 px-6">Supplier</th>
                                <th class="py-4 px-6">Cost Price</th>
                                <th class="py-4 px-6">Selling Price</th>
                                <th class="py-4 px-6 text-right">Qty Received</th>
                                <th class="py-4 px-6 text-right">Qty Remaining</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-850/60">
                            @forelse($item->purchaseBatches as $batch)
                                <tr class="hover:bg-slate-100/40 dark:hover:bg-slate-900/40 transition">
                                    <td class="py-4 px-6 font-mono font-bold text-slate-700 dark:text-slate-350">{{ $batch->batch_code }}</td>
                                    <td class="py-4 px-6 text-slate-500">{{ $batch->purchased_at->format('M d, Y') }}</td>
                                    <td class="py-4 px-6 text-slate-650 dark:text-slate-300 font-medium">{{ $batch->supplier ?? 'Initial Setup' }}</td>
                                    <td class="py-4 px-6 font-mono text-slate-500">{{ config('app.currency', 'Rs.') }}{{ number_format($batch->cost_price, 2) }}</td>
                                    <td class="py-4 px-6 font-mono font-semibold text-slate-800 dark:text-slate-100">{{ config('app.currency', 'Rs.') }}{{ number_format($batch->selling_price, 2) }}</td>
                                    <td class="py-4 px-6 text-right text-slate-600 dark:text-slate-400 font-mono">{{ $batch->quantity_received }}</td>
                                    <td class="py-4 px-6 text-right font-mono">
                                        @if($batch->quantity_remaining === 0)
                                            <span class="text-slate-400 line-through">0</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded bg-blue-500/10 text-primary font-bold border border-blue-500/20">
                                                {{ $batch->quantity_remaining }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-slate-500">
                                        No batches registered for this part.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Stock Movements Log -->
            <div class="app-card rounded-2xl overflow-hidden shadow-xs">
                <div class="p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-955/40 flex items-center justify-between">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-2">
                        <i data-lucide="activity" class="w-4 h-4 text-primary"></i>
                        <span>Stock Movement History</span>
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-100/60 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                                <th class="py-4 px-6">Timestamp</th>
                                <th class="py-4 px-6">Type</th>
                                <th class="py-4 px-6 text-right">Quantity</th>
                                <th class="py-4 px-6">Reference / Job Card</th>
                                <th class="py-4 px-6">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-850/60">
                            @forelse($item->stockMovements as $mov)
                                <tr class="hover:bg-slate-100/40 dark:hover:bg-slate-900/40 transition">
                                    <td class="py-4 px-6 text-slate-500 font-mono">{{ $mov->created_at->format('M d, Y H:i') }}</td>
                                    <td class="py-4 px-6 capitalize">
                                        @if($mov->type === 'in')
                                            <span class="px-2 py-0.5 rounded bg-green-500/10 text-green-700 dark:text-green-400 border border-green-500/20 font-bold">IN</span>
                                        @elseif($mov->type === 'out')
                                            <span class="px-2 py-0.5 rounded bg-red-500/10 text-red-650 dark:text-red-400 border border-red-500/20 font-bold">OUT</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded bg-yellow-500/10 text-yellow-650 dark:text-yellow-450 border border-yellow-500/20 font-bold">ADJ</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 text-right font-bold font-mono {{ $mov->quantity >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $mov->quantity >= 0 ? '+' : '' }}{{ $mov->quantity }}
                                    </td>
                                    <td class="py-4 px-6">
                                        @if($mov->jobCard)
                                            <a href="{{ route('job-cards.show', $mov->jobCard) }}" class="text-primary hover:underline font-semibold font-mono">
                                                {{ $mov->jobCard->card_number }} ({{ $mov->jobCard->vehicle->plate_number }})
                                            </a>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 text-slate-650 dark:text-slate-350">{{ $mov->notes ?? 'Manual update' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-slate-500">
                                        No stock movements recorded.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Right 1 Col: Complete Edit Capabilities -->
        <div class="space-y-6">
            
            <!-- Edit Part Details Card -->
            <div class="app-card rounded-2xl p-6 shadow-xs h-fit">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 mb-5 flex items-center gap-2">
                    <i data-lucide="edit-3" class="w-4 h-4 text-primary"></i>
                    <span>Edit Part Details</span>
                </h3>

                <form action="{{ route('inventory.update', $item) }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    @method('PUT')

                    <!-- Part Name -->
                    <div>
                        <label for="edit_name" class="block text-slate-500 dark:text-slate-450 mb-1.5 font-semibold">Part Name</label>
                        <input type="text" name="name" id="edit_name" required value="{{ old('name', $item->name) }}"
                               class="w-full px-3 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs">
                    </div>

                    <!-- SKU Code -->
                    <div>
                        <label for="edit_sku" class="block text-slate-500 dark:text-slate-450 mb-1.5 font-semibold">SKU Code</label>
                        <input type="text" name="sku" id="edit_sku" required value="{{ old('sku', $item->sku) }}"
                               class="w-full px-3 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary font-mono text-xs">
                    </div>

                    <!-- Measurement Unit -->
                    <div>
                        <label for="edit_unit" class="block text-slate-500 dark:text-slate-450 mb-1.5 font-semibold">Measurement Unit</label>
                        <input type="text" name="unit" id="edit_unit" required value="{{ old('unit', $item->unit) }}"
                               class="w-full px-3 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Cost Price -->
                        <div>
                            <label for="edit_cost" class="block text-slate-500 dark:text-slate-450 mb-1.5 font-semibold">Cost Price (Rs.)</label>
                            <input type="number" step="0.01" name="cost_price" id="edit_cost" required value="{{ old('cost_price', $item->cost_price) }}"
                                   class="w-full px-3 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary font-mono text-xs">
                        </div>

                        <!-- Selling Price -->
                        <div>
                            <label for="edit_selling" class="block text-slate-500 dark:text-slate-450 mb-1.5 font-semibold">Selling Price (Rs.)</label>
                            <input type="number" step="0.01" name="selling_price" id="edit_selling" required value="{{ old('selling_price', $item->selling_price) }}"
                                   class="w-full px-3 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary font-mono text-xs">
                        </div>
                    </div>

                    <!-- Low Stock Alert Quantity -->
                    <div>
                        <label for="edit_low_stock" class="block text-slate-500 dark:text-slate-450 mb-1.5 font-semibold">Low Stock Alert Qty</label>
                        <input type="number" name="low_stock_alert_qty" id="edit_low_stock" min="0" required value="{{ old('low_stock_alert_qty', $item->low_stock_alert_qty) }}"
                               class="w-full px-3 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary font-mono text-xs">
                        <span class="text-[10px] text-slate-450 dark:text-slate-500 mt-1 block">Define stock alert limit. If 0, alerts are disabled.</span>
                    </div>

                    <!-- Submit -->
                    <div class="pt-4 border-t border-slate-200 dark:border-slate-800">
                        <button type="submit" class="w-full py-2.5 px-4 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg shadow-sm transition">
                            Update Part Details
                        </button>
                    </div>
                </form>
            </div>

            <!-- Danger Zone -->
            <div class="app-card rounded-2xl p-6 shadow-xs border border-red-500/15 h-fit bg-red-500/5 dark:bg-red-500/5">
                <h3 class="text-xs font-bold uppercase tracking-wider text-red-650 dark:text-red-400 pb-2 mb-3 flex items-center gap-1.5">
                    <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                    <span>Danger Zone</span>
                </h3>
                
                <p class="text-[10px] text-slate-500 dark:text-slate-400 mb-4 leading-relaxed">
                    Deleting this part will permanently delete it from the inventory index, including all stock batch histories and stock movements logs.
                </p>

                <form action="{{ route('inventory.destroy', $item) }}" method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete this part? This action cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full py-2 px-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg text-xs transition">
                        Delete Inventory Part
                    </button>
                </form>
            </div>

        </div>

    </div>

</div>
@endsection
