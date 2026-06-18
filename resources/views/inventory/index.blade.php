@extends('layouts.app')

@section('title', 'Parts Inventory')

@section('content')
<div class="space-y-6">

    <!-- Header Actions and Search -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Search bar -->
        <form action="{{ route('inventory.index') }}" method="GET" class="w-full md:max-w-md flex gap-2">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search by part name or SKU..."
                   class="flex-1 px-4 py-2 bg-slate-900 border border-slate-800 rounded-lg text-slate-200 placeholder-slate-650 focus:outline-none focus:border-indigo-500 text-sm">
            <button type="submit" class="px-4 py-2 bg-slate-850 hover:bg-slate-800 border border-slate-750 rounded-lg text-xs font-semibold">
                Search
            </button>
            @if($search)
                <a href="{{ route('inventory.index') }}" class="px-3 py-2 bg-slate-900 border border-slate-800 rounded-lg text-xs font-semibold text-slate-500 hover:text-slate-350 flex items-center">
                    Reset
                </a>
            @endif
        </form>

        <button onclick="document.getElementById('create-part-drawer').classList.remove('hidden')"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-lg text-sm transition">
            + Add New Part
        </button>
    </div>

    <!-- Inventory Workspace Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left: Inventory List Table -->
        <div class="lg:col-span-2 space-y-6">
            <div class="glass-card rounded-2xl overflow-hidden border border-slate-900">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-900/60 border-b border-slate-850/80 text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                            <th class="py-4 px-6">Part Name</th>
                            <th class="py-4 px-6">SKU</th>
                            <th class="py-4 px-6">Available Stock</th>
                            <th class="py-4 px-6">Price</th>
                            <th class="py-4 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-850/60">
                        @forelse($items as $item)
                            <tr class="hover:bg-slate-900/40 transition">
                                <td class="py-4 px-6 font-semibold text-slate-200">
                                    {{ $item->name }}
                                </td>
                                <td class="py-4 px-6 text-slate-450 font-mono text-xs">{{ $item->sku }}</td>
                                <td class="py-4 px-6">
                                    @if($item->quantity < 10)
                                        <span class="px-2 py-0.5 rounded bg-red-500/10 text-red-400 border border-red-500/20 text-xs font-semibold">
                                            {{ $item->quantity }} {{ $item->unit }} (Low)
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-xs font-semibold">
                                            {{ $item->quantity }} {{ $item->unit }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-slate-300 font-medium">${{ number_format($item->price, 2) }}</td>
                                <td class="py-4 px-6 text-right space-x-2">
                                    <!-- Adjust Stock trigger -->
                                    <button onclick="openAdjustmentModal({{ $item->id }}, '{{ $item->name }}', '{{ $item->unit }}')"
                                            class="text-xs font-bold text-indigo-400 hover:text-indigo-300 bg-indigo-500/10 border border-indigo-500/20 px-2 py-1 rounded transition">
                                        Adjust Stock
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-slate-500">
                                    No parts registered in inventory.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div>
                {{ $items->appends(['search' => $search])->links() }}
            </div>
        </div>

        <!-- Right: Recent Stock Movements Logs -->
        <div class="glass-card rounded-2xl p-6">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800/80 pb-3 mb-4">
                📋 Stock Transaction Logs
            </h3>

            <div class="space-y-4 max-h-[500px] overflow-y-auto pr-2">
                @forelse($recentMovements as $mov)
                    <div class="p-3 bg-slate-900/40 rounded-xl border border-slate-850/80 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-slate-250">{{ $mov->inventory->name }}</span>
                            <span class="font-bold {{ $mov->quantity >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                {{ $mov->quantity >= 0 ? '+' : '' }}{{ $mov->quantity }} {{ $mov->inventory->unit }}
                            </span>
                        </div>
                        <div class="text-[10px] text-slate-500 mt-1 capitalize">
                            Type: {{ $mov->type }} • {{ $mov->notes ?? 'Manual update' }}
                        </div>
                        <div class="text-[9px] text-slate-600 mt-2 text-right">
                            {{ $mov->created_at->diffForHumans() }}
                        </div>
                    </div>
                @empty
                    <div class="text-slate-650 text-xs text-center py-4">
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
                <div class="flex h-full flex-col overflow-y-scroll bg-slate-900 border-l border-slate-800 shadow-xl">
                    <div class="p-6 border-b border-slate-800 flex items-center justify-between bg-slate-950">
                        <h2 class="text-lg font-bold text-slate-200">Add Inventory Part</h2>
                        <button onclick="document.getElementById('create-part-drawer').classList.add('hidden')" class="text-slate-500 hover:text-slate-400 font-bold p-2">✕</button>
                    </div>

                    <form action="{{ route('inventory.store') }}" method="POST" class="flex-1 p-6 space-y-5">
                        @csrf

                        <!-- Part Name -->
                        <div>
                            <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Part Name</label>
                            <input type="text" name="name" id="name" required placeholder="Engine Oil 5W-30"
                                   class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500 text-sm">
                        </div>

                        <!-- SKU -->
                        <div>
                            <label for="sku" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">SKU Code</label>
                            <input type="text" name="sku" id="sku" required placeholder="OIL-5W30-1L"
                                   class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500 text-sm">
                        </div>

                        <!-- Initial Quantity -->
                        <div>
                            <label for="quantity" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Initial Quantity</label>
                            <input type="number" name="quantity" id="quantity" required min="0" value="0"
                                   class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500 text-sm">
                        </div>

                        <!-- Unit Price -->
                        <div>
                            <label for="price" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Unit Price ($)</label>
                            <input type="number" step="0.01" name="price" id="price" required value="0.00"
                                   class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500 text-sm">
                        </div>

                        <!-- Unit -->
                        <div>
                            <label for="unit" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Measurement Unit</label>
                            <input type="text" name="unit" id="unit" required placeholder="pcs, liters, meters" value="pcs"
                                   class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500 text-sm">
                        </div>

                        <!-- Buttons -->
                        <div class="pt-4 border-t border-slate-800 flex gap-3">
                            <button type="submit"
                                    class="flex-1 py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-lg transition text-sm">
                                Register Part
                            </button>
                            <button type="button" onclick="document.getElementById('create-part-drawer').classList.add('hidden')"
                                    class="py-2.5 px-4 bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium rounded-lg transition text-sm">
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
    <div class="glass-card w-full max-w-md rounded-2xl relative z-10 overflow-hidden shadow-2xl border border-slate-800">
        <div class="p-6 border-b border-slate-800 flex items-center justify-between bg-slate-950">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-200" id="adjustment-modal-title">Adjust Stock</h2>
            <button onclick="closeAdjustmentModal()" class="text-slate-500 hover:text-slate-400 font-bold p-2">✕</button>
        </div>

        <form id="adjustment-form" method="POST" class="p-6 space-y-4 text-xs">
            @csrf
            @method('PATCH')

            <div>
                <label for="adjustment_type" class="block text-slate-500 mb-1">Adjustment Type</label>
                <select name="adjustment_type" id="adjustment_type" required
                        class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500">
                    <option value="in">Add Stock (+)</option>
                    <option value="out">Remove Stock (-)</option>
                    <option value="adjustment">Direct Override (=)</option>
                </select>
            </div>

            <div>
                <label for="adj_quantity" class="block text-slate-500 mb-1">Quantity (<span id="adj-unit-label">pcs</span>)</label>
                <input type="number" name="quantity" id="adj_quantity" required min="1" value="1"
                       class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500">
            </div>

            <div>
                <label for="adj_notes" class="block text-slate-500 mb-1">Transaction Notes</label>
                <textarea name="notes" id="adj_notes" rows="2" placeholder="Manual stock delivery or inventory count check..."
                          class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500"></textarea>
            </div>

            <!-- Buttons -->
            <div class="pt-4 border-t border-slate-800 flex gap-2 justify-end">
                <button type="button" onclick="closeAdjustmentModal()"
                        class="py-2 px-3 bg-slate-800 hover:bg-slate-700 text-slate-350 font-medium rounded-lg">
                    Cancel
                </button>
                <button type="submit" class="py-2 px-4 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-lg">
                    Record Adjustment
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
</script>
@endsection
