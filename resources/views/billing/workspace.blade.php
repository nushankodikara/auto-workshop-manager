@extends('layouts.app')

@section('title', 'Billing Workspace')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">

    <!-- Header navigation -->
    <div class="flex items-center gap-3 border-b border-slate-800 pb-4">
        <a href="{{ route('job-cards.show', $jobCard->id) }}" class="text-sm font-semibold text-indigo-400 hover:text-indigo-300">
            &larr; Back to Job Card
        </a>
        <span class="text-slate-600">|</span>
        <span class="text-slate-350 font-semibold text-sm">Invoice Workspace</span>
    </div>

    <!-- Main Workspace Form -->
    <form action="{{ route('billing.store', $jobCard->id) }}" method="POST" class="space-y-8">
        @csrf

        <!-- 1. Parts Allocated Summary -->
        <div class="glass-card rounded-2xl p-6 space-y-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800/80 pb-3">
                📦 Allocated Parts Summary
            </h3>
            
            <div class="space-y-3">
                @php $hasParts = false; @endphp
                @foreach($allocatedParts as $mov)
                    @php $hasParts = true; @endphp
                    <div class="flex items-center justify-between p-3.5 bg-slate-900/40 rounded-xl border border-slate-850/80 text-sm">
                        <div>
                            <span class="font-semibold text-slate-200">{{ $mov->inventory->name }}</span>
                            <span class="text-xs text-slate-500 font-mono block mt-0.5">SKU: {{ $mov->inventory->sku }}</span>
                        </div>
                        <div class="text-right">
                            <span class="font-bold text-slate-300">{{ abs($mov->quantity) }} {{ $mov->inventory->unit }}</span>
                            <span class="text-xs text-slate-500 block mt-0.5">Cost: ${{ number_format(abs($mov->quantity) * $mov->inventory->price, 2) }}</span>
                        </div>
                    </div>
                @endforeach

                @if(!$hasParts)
                    <div class="text-slate-500 text-sm py-4 text-center bg-slate-900/20 rounded-xl border border-slate-850/80">
                        No parts were allocated to this repair order.
                    </div>
                @endif
            </div>
        </div>

        <!-- 2. Labor Charges Section -->
        <div class="glass-card rounded-2xl p-6 space-y-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800/80 pb-3">
                ⚙️ Labor & Service Charges
            </h3>
            
            <!-- Dynamic fields -->
            <div id="labor-items-container" class="space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center labor-item">
                    <div class="md:col-span-3">
                        <label class="block text-[10px] text-slate-500 uppercase mb-1">Service / Labor Description</label>
                        <input type="text" name="labor_desc[]" placeholder="e.g., Engine Tune-Up, Brake Calipers Alignment"
                               class="w-full px-4 py-2 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500 text-xs">
                    </div>
                    <div>
                        <label class="block text-[10px] text-slate-500 uppercase mb-1">Charge ($)</label>
                        <input type="number" step="0.01" name="labor_price[]" placeholder="0.00"
                               class="w-full px-4 py-2 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500 text-xs">
                    </div>
                </div>
            </div>

            <!-- Add more rows button -->
            <button type="button" onclick="addLaborRow()" 
                    class="py-1.5 px-3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold rounded-lg text-xs transition border border-slate-750">
                + Add Service Line
            </button>
        </div>

        <!-- 3. Billing Preferences & Summary -->
        <div class="glass-card rounded-2xl p-6 grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
            <div>
                <label for="tax" class="block text-xs text-slate-500 mb-1.5">Apply Tax Percentage (%)</label>
                <input type="number" step="0.01" name="tax" id="tax" placeholder="0.00" value="0.00"
                       class="w-full px-4 py-2 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500 text-xs">
            </div>

            <div>
                <label for="status" class="block text-xs text-slate-500 mb-1.5">Invoice State</label>
                <select name="status" id="status" required
                        class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500 text-xs cursor-pointer">
                    <option value="draft">Draft / Unpaid</option>
                    <option value="paid">Paid</option>
                </select>
            </div>

            <div>
                <button type="submit" 
                        class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-lg text-xs transition shadow-lg shadow-indigo-600/10">
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
                       class="w-full px-4 py-2 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500 text-xs">
            </div>
            <div class="flex gap-2">
                <input type="number" step="0.01" name="labor_price[]" placeholder="0.00"
                       class="flex-1 px-4 py-2 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500 text-xs">
                <button type="button" onclick="this.closest('.labor-item').remove()" class="text-red-500 hover:text-red-400 font-bold px-2 text-sm">✕</button>
            </div>
        `;
        container.appendChild(newRow);
    }
</script>
@endsection
