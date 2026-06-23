@extends('layouts.app')

@section('title', 'Billing Workspace')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">

    <!-- Header navigation -->
    <div class="flex items-center gap-3 border-b border-slate-200 dark:border-slate-800 pb-4">
        <a href="{{ route('job-cards.show', $jobCard->id) }}" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            <span>Back to Job Card</span>
        </a>
        <span class="text-slate-400">|</span>
        <span class="text-slate-650 dark:text-slate-350 font-semibold text-sm">Invoice Workspace</span>
    </div>

    <!-- Main Workspace Form -->
    <form action="{{ route('billing.store', $jobCard->id) }}" method="POST" class="space-y-8">
        @csrf

        <!-- 1. Parts Allocated Summary -->
        <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-2">
                <i data-lucide="package" class="w-4 h-4 text-primary"></i>
                <span>Allocated Parts Summary</span>
            </h3>
            
            <div class="space-y-3">
                @php $hasParts = false; @endphp
                @foreach($allocatedParts as $mov)
                    @php $hasParts = true; @endphp
                    <div class="flex items-center justify-between p-3.5 bg-slate-50 dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-800/80 text-sm">
                        <div>
                            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $mov->inventory->name }}</span>
                            <span class="text-xs text-slate-500 font-mono block mt-0.5 font-semibold">SKU: {{ $mov->inventory->sku }}</span>
                        </div>
                        <div class="text-right">
                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ abs($mov->quantity) }} {{ $mov->inventory->unit }}</span>
                            <span class="text-xs text-slate-500 block font-mono mt-0.5">Price: {{ config('app.currency', 'Rs.') }}{{ number_format(abs($mov->quantity) * ($mov->purchaseBatch ? $mov->purchaseBatch->selling_price : $mov->inventory->selling_price), 2) }}</span>
                        </div>
                    </div>
                @endforeach

                @if(!$hasParts)
                    <div class="text-slate-500 text-sm py-4 text-center bg-slate-50 dark:bg-slate-950/10 rounded-xl border border-slate-200 dark:border-slate-800 border-dashed">
                        No parts were allocated to this repair order.
                    </div>
                @endif
            </div>
        </div>

        <!-- 2. Labor Charges Section -->
        <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-2">
                <i data-lucide="wrench" class="w-4 h-4 text-primary"></i>
                <span>Labor & Service Charges</span>
            </h3>
            
            <!-- Dynamic fields -->
            <div id="labor-items-container" class="space-y-3">
                @forelse($jobCard->services as $serv)
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center labor-item">
                        <div class="md:col-span-3">
                            <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Service / Labor Description</label>
                            <input type="text" name="labor_desc[]" value="{{ $serv->name }}" required placeholder="e.g., Engine Tune-Up, Brake Calipers Alignment"
                                   class="w-full px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs">
                        </div>
                        <div class="flex gap-2 items-end">
                            <div class="flex-1">
                                <label class="block text-[10px] text-slate-505 uppercase mb-1 font-semibold">Charge ({{ config('app.currency', '$') }})</label>
                                <input type="number" step="0.01" name="labor_price[]" value="{{ $serv->price }}" required placeholder="0.00"
                                       class="w-full px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono">
                            </div>
                            <button type="button" onclick="this.closest('.labor-item').remove()" class="text-red-500 hover:text-red-400 font-bold px-2 py-2 text-sm">✕</button>
                        </div>
                    </div>
                @empty
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center labor-item">
                        <div class="md:col-span-3">
                            <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Service / Labor Description</label>
                            <input type="text" name="labor_desc[]" placeholder="e.g., Engine Tune-Up, Brake Calipers Alignment"
                                   class="w-full px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs">
                        </div>
                        <div>
                            <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Charge ({{ config('app.currency', '$') }})</label>
                            <input type="number" step="0.01" name="labor_price[]" placeholder="0.00"
                                   class="w-full px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono">
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Add more rows button -->
            <button type="button" onclick="addLaborRow()" 
                    class="py-1.5 px-3 bg-slate-200 hover:bg-slate-305 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-750 dark:text-slate-300 font-semibold rounded-lg text-xs transition border border-transparent hover:border-slate-300 dark:hover:border-slate-700 flex items-center gap-1">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>Add Service Line</span>
            </button>
        </div>

        <!-- 3. Billing Preferences & Summary -->
        <div class="app-card rounded-2xl p-6 grid grid-cols-1 md:grid-cols-3 gap-6 items-end shadow-xs">
            <div>
                <label for="tax" class="block text-xs text-slate-500 mb-1.5 font-semibold">Apply Tax Percentage (%)</label>
                <input type="number" step="0.01" name="tax" id="tax" placeholder="0.00" value="0.00"
                       class="w-full px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono">
            </div>

            <div>
                <label for="status" class="block text-xs text-slate-500 mb-1.5 font-semibold">Invoice State</label>
                <select name="status" id="status" required
                        class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs cursor-pointer">
                    <option value="draft">Draft / Unpaid</option>
                    <option value="paid">Paid</option>
                </select>
            </div>

            <div>
                <button type="submit" 
                        class="w-full py-2.5 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg text-xs transition shadow-sm cursor-pointer">
                    Generate Invoice PDF
                </button>
            </div>
        </div>

    </form>

</div>

<script>
    function addLaborRow() {
        const container = document.getElementById('labor-items-container');
        const newRow = document.createElement('div');
        newRow.className = 'grid grid-cols-1 md:grid-cols-4 gap-4 items-center labor-item';
        newRow.innerHTML = `
            <div class="md:col-span-3">
                <input type="text" name="labor_desc[]" placeholder="e.g., Engine Tune-Up, Brake Calipers Alignment"
                       class="w-full px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs">
            </div>
            <div class="flex gap-2">
                <input type="number" step="0.01" name="labor_price[]" placeholder="0.00"
                       class="flex-1 px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono">
                <button type="button" onclick="this.closest('.labor-item').remove()" class="text-red-500 hover:text-red-400 font-bold px-2 text-sm">✕</button>
            </div>
        `;
        container.appendChild(newRow);
        lucide.createIcons();
    }
</script>
@endsection
