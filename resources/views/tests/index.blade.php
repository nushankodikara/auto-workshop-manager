@extends('layouts.app')

@section('title', 'Tests & Utilities')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto pb-12">
    <!-- Header Navigation -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-850 dark:text-slate-100 flex items-center gap-2">
                <i data-lucide="beaker" class="w-6 h-6 text-primary"></i>
                <span>Tests & Utilities</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">Super-admin diagnostics, testing environment, and offline invoice mockers.</p>
        </div>
    </div>

    <!-- Utilities Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Utility Card: Dummy Invoice Generator -->
        <div class="app-card rounded-2xl p-6 flex flex-col justify-between shadow-xs border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/50">
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <span class="p-2.5 rounded-xl bg-primary/10 text-primary">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                    </span>
                    <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">Dummy Invoice Generator</h3>
                </div>
                <p class="text-xs text-slate-550 dark:text-slate-400 leading-relaxed">
                    Generate, preview, and print final workshop invoices dynamically. Input mock client details, vehicle information, and arbitrary line items without making modifications to the database or double-entry ledgers.
                </p>
            </div>
            
            <div class="pt-6 mt-6 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 bg-slate-100 dark:bg-slate-950 px-2 py-0.5 rounded">Mock Utility</span>
                <a href="{{ route('billing.dummy.workspace') }}" class="px-4 py-2 bg-primary hover:bg-primary-hover text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer">
                    <span>Launch Generator</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
