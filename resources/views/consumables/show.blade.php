@extends('layouts.app')

@section('title', 'Manage ' . $consumable->name)

@section('content')
<div class="space-y-6">
    <!-- Header with Back Button -->
    <div class="flex items-center gap-3">
        <a href="{{ route('consumables.index') }}" 
           class="p-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 rounded-lg transition">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-800 dark:text-slate-200">{{ $consumable->name }}</h1>
            <p class="text-xs text-slate-500 font-mono">SKU: {{ $consumable->sku ?: 'N/A' }}</p>
        </div>
    </div>

    <!-- Quick Info Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="app-card rounded-2xl p-6 shadow-xs border border-primary/10 bg-primary/[0.01]">
            <span class="text-xs text-slate-500 block uppercase font-bold tracking-wider mb-2">Current Stock Level</span>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-bold font-mono text-slate-800 dark:text-slate-100">
                    {{ number_format($consumable->quantity, 2) }}
                </span>
                <span class="text-sm font-semibold text-slate-500">{{ $consumable->unit }}</span>
            </div>
            <p class="text-[10px] text-slate-400 mt-2 font-medium">Updated automatically on purchases & consumption logs.</p>
        </div>

        <div class="app-card rounded-2xl p-6 shadow-xs border border-slate-200 dark:border-slate-800/80">
            <span class="text-xs text-slate-500 block uppercase font-bold tracking-wider mb-2">Total Inflow Purchases</span>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-bold font-mono text-slate-800 dark:text-slate-100">
                    {{ number_format($consumable->purchases->sum('quantity'), 2) }}
                </span>
                <span class="text-sm font-semibold text-slate-500">{{ $consumable->unit }}</span>
            </div>
            <p class="text-[10px] text-slate-400 mt-2 font-medium">Cumulative quantity purchased since registration.</p>
        </div>

        <div class="app-card rounded-2xl p-6 shadow-xs border border-slate-200 dark:border-slate-800/80">
            <span class="text-xs text-slate-500 block uppercase font-bold tracking-wider mb-2">Recorded Consumptions</span>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-bold font-mono text-slate-800 dark:text-slate-100">
                    {{ number_format($consumable->usages->sum('quantity_consumed'), 2) }}
                </span>
                <span class="text-sm font-semibold text-slate-500">{{ $consumable->unit }}</span>
            </div>
            <p class="text-[10px] text-slate-400 mt-2 font-medium">Quantity logged as consumed/adjusted manually.</p>
        </div>
    </div>

    <!-- Workspace Split Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Left: Purchases / Inflows History -->
        <div class="space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                    <i data-lucide="download" class="w-4 h-4 text-emerald-500"></i>
                    <span>Purchases & Inflows</span>
                </h3>
                <button onclick="document.getElementById('add-purchase-modal').classList.remove('hidden')"
                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg transition flex items-center gap-1 cursor-pointer border-0">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Log Purchase</span>
                </button>
            </div>

            <div class="app-card rounded-2xl overflow-hidden shadow-xs border border-slate-200 dark:border-slate-800">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/50 text-slate-400 font-bold uppercase border-b border-slate-200 dark:border-slate-800">
                                <th class="py-3 px-4">Batch / Date</th>
                                <th class="py-3 px-4">Supplier</th>
                                <th class="py-3 px-4">Qty</th>
                                <th class="py-3 px-4">Cost</th>
                                <th class="py-3 px-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-850/60 font-medium">
                            @forelse($consumable->purchases as $p)
                                <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-900/30 transition">
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-slate-850 dark:text-slate-300">{{ $p->batch_code }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $p->purchased_at->format('D, d M Y') }}</div>
                                    </td>
                                    <td class="py-3 px-4 text-slate-655 dark:text-slate-400">
                                        {{ $p->supplier ?: '—' }}
                                        <div class="text-[9px] text-slate-500 font-mono uppercase mt-0.5">{{ $p->payment_method }}</div>
                                    </td>
                                    <td class="py-3 px-4 text-slate-800 dark:text-slate-300 font-mono font-semibold">
                                        +{{ number_format($p->quantity, 2) }}
                                    </td>
                                    <td class="py-3 px-4 text-slate-800 dark:text-slate-300 font-mono font-bold">
                                        {{ config('app.currency', 'Rs.') }}{{ number_format($p->cost_price, 2) }}
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <form action="{{ route('consumables.purchase.delete', $p->id) }}" method="POST"
                                              onsubmit="return confirm('Are you sure you want to delete this purchase batch? This will revert stock and delete ledger postings.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 p-1.5 hover:bg-red-500/10 rounded-lg transition border-0 bg-transparent cursor-pointer">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-400 dark:text-slate-500">
                                        No purchase records registered for this supply.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right: Usages / Consumption Logs -->
        <div class="space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-2">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                    <i data-lucide="upload" class="w-4 h-4 text-amber-500"></i>
                    <span>Consumption & Stocktakes</span>
                </h3>
                <button onclick="document.getElementById('add-usage-modal').classList.remove('hidden')"
                        class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-lg transition flex items-center gap-1 cursor-pointer border-0">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Log Usage</span>
                </button>
            </div>

            <div class="app-card rounded-2xl overflow-hidden shadow-xs border border-slate-200 dark:border-slate-800">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/50 text-slate-400 font-bold uppercase border-b border-slate-200 dark:border-slate-800">
                                <th class="py-3 px-4">Date</th>
                                <th class="py-3 px-4">Quantity Consumed</th>
                                <th class="py-3 px-4">Notes / Purpose</th>
                                <th class="py-3 px-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-850/60 font-medium">
                            @forelse($consumable->usages as $u)
                                <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-900/30 transition">
                                    <td class="py-3 px-4 font-mono text-slate-655 dark:text-slate-450">
                                        {{ $u->recorded_at->format('D, d M Y') }}
                                    </td>
                                    <td class="py-3 px-4 text-slate-800 dark:text-slate-300 font-mono font-semibold text-amber-600 dark:text-amber-500">
                                        -{{ number_format($u->quantity_consumed, 2) }}
                                    </td>
                                    <td class="py-3 px-4 text-slate-500">
                                        {{ $u->notes ?: 'Stock adjustment' }}
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <form action="{{ route('consumables.usage.delete', $u->id) }}" method="POST"
                                              onsubmit="return confirm('Delete this usage record and restore stock?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 p-1.5 hover:bg-red-500/10 rounded-lg transition border-0 bg-transparent cursor-pointer">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-slate-400 dark:text-slate-500">
                                        No consumption logs recorded. Stock level represents purchase inflows.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 1: Add Purchase -->
    <div id="add-purchase-modal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" onclick="document.getElementById('add-purchase-modal').classList.add('hidden')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-middle bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 dark:border-slate-800">
                <div class="bg-slate-50 dark:bg-slate-950/20 px-6 py-4 border-b border-slate-200 dark:border-slate-800/80 flex items-center justify-between">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Log Consumable Purchase</h3>
                    <button onclick="document.getElementById('add-purchase-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-655 dark:hover:text-slate-300">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form action="{{ route('consumables.purchase.store', $consumable->id) }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="quantity" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Quantity Purchased ({{ $consumable->unit }})</label>
                            <input type="number" step="0.01" name="quantity" id="quantity" required placeholder="e.g. 24"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                        </div>
                        <div>
                            <label for="cost_price" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Total Cost ({{ config('app.currency', 'Rs.') }})</label>
                            <input type="number" step="0.01" name="cost_price" id="cost_price" required placeholder="e.g. 18500"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                        </div>
                    </div>

                    <div>
                        <label for="supplier" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Supplier</label>
                        <input type="text" name="supplier" id="supplier" placeholder="e.g. Wurth Lanka (Pvt) Ltd"
                               class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="purchased_at" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Purchase Date</label>
                            <input type="date" name="purchased_at" id="purchased_at" required value="{{ date('Y-m-d') }}"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>
                        <div>
                            <label for="payment_method" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Payment Mode</label>
                            <select name="payment_method" id="payment_method" required
                                    class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                                <option value="cash">Cash Registry</option>
                                <option value="card">Credit Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>
                    </div>

                    <div class="border-t border-slate-200 dark:border-slate-800 pt-4 mt-6 flex justify-end gap-3">
                        <button type="button" onclick="document.getElementById('add-purchase-modal').classList.add('hidden')"
                                class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-semibold rounded-lg text-xs transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs transition shadow-sm cursor-pointer border-0">
                            Post Purchase
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal 2: Log Usage -->
    <div id="add-usage-modal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" onclick="document.getElementById('add-usage-modal').classList.add('hidden')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-middle bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 dark:border-slate-800">
                <div class="bg-slate-50 dark:bg-slate-950/20 px-6 py-4 border-b border-slate-200 dark:border-slate-800/80 flex items-center justify-between">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">Log Consumable Usage / Stocktake Adjustment</h3>
                    <button onclick="document.getElementById('add-usage-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-655 dark:hover:text-slate-300">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form action="{{ route('consumables.usage.store', $consumable->id) }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="quantity_consumed" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Quantity Consumed ({{ $consumable->unit }})</label>
                            <input type="number" step="0.01" name="quantity_consumed" id="quantity_consumed" required placeholder="e.g. 5"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                        </div>
                        <div>
                            <label for="recorded_at" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Date Recorded</label>
                            <input type="date" name="recorded_at" id="recorded_at" required value="{{ date('Y-m-d') }}"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Notes / Reason</label>
                        <input type="text" name="notes" id="notes" placeholder="e.g. Weekly shop floor stocktake correction"
                               class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                    </div>

                    <div class="border-t border-slate-200 dark:border-slate-800 pt-4 mt-6 flex justify-end gap-3">
                        <button type="button" onclick="document.getElementById('add-usage-modal').classList.add('hidden')"
                                class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-semibold rounded-lg text-xs transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2.5 bg-amber-605 hover:bg-amber-700 text-white font-bold rounded-lg text-xs transition shadow-sm cursor-pointer border-0">
                            Log Usage
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
