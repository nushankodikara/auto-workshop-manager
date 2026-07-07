@extends('layouts.app')

@section('title', 'Vehicle Service History')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto print:p-0 print:max-w-full print:bg-white print:text-black">

    <!-- Company Branding Header (Visible on print, clean on screen) -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4 mb-6 print:border-none print:pb-3">
        <div class="flex items-center gap-3">
            @if(file_exists(public_path('images/logo.png')))
                <img src="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" alt="Logo" class="w-10 h-10 object-contain rounded-lg shrink-0">
            @else
                <svg class="w-10 h-10 text-primary shrink-0" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M50 10 L85 30 L85 70 L50 90 L15 70 L15 30 Z" stroke="currentColor" stroke-width="6" stroke-linejoin="round" fill="currentColor" fill-opacity="0.05"/>
                    <circle cx="50" cy="50" r="18" stroke="currentColor" stroke-width="6"/>
                    <path d="M50 24 L50 32 M50 68 L50 76 M24 50 L32 50 M68 50 L76 50 M32 32 L38 38 M62 62 L68 68 M32 68 L38 62 M62 32 L68 38" stroke="currentColor" stroke-width="6" stroke-linecap="round"/>
                    <path d="M35 65 L65 35" stroke="currentColor" stroke-width="8" stroke-linecap="round"/>
                </svg>
            @endif
            <div>
                <h1 class="text-xl font-bold text-slate-850 dark:text-slate-100 print:text-black leading-tight">{{ config('app.name', 'Auto Workshop Manager') }}</h1>
                <p class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold print:text-black/60">Vehicle Service & Maintenance Report</p>
            </div>
        </div>
        <div class="text-right text-xs text-slate-500 print:text-black/60 font-mono">
            <div>Report Date: {{ date('Y-m-d') }}</div>
        </div>
    </div>

    <!-- Header navigation & controls (Hidden on Print) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-4 print:hidden">
        <div class="flex items-center gap-3">
            <a href="{{ route('vehicles.index') }}" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                <span>Vehicles Directory</span>
            </a>
            <span class="text-slate-400">|</span>
            <span class="text-slate-650 dark:text-slate-350 font-semibold text-sm">{{ $vehicle->make }} {{ $vehicle->model }} ({{ $vehicle->plate_number }})</span>
        </div>

        <div class="flex items-center gap-4">
            <!-- Toggle prices setting -->
            <label class="inline-flex items-center cursor-pointer text-xs font-semibold text-slate-650 dark:text-slate-405">
                <input type="checkbox" id="toggle-prices" checked onchange="togglePrices(this.checked)"
                       class="h-4 w-4 rounded border-slate-350 dark:border-slate-800 text-primary focus:ring-primary mr-2">
                <span>Include Prices in Report</span>
            </label>

            <!-- Print button -->
            <button onclick="window.print()" class="px-4 py-2 bg-primary hover:bg-primary-hover text-white font-medium rounded-lg text-xs transition flex items-center gap-1.5 shadow-sm">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Print Report</span>
            </button>
        </div>
    </div>

    <!-- Vehicle Summary Card -->
    <div class="app-card rounded-2xl p-6 shadow-xs print:border-b print:border-black/20 print:p-0 print:shadow-none print:bg-transparent">
        <div class="flex flex-col md:flex-row justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-4 mb-4 print:border-black/10">
            <div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100 print:text-black">{{ $vehicle->make }} {{ $vehicle->model }}</h2>
                <p class="text-xs text-slate-500 mt-1 font-semibold uppercase tracking-wider print:text-black/60">Registered Year: {{ $vehicle->year }}</p>
            </div>
            <div class="text-left md:text-right">
                <span class="text-xs text-slate-500 block font-semibold print:text-black/60">Owner / Client</span>
                @if($vehicle->client)
                    <span class="font-bold text-slate-800 dark:text-slate-200 block mt-0.5 print:text-black">{{ $vehicle->client->name }}</span>
                    <span class="text-xs text-slate-500 font-mono block mt-0.5 print:text-black/60">{{ $vehicle->client->phone }} • {{ $vehicle->client->email }}</span>
                @else
                    <span class="text-slate-400 block mt-0.5">No client profile linked</span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-6 text-sm">
            <div>
                <span class="text-slate-500 block text-xs font-semibold print:text-black/60">Plate / Registration No.</span>
                <span class="font-mono font-bold text-slate-800 dark:text-slate-200 mt-1 block print:text-black">{{ $vehicle->plate_number }}</span>
            </div>
            <div>
                <span class="text-slate-500 block text-xs font-semibold print:text-black/60">Chassis / VIN</span>
                <span class="font-mono font-semibold text-slate-700 dark:text-slate-300 mt-1 block print:text-black">{{ $vehicle->vin ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="text-slate-500 block text-xs font-semibold print:text-black/60">Current Mileage (Odometer)</span>
                <span class="font-bold text-slate-850 dark:text-slate-200 mt-1 block print:text-black">
                    {{ $vehicle->mileage ? number_format($vehicle->mileage) . ' km' : 'Not recorded' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Repair Timeline / Services History -->
    <div class="space-y-6">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-450 flex items-center gap-1.5 print:text-black">
            <i data-lucide="history" class="w-4 h-4 text-primary print:hidden"></i>
            <span>Repair & Maintenance Logs</span>
        </h3>

        @forelse($vehicle->jobCards as $jc)
            <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs page-break-inside-avoid print:border print:border-black/20 print:p-6 print:shadow-none print:bg-transparent">
                <!-- Job Card Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3 print:border-black/10">
                    <div>
                        <span class="font-bold text-slate-800 dark:text-slate-200 text-sm print:text-black">Job Card #{{ str_pad($jc->id, 4, '0', STR_PAD_LEFT) }}</span>
                        <span class="text-xs text-slate-500 ml-2 print:text-black/60">{{ $jc->created_at->format('Y-m-d') }}</span>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-slate-500 print:text-black/60">
                        @if($jc->mileage)
                            <span class="font-semibold flex items-center gap-1">
                                <i data-lucide="gauge" class="w-3.5 h-3.5 print:hidden"></i>
                                <span>Odometer: {{ number_format($jc->mileage) }} km</span>
                            </span>
                            <span>•</span>
                        @endif
                        <span class="capitalize">Shop: {{ $jc->shop->name }}</span>
                    </div>
                </div>

                <!-- Diagnostics & Notes -->
                @if($jc->notes)
                    <div>
                        <span class="text-slate-500 block text-xs font-semibold print:text-black/60">Diagnostics & Remarks:</span>
                        <p class="text-slate-700 dark:text-slate-350 text-xs mt-1 leading-relaxed">{{ $jc->notes }}</p>
                    </div>
                @endif

                <!-- Services Performed List -->
                @if($jc->services->isNotEmpty())
                    <div>
                        <span class="text-slate-500 block text-xs font-semibold print:text-black/60 mb-2">Services & Operations:</span>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-950/40 border-b border-slate-200 dark:border-slate-800 text-slate-550 dark:text-slate-450 font-semibold print:bg-transparent print:border-black/10">
                                        <th class="py-2 px-3">Service Name</th>
                                        <th class="py-2 px-3">Description</th>
                                        <th class="py-2 px-3 text-right price-element">Price</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40 print:divide-black/10">
                                    @foreach($jc->services as $serv)
                                        <tr class="text-slate-700 dark:text-slate-300 print:text-black">
                                            <td class="py-2 px-3 font-semibold">{{ $serv->name }}</td>
                                            <td class="py-2 px-3 text-slate-500 print:text-black/70">{{ $serv->description ?? 'N/A' }}</td>
                                            <td class="py-2 px-3 text-right font-mono price-element">{{ config('app.currency', '$') }}{{ number_format($serv->price, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Parts Used List -->
                @php
                    $partsMovements = $jc->stockMovements->where('type', 'out');
                @endphp
                @if($partsMovements->isNotEmpty())
                    <div>
                        <span class="text-slate-500 block text-xs font-semibold print:text-black/60 mb-2">Parts / Inventory Allocated:</span>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-950/40 border-b border-slate-200 dark:border-slate-800 text-slate-550 dark:text-slate-450 font-semibold print:bg-transparent print:border-black/10">
                                        <th class="py-2 px-3">Part Name</th>
                                        <th class="py-2 px-3">SKU</th>
                                        <th class="py-2 px-3 text-center">Qty</th>
                                        <th class="py-2 px-3 text-right price-element">Unit Price</th>
                                        <th class="py-2 px-3 text-right price-element">Total Price</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40 print:divide-black/10">
                                    @foreach($partsMovements as $mov)
                                        <tr class="text-slate-700 dark:text-slate-300 print:text-black">
                                            <td class="py-2 px-3 font-medium">{{ $mov->inventory->name }}</td>
                                            <td class="py-2 px-3 font-mono text-slate-500 print:text-black/70">{{ $mov->inventory->sku }}</td>
                                            <td class="py-2 px-3 text-center font-mono">{{ abs($mov->quantity) }}</td>
                                            <td class="py-2 px-3 text-right font-mono price-element">{{ config('app.currency', 'Rs.') }}{{ number_format($mov->purchaseBatch ? $mov->purchaseBatch->selling_price : $mov->inventory->selling_price, 2) }}</td>
                                            <td class="py-2 px-3 text-right font-mono price-element">{{ config('app.currency', 'Rs.') }}{{ number_format(abs($mov->quantity) * ($mov->purchaseBatch ? $mov->purchaseBatch->selling_price : $mov->inventory->selling_price), 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Job Cost Summary (Only shown when price is enabled) -->
                @if($jc->bill)
                    <div class="flex justify-between items-center bg-slate-50 dark:bg-slate-950/20 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800/80 text-xs price-element print:bg-transparent print:border-black/10 print:p-3">
                        <span class="font-bold text-slate-500 uppercase tracking-wider print:text-black/70">Invoice: {{ $jc->bill->bill_number }} ({{ ucfirst($jc->bill->status) }})</span>
                        <span class="text-sm font-bold text-slate-800 dark:text-slate-100 font-mono print:text-black">
                            Total Bill: {{ config('app.currency', '$') }}{{ number_format($jc->bill->total_amount, 2) }}
                        </span>
                    </div>
                @elseif($jc->ticket_sum > 0)
                    <div class="flex justify-between items-center bg-slate-50 dark:bg-slate-950/20 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800/80 text-xs price-element print:bg-transparent print:border-black/10 print:p-3">
                        <span class="font-bold text-slate-500 uppercase tracking-wider print:text-black/70">Ticket Sum</span>
                        <span class="text-sm font-bold text-slate-800 dark:text-slate-100 font-mono print:text-black">
                            {{ config('app.currency', 'Rs.') }}{{ number_format($jc->ticket_sum, 2) }}
                        </span>
                    </div>
                @endif
            </div>
        @empty
            <div class="text-slate-500 text-sm py-12 text-center bg-slate-50 dark:bg-slate-955/20 rounded-xl border border-slate-200 dark:border-slate-800 border-dashed">
                No repair or maintenance operations registered for this vehicle.
            </div>
        @endforelse
    </div>

</div>

<script>
    function togglePrices(show) {
        document.querySelectorAll('.price-element').forEach(el => {
            if (show) {
                el.style.display = '';
            } else {
                el.style.display = 'none';
            }
        });
    }

    // Set default initial state on load
    document.addEventListener("DOMContentLoaded", function() {
        togglePrices(document.getElementById('toggle-prices').checked);
    });
</script>

<style>
    @media print {
        body {
            background-color: white !important;
            color: black !important;
        }
        .app-card {
            border: none !important;
            box-shadow: none !important;
            background: transparent !important;
            color: black !important;
            padding: 0 !important;
        }
        /* Strip out borders and lines to keep print simple and border-free */
        .border-b, .border-t, .divide-y, td, th, tr, table {
            border: none !important;
            border-bottom: none !important;
            border-top: none !important;
            border-color: transparent !important;
            box-shadow: none !important;
        }
        /* Add spacing in place of horizontal dividers */
        .pb-4, .pb-3, .pt-3 {
            padding-bottom: 1rem !important;
            padding-top: 1rem !important;
        }
        /* Page break prevention rules */
        .page-break-inside-avoid {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
    }
</style>
@endsection
