@extends('layouts.app')

@section('title', 'Quotation Details')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">

    <!-- Actions bar (hidden during print) -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4 print:hidden">
        <div class="flex items-center gap-3">
            <a href="{{ route('quotations.index') }}" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                <span>Back to Workspace</span>
            </a>
            <span class="text-slate-400">|</span>
            <span class="text-slate-650 dark:text-slate-350 font-semibold text-sm">Quotation #{{ $quotation->quotation_number }}</span>
        </div>

        <div class="flex items-center gap-3">
            <!-- Print Button -->
            <button onclick="window.print()" 
                    class="px-4 py-2 bg-slate-200 hover:bg-slate-305 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-750 dark:text-slate-200 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Print / Save PDF</span>
            </button>
        </div>
    </div>

    <!-- Printable Quotation Card -->
    <div class="app-card rounded-2xl p-8 space-y-8 shadow-xs print:bg-white print:text-black print:border-0 print:p-0">
        
        <!-- Header: Workshop, Customer, & Quotation details (Single Row) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-sm border-b border-slate-200 dark:border-slate-800 pb-6 print:grid-cols-3 print:gap-6 print:pb-4 print:border-none">
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
                    <span class="text-base font-bold text-slate-855 dark:text-slate-100 print:text-black leading-tight">{{ config('app.name', 'Auto Workshop Manager') }}</span>
                </div>
                <div class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold print:text-black/60 leading-tight">Professional Auto Repair & Maintenance</div>
                <div class="text-xs text-slate-655 dark:text-slate-400 print:text-black/70 font-sans">
                    <div class="font-bold text-slate-800 dark:text-slate-300 print:text-black">{{ $shop->name }}</div>
                    <div>{{ $shop->address }}</div>
                </div>
            </div>

            <!-- Customer Billing Address -->
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 print:text-black/45">Quoted To</h4>
                <div class="font-bold text-slate-850 dark:text-slate-200 print:text-black">{{ $quotation->customer_name }}</div>
                @if($quotation->customer_phone)
                    <div class="text-xs text-slate-500 dark:text-slate-450 mt-1.5 print:text-black/70"><span class="font-semibold text-slate-700 dark:text-slate-450 print:text-black">Phone:</span> {{ $quotation->customer_phone }}</div>
                @endif
                @if($quotation->customer_email)
                    <div class="text-xs text-slate-500 dark:text-slate-450 print:text-black/70"><span class="font-semibold text-slate-700 dark:text-slate-450 print:text-black">Email:</span> {{ $quotation->customer_email }}</div>
                @endif
                @if($quotation->customer_address)
                    <div class="text-xs text-slate-500 dark:text-slate-450 print:text-black/70"><span class="font-semibold text-slate-700 dark:text-slate-450 print:text-black">Address:</span> {{ $quotation->customer_address }}</div>
                @endif
            </div>

            <!-- Quotation Metadata -->
            <div class="text-left md:text-right print:text-right">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 print:text-black/45">Summary</h4>
                <div class="font-bold text-slate-850 dark:text-slate-200 print:text-black text-sm uppercase">SERVICE QUOTATION</div>
                <div class="text-xs text-slate-500 mt-1 print:text-black/70 font-mono">Quote #: {{ $quotation->quotation_number }}</div>
                <div class="text-xs text-slate-505 print:text-black/70 font-mono">Date: {{ $quotation->created_at->format('Y-m-d') }}</div>
                <div class="text-xs text-slate-505 print:text-black/70 font-mono">Valid For: 30 Days</div>
            </div>
        </div>

        <!-- Table of Line Items -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="text-slate-500 dark:text-slate-400 font-bold uppercase text-[10px] tracking-wider print:text-black/60 border-b border-slate-200 dark:border-slate-800 pb-2">
                        <th class="py-2">Description</th>
                        <th class="py-2">Category</th>
                        <th class="py-2 text-right">Quantity</th>
                        <th class="py-2 text-right">Unit Price</th>
                        <th class="py-2 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-850/60 print:divide-black/10">
                    @php $subtotal = 0.00; @endphp
                    @foreach($quotation->items as $item)
                        @php $subtotal += $item->total_price; @endphp
                        <tr class="text-slate-750 dark:text-slate-300 print:text-black">
                            <td class="py-3 font-semibold text-slate-800 dark:text-slate-200">{{ $item->description }}</td>
                            <td class="py-3 text-xs capitalize text-slate-500">
                                {{ $item->type === 'outsourcing' ? 'Specialist Service' : ($item->type === 'labor' ? 'Labor/Service' : ($item->type === 'part' ? 'Spare Part' : $item->type)) }}
                            </td>
                            <td class="py-3 text-right font-mono">{{ number_format($item->quantity, 2) }}</td>
                            <td class="py-3 text-right font-mono">{{ config('app.currency', 'Rs.') }}{{ number_format($item->unit_price, 2) }}</td>
                            <td class="py-3 text-right font-mono font-semibold">{{ config('app.currency', 'Rs.') }}{{ number_format($item->total_price, 2) }}</td>
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
                    <span class="font-mono font-semibold">{{ config('app.currency', 'Rs.') }}{{ number_format($subtotal, 2) }}</span>
                </div>
                
                @if($quotation->discount_percent > 0)
                    @php $discountAmount = ($subtotal * ($quotation->discount_percent / 100)); @endphp
                    <div class="flex justify-between text-red-500">
                        <span>Discount ({{ number_format($quotation->discount_percent, 2) }}%):</span>
                        <span class="font-mono font-semibold">-{{ config('app.currency', 'Rs.') }}{{ number_format($discountAmount, 2) }}</span>
                    </div>
                    @php $subtotal = $subtotal - $discountAmount; @endphp
                @endif
                
                @if($quotation->tax > 0)
                    @php $taxAmount = ($subtotal * ($quotation->tax / 100)); @endphp
                    <div class="flex justify-between text-slate-500 dark:text-slate-400">
                        <span>Tax ({{ $quotation->tax }}%):</span>
                        <span class="font-mono font-semibold">{{ config('app.currency', 'Rs.') }}{{ number_format($taxAmount, 2) }}</span>
                    </div>
                @endif
                
                <div class="flex justify-between text-base font-bold border-t border-slate-200 dark:border-slate-800 pt-2 text-slate-850 dark:text-slate-100 print:text-black">
                    <span>Estimated Total:</span>
                    <span class="font-mono text-primary print:text-black">{{ config('app.currency', 'Rs.') }}{{ number_format($quotation->total_amount, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Quotation footer -->
        <div class="text-center text-xs text-slate-500 mt-12 border-t border-slate-200 dark:border-slate-800 pt-6 print:border-black/10 print:text-black/60">
            This document is a service cost estimate and is valid for 30 days from the date of issue.
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
        .print\:hidden {
            display: none !important;
        }
        .print\:border-none {
            border: none !important;
        }
        .print\:bg-white {
            background-color: white !important;
        }
        .print\:text-black {
            color: black !important;
        }
        .print\:text-black\/70 {
            color: rgba(0, 0, 0, 0.7) !important;
        }
        .print\:text-black\/60 {
            color: rgba(0, 0, 0, 0.6) !important;
        }
        .print\:text-black\/45 {
            color: rgba(0, 0, 0, 0.45) !important;
        }
        .print\:p-0 {
            padding: 0 !important;
        }
        .print\:grid-cols-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        }
    }
</style>
@endsection
