@extends('layouts.app')

@section('title', 'Invoice Details')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">

    <!-- Actions bar (hidden during print) -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4 print:hidden">
        <div class="flex items-center gap-3">
            <a href="{{ route('job-cards.show', $jobCard->id) }}" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                <span>Back to Job Card</span>
            </a>
            <span class="text-slate-400">|</span>
            <span class="text-slate-650 dark:text-slate-350 font-semibold text-sm">Invoice #{{ $jobCard->bill->bill_number }}</span>
        </div>

        <div class="flex items-center gap-3">
            <!-- Mark as Paid form (Only show if draft) -->
            @if($jobCard->bill->status === 'draft')
                <form action="{{ route('billing.update-status', $jobCard->bill->id) }}" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="paid">
                    <button type="submit" 
                            class="px-4 py-2 bg-green-650 hover:bg-green-650 text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                        <span>Mark as Paid</span>
                    </button>
                </form>
            @endif

            <!-- Print Button -->
            <button onclick="window.print()" 
                    class="px-4 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-750 dark:text-slate-200 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-bold transition flex items-center gap-1.5">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Print / Save PDF</span>
            </button>
        </div>
    </div>

    <!-- Printable Invoice Card -->
    <div class="app-card rounded-2xl p-8 space-y-8 shadow-xs print:bg-white print:text-black print:border-0 print:p-0">
        
        <!-- Header: Workshop, Customer, Vehicle, & Invoice details (Single Row) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 text-sm border-b border-slate-200 dark:border-slate-800 pb-6 print:grid-cols-4 print:gap-6 print:pb-4 print:border-none">
            <!-- Company Logo & Address -->
            <div class="space-y-2.5">
                <div class="flex items-center gap-2">
                    @if(file_exists(public_path('images/logo.png')))
                        <img src="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" alt="Logo" class="w-8 h-8 object-contain rounded-lg shrink-0">
                    @else
                        <!-- Fallback SVG Logo -->
                        <svg class="w-8 h-8 text-primary shrink-0" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M50 10 L85 30 L85 70 L50 90 L15 70 L15 30 Z" stroke="currentColor" stroke-width="6" stroke-linejoin="round" fill="currentColor" fill-opacity="0.05"/>
                            <circle cx="50" cy="50" r="18" stroke="currentColor" stroke-width="6"/>
                            <path d="M50 24 L50 32 M50 68 L50 76 M24 50 L32 50 M68 50 L76 50 M32 32 L38 38 M62 62 L68 68 M32 68 L38 62 M62 32 L68 38" stroke="currentColor" stroke-width="6" stroke-linecap="round"/>
                            <path d="M35 65 L65 35" stroke="currentColor" stroke-width="8" stroke-linecap="round"/>
                        </svg>
                    @endif
                    <span class="text-base font-bold text-slate-850 dark:text-slate-100 print:text-black leading-tight">{{ config('app.name', 'Auto Workshop Manager') }}</span>
                </div>
                <div class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold print:text-black/60 leading-tight">Professional Auto Repair & Maintenance</div>
                <div class="text-xs text-slate-600 dark:text-slate-400 print:text-black/70">
                    <div class="font-bold text-slate-800 dark:text-slate-300 print:text-black">{{ $jobCard->shop->name }}</div>
                    <div>{{ $jobCard->shop->address }}</div>
                </div>
            </div>

            <!-- Customer Billing Address -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 print:text-black/45">Billed To</h4>
                <div class="font-bold text-slate-850 dark:text-slate-200 print:text-black">{{ $jobCard->vehicle->client->name }}</div>
                <div class="text-xs text-slate-500 dark:text-slate-450 mt-1.5 print:text-black/70"><span class="font-semibold text-slate-700 dark:text-slate-450 print:text-black">Phone:</span> {{ $jobCard->vehicle->client->phone }}</div>
                <div class="text-xs text-slate-500 dark:text-slate-450 print:text-black/70"><span class="font-semibold text-slate-700 dark:text-slate-450 print:text-black">Email:</span> {{ $jobCard->vehicle->client->email ?? 'N/A' }}</div>
                <div class="text-xs text-slate-500 dark:text-slate-450 print:text-black/70"><span class="font-semibold text-slate-700 dark:text-slate-450 print:text-black">Address:</span> {{ $jobCard->vehicle->client->address ?? 'N/A' }}</div>
            </div>

            <!-- Vehicle Details -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 print:text-black/45">Vehicle Details</h4>
                <div class="font-bold text-slate-850 dark:text-slate-200 print:text-black">{{ $jobCard->vehicle->make }} {{ $jobCard->vehicle->model }}</div>
                <div class="text-xs text-slate-500 dark:text-slate-450 mt-1.5 print:text-black/70"><span class="font-semibold text-slate-700 dark:text-slate-450 print:text-black">Year:</span> {{ $jobCard->vehicle->year }}</div>
                <div class="text-xs text-slate-500 dark:text-slate-450 print:text-black/70"><span class="font-semibold text-slate-700 dark:text-slate-450 print:text-black">Plate:</span> {{ $jobCard->vehicle->plate_number }}</div>
                <div class="text-xs text-slate-500 dark:text-slate-450 print:text-black/70 font-mono"><span class="font-semibold font-sans text-slate-700 dark:text-slate-450 print:text-black">VIN:</span> {{ $jobCard->vehicle->vin ?? 'N/A' }}</div>
            </div>

            <!-- Invoice Metadata -->
            <div class="text-left lg:text-right print:text-right">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 print:text-black/45">Invoice Summary</h4>
                <div class="font-bold text-slate-850 dark:text-slate-200 print:text-black text-sm uppercase">INVOICE</div>
                <div class="text-xs text-slate-500 mt-1 print:text-black/70 font-mono">Invoice #: {{ $jobCard->bill->bill_number }}</div>
                <div class="text-xs text-slate-500 print:text-black/70 font-mono">Date: {{ $jobCard->bill->created_at->format('Y-m-d') }}</div>
                <div class="mt-2 text-xs">
                    @if($jobCard->bill->status === 'paid')
                        <span class="px-2.5 py-0.5 rounded bg-green-500/10 text-green-600 dark:text-green-450 border border-green-500/20 font-bold uppercase print:border-black/20 print:text-black">Paid</span>
                    @else
                        <span class="px-2.5 py-0.5 rounded bg-red-500/10 text-red-650 dark:text-red-400 border border-red-500/20 font-bold uppercase print:border-black/20 print:text-black">Unpaid / Draft</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Table of Line Items -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="text-slate-500 dark:text-slate-400 font-bold uppercase text-[10px] tracking-wider print:text-black/60 border-b border-slate-200 dark:border-slate-800 pb-2">
                        <th class="py-2">Description</th>
                        <th class="py-2">Type</th>
                        <th class="py-2 text-right">Quantity</th>
                        <th class="py-2 text-right">Unit Price</th>
                        <th class="py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-850/60 print:divide-black/10">
                    @php $subtotal = 0.00; @endphp
                    @foreach($jobCard->bill->items as $item)
                        @php $subtotal += $item->total_price; @endphp
                        <tr class="text-slate-750 dark:text-slate-300 print:text-black">
                            <td class="py-3 font-semibold text-slate-800 dark:text-slate-200">{{ $item->description }}</td>
                            <td class="py-3 text-xs capitalize text-slate-500">{{ $item->type }}</td>
                            <td class="py-3 text-right font-mono">{{ number_format($item->quantity, 2) }}</td>
                            <td class="py-3 text-right font-mono">{{ config('app.currency', '$') }}{{ number_format($item->unit_price, 2) }}</td>
                            <td class="py-3 text-right font-mono font-semibold">{{ config('app.currency', '$') }}{{ number_format($item->total_price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals block -->
        <div class="flex justify-end text-sm border-t border-slate-200 dark:border-slate-800 pt-4 print:border-black/10">
            <div class="w-full md:w-80 space-y-2">
                <div class="flex justify-between text-slate-500 dark:text-slate-400">
                    <span>Subtotal:</span>
                    <span class="font-mono font-semibold">{{ config('app.currency', '$') }}{{ number_format($subtotal, 2) }}</span>
                </div>
                
                @if($jobCard->bill->tax > 0)
                    @php $taxAmount = ($subtotal * ($jobCard->bill->tax / 100)); @endphp
                    <div class="flex justify-between text-slate-500 dark:text-slate-400">
                        <span>Tax ({{ $jobCard->bill->tax }}%):</span>
                        <span class="font-mono font-semibold">{{ config('app.currency', '$') }}{{ number_format($taxAmount, 2) }}</span>
                    </div>
                @endif
                
                <div class="flex justify-between text-base font-bold border-t border-slate-200 dark:border-slate-800 pt-2 text-slate-850 dark:text-slate-100 print:text-black">
                    <span>Total Amount:</span>
                    <span class="font-mono text-primary">{{ config('app.currency', '$') }}{{ number_format($jobCard->bill->total_amount, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Invoice footer -->
        <div class="text-center text-xs text-slate-500 mt-12 border-t border-slate-200 dark:border-slate-800 pt-6 print:border-black/10 print:text-black/60">
            Thank you for your business! If you have any questions about this invoice, please contact support.
        </div>

    </div>

</div>

<!-- Styles override for clean printing layout -->
<style>
    @media print {
        aside {
            display: none !important;
        }
        header {
            display: none !important;
        }
        main {
            padding: 0 !important;
            background: white !important;
        }
        body {
            background: white !important;
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
        .pb-6, .pt-4, .pt-6 {
            padding-bottom: 1.5rem !important;
            padding-top: 1.5rem !important;
        }
    }
</style>
@endsection
