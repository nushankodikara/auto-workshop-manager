@extends('layouts.app')

@section('title', 'Billing Workspace')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto pb-12">

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
                <span>Allocated Parts Summary & Pricing</span>
            </h3>
            
            <div class="space-y-3">
                @php $hasParts = false; @endphp
                @foreach($allocatedParts as $mov)
                    @php 
                        $hasParts = true;
                        $existingPartItem = $jobCard->bill ? $jobCard->bill->items->where('type', 'part')->where('inventory_id', $mov->inventory_id)->first() : null;
                        $costVal = $existingPartItem ? $existingPartItem->cost_price : ($mov->cost_price ?? ($mov->purchaseBatch ? $mov->purchaseBatch->cost_price : $mov->inventory->cost_price));
                        $sellVal = $existingPartItem ? $existingPartItem->unit_price : ($mov->purchaseBatch ? $mov->purchaseBatch->selling_price : $mov->inventory->selling_price);
                    @endphp
                    <div class="p-4 bg-slate-50 dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-800/80 text-sm">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <span class="font-semibold text-slate-800 dark:text-slate-200 block text-base">{{ $mov->inventory->name }}</span>
                                <span class="text-xs text-slate-500 font-mono block mt-1 font-semibold">SKU: {{ $mov->inventory->sku }} | Qty: {{ abs($mov->quantity) }} {{ $mov->inventory->unit }}</span>
                            </div>
                            <div class="flex flex-wrap sm:flex-nowrap gap-3 items-end">
                                <div>
                                    <label class="block text-[9px] text-slate-500 uppercase mb-1 font-semibold">Cost to Company ({{ config('app.currency', 'Rs.') }})</label>
                                    <input type="number" step="0.01" name="parts_cost[{{ $mov->id }}]" value="{{ $costVal }}" required
                                           class="w-28 px-3 py-1.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono">
                                </div>
                                <div>
                                    <label class="block text-[9px] text-slate-500 uppercase mb-1 font-semibold">Selling Price ({{ config('app.currency', 'Rs.') }})</label>
                                    <input type="number" step="0.01" name="parts_price[{{ $mov->id }}]" value="{{ $sellVal }}" required
                                           class="w-28 px-3 py-1.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                @if(!$hasParts)
                    <div class="text-slate-500 text-sm py-6 text-center bg-slate-50 dark:bg-slate-950/10 rounded-xl border border-slate-200 dark:border-slate-800 border-dashed">
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
            
            <!-- Predefined Service Quick Add -->
            @if($predefinedServices->isNotEmpty())
                <div class="flex flex-wrap items-center gap-3 p-3 bg-slate-50 dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-800">
                    <label for="predefined-service-select" class="text-xs font-semibold text-slate-650 dark:text-slate-350">Quick-Add Predefined Service:</label>
                    <select id="predefined-service-select" class="app-input text-xs rounded-lg py-1.5 px-3 focus:outline-none focus:border-primary max-w-xs cursor-pointer">
                        <option value="">-- Choose Predefined Service --</option>
                        @foreach($predefinedServices as $ps)
                            <option value="{{ $ps->id }}" data-name="{{ $ps->name }}" data-cost="{{ $ps->cost_price }}" data-sell="{{ $ps->selling_price }}">
                                {{ $ps->name }} (Cost: {{ config('app.currency', 'Rs.') }}{{ $ps->cost_price }} | Sell: {{ config('app.currency', 'Rs.') }}{{ $ps->selling_price }})
                            </option>
                        @endforeach
                    </select>
                    <button type="button" onclick="addSelectedPredefinedService()" class="py-1.5 px-3.5 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg text-xs transition cursor-pointer">
                        Add Service
                    </button>
                </div>
            @endif

            <div id="labor-items-container" class="space-y-3">
                @if($jobCard->bill)
                    @forelse($jobCard->bill->items->where('type', 'labor') as $item)
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end labor-item bg-slate-50 dark:bg-slate-900/10 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800/80">
                            <div class="md:col-span-3">
                                <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Service / Labor Description</label>
                                <input type="text" name="labor_desc[]" value="{{ $item->description }}" required placeholder="e.g., Engine Tune-Up, Brake Calipers Alignment"
                                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Cost Price ({{ config('app.currency', 'Rs.') }})</label>
                                <input type="number" step="0.01" name="labor_cost[]" value="{{ $item->cost_price }}" required placeholder="0.00"
                                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Selling Price ({{ config('app.currency', 'Rs.') }})</label>
                                <input type="number" step="0.01" name="labor_price[]" value="{{ $item->unit_price }}" required placeholder="0.00"
                                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono">
                            </div>
                            <div class="flex justify-end">
                                <button type="button" onclick="this.closest('.labor-item').remove()" class="text-red-500 hover:text-red-400 font-bold px-2 py-1.5 text-xs flex items-center gap-1 cursor-pointer">
                                    ✕ Remove
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end labor-item bg-slate-50 dark:bg-slate-900/10 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800/80">
                            <div class="md:col-span-3">
                                <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Service / Labor Description</label>
                                <input type="text" name="labor_desc[]" placeholder="e.g., Engine Tune-Up, Brake Calipers Alignment"
                                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Cost Price ({{ config('app.currency', 'Rs.') }})</label>
                                <input type="number" step="0.01" name="labor_cost[]" placeholder="0.00"
                                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Selling Price ({{ config('app.currency', 'Rs.') }})</label>
                                <input type="number" step="0.01" name="labor_price[]" placeholder="0.00"
                                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono">
                            </div>
                            <div class="flex justify-end">
                                <button type="button" onclick="this.closest('.labor-item').remove()" class="text-red-500 hover:text-red-400 font-bold px-2 py-1.5 text-xs flex items-center gap-1 cursor-pointer">
                                    ✕ Remove
                                </button>
                            </div>
                        </div>
                    @endforelse
                @else
                    @forelse($jobCard->services as $serv)
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end labor-item bg-slate-50 dark:bg-slate-900/10 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800/80">
                            <div class="md:col-span-3">
                                <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Service / Labor Description</label>
                                <input type="text" name="labor_desc[]" value="{{ $serv->name }}" required placeholder="e.g., Engine Tune-Up, Brake Calipers Alignment"
                                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-505 uppercase mb-1 font-semibold">Cost Price ({{ config('app.currency', 'Rs.') }})</label>
                                <input type="number" step="0.01" name="labor_cost[]" value="{{ $serv->price }}" required placeholder="0.00"
                                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-505 uppercase mb-1 font-semibold">Selling Price ({{ config('app.currency', 'Rs.') }})</label>
                                <input type="number" step="0.01" name="labor_price[]" value="{{ $serv->price }}" required placeholder="0.00"
                                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono">
                            </div>
                            <div class="flex justify-end">
                                <button type="button" onclick="this.closest('.labor-item').remove()" class="text-red-500 hover:text-red-400 font-bold px-2 py-1.5 text-xs flex items-center gap-1 cursor-pointer">
                                    ✕ Remove
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end labor-item bg-slate-50 dark:bg-slate-900/10 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800/80">
                            <div class="md:col-span-3">
                                <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Service / Labor Description</label>
                                <input type="text" name="labor_desc[]" placeholder="e.g., Engine Tune-Up, Brake Calipers Alignment"
                                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Cost Price ({{ config('app.currency', 'Rs.') }})</label>
                                <input type="number" step="0.01" name="labor_cost[]" placeholder="0.00"
                                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Selling Price ({{ config('app.currency', 'Rs.') }})</label>
                                <input type="number" step="0.01" name="labor_price[]" placeholder="0.00"
                                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono">
                            </div>
                            <div class="flex justify-end">
                                <button type="button" onclick="this.closest('.labor-item').remove()" class="text-red-500 hover:text-red-400 font-bold px-2 py-1.5 text-xs flex items-center gap-1 cursor-pointer">
                                    ✕ Remove
                                </button>
                            </div>
                        </div>
                    @endforelse
                @endif
            </div>

            <!-- Add more rows button -->
            <button type="button" onclick="addLaborRow()" 
                    class="py-1.5 px-3 bg-slate-200 hover:bg-slate-305 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-750 dark:text-slate-300 font-semibold rounded-lg text-xs transition border border-transparent hover:border-slate-300 dark:hover:border-slate-700 flex items-center gap-1 cursor-pointer">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>Add Custom Service Line</span>
            </button>
        </div>

        <!-- 3. Outsourcing Services Section -->
        <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-2">
                <i data-lucide="handshake" class="w-4 h-4 text-primary"></i>
                <span>Outsourcing Services (Specialist Services)</span>
            </h3>
            
            <div id="outsourcing-items-container" class="space-y-3">
                @if($jobCard->bill)
                    @foreach($jobCard->bill->items->where('type', 'outsourcing') as $item)
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end outsourcing-item bg-slate-50 dark:bg-slate-900/10 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800/80">
                            <div class="md:col-span-2">
                                <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Partner Company</label>
                                <select name="outsourcing_company_id[]" required class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs cursor-pointer">
                                    <option value="">-- Select Partner --</option>
                                    @foreach($partners as $partner)
                                        <option value="{{ $partner->id }}" {{ $item->outsourcing_company_id == $partner->id ? 'selected' : '' }}>{{ $partner->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Job Description</label>
                                <input type="text" name="outsourcing_desc[]" value="{{ $item->description }}" required placeholder="e.g., Lathe Engine Block Sleeving"
                                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Cost Price ({{ config('app.currency', 'Rs.') }})</label>
                                <input type="number" step="0.01" name="outsourcing_cost[]" value="{{ $item->cost_price }}" required placeholder="0.00"
                                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Selling Price ({{ config('app.currency', 'Rs.') }})</label>
                                <input type="number" step="0.01" name="outsourcing_price[]" value="{{ $item->unit_price }}" required placeholder="0.00"
                                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono">
                            </div>
                            <div class="flex justify-end">
                                <button type="button" onclick="this.closest('.outsourcing-item').remove()" class="text-red-500 hover:text-red-400 font-bold px-2 py-1.5 text-xs flex items-center gap-1 cursor-pointer">
                                    ✕ Remove
                                </button>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            <!-- Add outsourcing row button -->
            <button type="button" onclick="addOutsourcingRow()" 
                    class="py-1.5 px-3 bg-slate-200 hover:bg-slate-305 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-750 dark:text-slate-300 font-semibold rounded-lg text-xs transition border border-transparent hover:border-slate-300 dark:hover:border-slate-700 flex items-center gap-1 cursor-pointer">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>Add Outsourced/Specialist Service Line</span>
            </button>
        </div>

        <!-- 4. Billing Preferences & Summary -->
        <div class="app-card rounded-2xl p-6 grid grid-cols-1 md:grid-cols-4 gap-6 items-end shadow-xs">
            <div>
                <label for="tax" class="block text-xs text-slate-500 mb-1.5 font-semibold">Apply Tax Percentage (%)</label>
                <input type="number" step="0.01" name="tax" id="tax" placeholder="0.00" value="{{ $jobCard->bill ? $jobCard->bill->tax : '0.00' }}"
                       class="w-full px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono">
            </div>

            <div>
                <label for="discount_percent" class="block text-xs text-slate-500 mb-1.5 font-semibold">Discount Percentage (%)</label>
                <input type="number" step="0.01" name="discount_percent" id="discount_percent" placeholder="0.00" value="{{ $jobCard->bill ? $jobCard->bill->discount_percent : '0.00' }}"
                       class="w-full px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono">
            </div>

            <div>
                <label for="status" class="block text-xs text-slate-500 mb-1.5 font-semibold">Invoice State</label>
                <select name="status" id="status" required
                        class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs cursor-pointer">
                    <option value="draft" {{ ($jobCard->bill && $jobCard->bill->status === 'draft') ? 'selected' : '' }}>Draft / Unpaid</option>
                    <option value="paid" {{ ($jobCard->bill && $jobCard->bill->status === 'paid') ? 'selected' : '' }}>Paid</option>
                </select>
            </div>

            <div>
                <button type="submit" 
                        class="w-full py-2.5 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg text-xs transition shadow-sm cursor-pointer font-sans">
                    {{ $jobCard->bill ? 'Update Invoice & Calculations' : 'Generate Invoice PDF' }}
                </button>
            </div>
        </div>

    </form>

</div>

<script>
    const partnersJson = @json($partners);

    function addLaborRow() {
        const container = document.getElementById('labor-items-container');
        const newRow = document.createElement('div');
        newRow.className = 'grid grid-cols-1 md:grid-cols-6 gap-4 items-end labor-item bg-slate-50 dark:bg-slate-900/10 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800/80';
        newRow.innerHTML = `
            <div class="md:col-span-3">
                <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Service / Labor Description</label>
                <input type="text" name="labor_desc[]" required placeholder="e.g., Engine Tune-Up, Brake Calipers Alignment"
                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs">
            </div>
            <div>
                <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Cost Price (${window.currencySymbol || 'Rs.'})</label>
                <input type="number" step="0.01" name="labor_cost[]" required placeholder="0.00"
                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono">
            </div>
            <div>
                <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Selling Price (${window.currencySymbol || 'Rs.'})</label>
                <input type="number" step="0.01" name="labor_price[]" required placeholder="0.00"
                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono">
            </div>
            <div class="flex justify-end">
                <button type="button" onclick="this.closest('.labor-item').remove()" class="text-red-500 hover:text-red-400 font-bold px-2 py-1.5 text-xs flex items-center gap-1 cursor-pointer">✕ Remove</button>
            </div>
        `;
        container.appendChild(newRow);
    }

    function addSelectedPredefinedService() {
        const select = document.getElementById('predefined-service-select');
        const selectedOption = select.options[select.selectedIndex];
        if (!selectedOption || !selectedOption.value) return;

        const name = selectedOption.getAttribute('data-name');
        const cost = selectedOption.getAttribute('data-cost');
        const sell = selectedOption.getAttribute('data-sell');

        const container = document.getElementById('labor-items-container');
        const newRow = document.createElement('div');
        newRow.className = 'grid grid-cols-1 md:grid-cols-6 gap-4 items-end labor-item bg-slate-50 dark:bg-slate-900/10 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800/80';
        newRow.innerHTML = `
            <div class="md:col-span-3">
                <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Service / Labor Description</label>
                <input type="text" name="labor_desc[]" value="${name}" required placeholder="e.g., Engine Tune-Up, Brake Calipers Alignment"
                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs">
            </div>
            <div>
                <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Cost Price (${window.currencySymbol || 'Rs.'})</label>
                <input type="number" step="0.01" name="labor_cost[]" value="${cost}" required placeholder="0.00"
                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono">
            </div>
            <div>
                <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Selling Price (${window.currencySymbol || 'Rs.'})</label>
                <input type="number" step="0.01" name="labor_price[]" value="${sell}" required placeholder="0.00"
                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono">
            </div>
            <div class="flex justify-end">
                <button type="button" onclick="this.closest('.labor-item').remove()" class="text-red-500 hover:text-red-400 font-bold px-2 py-1.5 text-xs flex items-center gap-1 cursor-pointer">✕ Remove</button>
            </div>
        `;
        container.appendChild(newRow);
        select.selectedIndex = 0; // Reset select dropdown
    }

    function addOutsourcingRow() {
        const container = document.getElementById('outsourcing-items-container');
        const newRow = document.createElement('div');
        newRow.className = 'grid grid-cols-1 md:grid-cols-6 gap-4 items-end outsourcing-item bg-slate-50 dark:bg-slate-900/10 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800/80';
        
        let partnerOptions = '<option value="">-- Select Partner --</option>';
        partnersJson.forEach(p => {
            partnerOptions += `<option value="${p.id}">${p.name}</option>`;
        });

        newRow.innerHTML = `
            <div class="md:col-span-2">
                <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Partner Company</label>
                <select name="outsourcing_company_id[]" required class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs cursor-pointer">
                    ${partnerOptions}
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Job Description</label>
                <input type="text" name="outsourcing_desc[]" required placeholder="e.g., Lathe Engine Block Sleeving"
                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs">
            </div>
            <div>
                <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Cost Price (${window.currencySymbol || 'Rs.'})</label>
                <input type="number" step="0.01" name="outsourcing_cost[]" required placeholder="0.00"
                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono">
            </div>
            <div>
                <label class="block text-[10px] text-slate-500 uppercase mb-1 font-semibold">Selling Price (${window.currencySymbol || 'Rs.'})</label>
                <input type="number" step="0.01" name="outsourcing_price[]" required placeholder="0.00"
                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono">
            </div>
            <div class="flex justify-end">
                <button type="button" onclick="this.closest('.outsourcing-item').remove()" class="text-red-500 hover:text-red-400 font-bold px-2 py-1.5 text-xs flex items-center gap-1 cursor-pointer">✕ Remove</button>
            </div>
        `;
        container.appendChild(newRow);
    }
</script>
@endsection
