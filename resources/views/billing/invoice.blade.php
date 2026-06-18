@extends('layouts.app')

@section('title', 'Invoice Details')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">

    <!-- Actions bar (hidden during print) -->
    <div class="flex items-center justify-between border-b border-slate-800 pb-4 print:hidden">
        <div class="flex items-center gap-3">
            <a href="{{ route('job-cards.show', $jobCard->id) }}" class="text-sm font-semibold text-indigo-400 hover:text-indigo-300">
                &larr; Back to Job Card
            </a>
            <span class="text-slate-600">|</span>
            <span class="text-slate-350 font-semibold text-sm">Invoice #{{ $jobCard->bill->bill_number }}</span>
        </div>

        <div class="flex items-center gap-3">
            <!-- Mark as Paid form (Only show if draft) -->
            @if($jobCard->bill->status === 'draft')
                <form action="{{ route('billing.update-status', $jobCard->bill->id) }}" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="paid">
                    <button type="submit" 
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition">
                        Mark as Paid
                    </button>
                </form>
            @endif

            <!-- Print Button -->
            <button onclick="window.print()" 
                    class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-lg text-xs font-bold transition">
                🖨️ Print / Save PDF
            </button>
        </div>
    </div>

    <!-- Printable Invoice Card -->
    <div class="glass-card rounded-2xl p-8 space-y-8 bg-slate-900/40 border border-slate-850 print:bg-white print:text-black print:border-0 print:p-0">
        
        <!-- Header: Workshop Details & Invoice Meta -->
        <div class="flex flex-col md:flex-row justify-between gap-6 border-b border-slate-800/80 pb-6 print:border-black/10">
            <div>
                <h2 class="text-2xl font-bold text-slate-100 print:text-black">{{ config('app.name', 'Auto Workshop Manager') }}</h2>
                <p class="text-xs text-slate-400 mt-1 print:text-black/60">Professional Mechanical & Electrical Auto Repair</p>
            </div>
            <div class="text-left md:text-right text-sm">
                <div class="font-bold text-slate-200 print:text-black">INVOICE</div>
                <div class="text-xs text-slate-400 mt-1 print:text-black/60 font-mono">Invoice #: {{ $jobCard->bill->bill_number }}</div>
                <div class="text-xs text-slate-400 font-mono print:text-black/60">Date: {{ $jobCard->bill->created_at->format('Y-m-d') }}</div>
                <div class="mt-2 text-xs">
                    @if($jobCard->bill->status === 'paid')
                        <span class="px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-450 border border-emerald-500/20 font-bold uppercase print:border-black/20 print:text-black">Paid</span>
                    @else
                        <span class="px-2 py-0.5 rounded bg-red-500/10 text-red-400 border border-red-500/20 font-bold uppercase print:border-black/20 print:text-black">Unpaid / Draft</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Billing Info: Client & Vehicle -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-sm">
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 print:text-black/40">Billed To</h4>
                <div class="font-bold text-slate-200 print:text-black">{{ $jobCard->vehicle->client->name }}</div>
                <div class="text-xs text-slate-400 print:text-black/60 mt-1">Phone: {{ $jobCard->vehicle->client->phone }}</div>
                <div class="text-xs text-slate-400 print:text-black/60">Email: {{ $jobCard->vehicle->client->email ?? 'N/A' }}</div>
                <div class="text-xs text-slate-400 print:text-black/60">Address: {{ $jobCard->vehicle->client->address ?? 'N/A' }}</div>
            </div>
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 print:text-black/40">Vehicle Details</h4>
                <div class="font-bold text-slate-200 print:text-black">{{ $jobCard->vehicle->make }} {{ $jobCard->vehicle->model }}</div>
                <div class="text-xs text-slate-400 print:text-black/60 mt-1">Year: {{ $jobCard->vehicle->year }}</div>
                <div class="text-xs text-slate-400 print:text-black/60">Plate: {{ $jobCard->vehicle->plate_number }}</div>
                <div class="text-xs text-slate-400 print:text-black/60 font-mono">VIN: {{ $jobCard->vehicle->vin ?? 'N/A' }}</div>
            </div>
        </div>

        <!-- Table of Line Items -->
        <div class="overflow-x-auto border-t border-b border-slate-800/80 py-4 print:border-black/10">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="text-slate-400 font-semibold uppercase text-[10px] tracking-wider print:text-black/60">
                        <th class="py-2">Description</th>
                        <th class="py-2">Type</th>
                        <th class="py-2 text-right">Quantity</th>
                        <th class="py-2 text-right">Unit Price</th>
                        <th class="py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50 print:divide-black/10">
                    @php $subtotal = 0.00; @endphp
                    @foreach($jobCard->bill->items as $item)
                        @php $subtotal += $item->total_price; @endphp
                        <tr class="text-slate-300 print:text-black">
                            <td class="py-3 font-medium">{{ $item->description }}</td>
                            <td class="py-3 text-xs capitalize text-slate-500 print:text-black/60">{{ $item->type }}</td>
                            <td class="py-3 text-right font-mono">{{ number_format($item->quantity, 2) }}</td>
                            <td class="py-3 text-right font-mono">${{ number_format($item->unit_price, 2) }}</td>
                            <td class="py-3 text-right font-mono">${{ number_format($item->total_price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals block -->
        <div class="flex justify-end text-sm">
            <div class="w-full md:w-80 space-y-2 border-slate-800/80 pt-2 print:border-black/10">
                <div class="flex justify-between text-slate-400 print:text-black/60">
                    <span>Subtotal:</span>
                    <span class="font-mono">${{ number_format($subtotal, 2) }}</span>
                </div>
                
                @if($jobCard->bill->tax > 0)
                    @php $taxAmount = ($subtotal * ($jobCard->bill->tax / 100)); @endphp
                    <div class="flex justify-between text-slate-400 print:text-black/60">
                        <span>Tax ({{ $jobCard->bill->tax }}%):</span>
                        <span class="font-mono">${{ number_format($taxAmount, 2) }}</span>
                    </div>
                @endif
                
                <div class="flex justify-between text-lg font-bold border-t border-slate-800/80 pt-2 print:border-black/10 text-slate-100 print:text-black">
                    <span>Total Amount:</span>
                    <span class="font-mono">${{ number_format($jobCard->bill->total_amount, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Invoice footer -->
        <div class="text-center text-xs text-slate-500 mt-12 border-t border-slate-900 pt-6 print:border-black/10 print:text-black/60">
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
    }
</style>
@endsection
