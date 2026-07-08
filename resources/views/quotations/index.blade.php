@extends('layouts.app')

@section('title', 'Quotation Workspace')

@section('content')
<div class="space-y-6">

    <!-- Header Actions -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Draft Service Quotations</h2>
            <p class="text-xs text-slate-500 mt-1">Quotations are completely isolated documents and do not impact double-entry bookkeeping, billing, or inventory counts.</p>
        </div>
        <a href="{{ route('quotations.create') }}"
           class="px-4 py-2 bg-primary hover:bg-primary-hover text-white font-medium rounded-lg text-sm transition flex items-center gap-1.5 shadow-sm">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>Draft New Quotation</span>
        </a>
    </div>

    <!-- Search panel -->
    <div class="app-card rounded-2xl p-4 shadow-xs">
        <form action="{{ route('quotations.index') }}" method="GET" class="flex gap-3">
            <div class="relative flex-1">
                <i data-lucide="search" class="absolute left-3.5 top-3 w-4 h-4 text-slate-400"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by customer name or quotation number (e.g. QT-20260708-0001)..."
                       class="w-full pl-10 pr-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-semibold rounded-lg text-sm transition cursor-pointer">
                Search
            </button>
            @if($search)
                <a href="{{ route('quotations.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800/40 dark:hover:bg-slate-800/80 text-slate-500 rounded-lg text-sm font-semibold transition flex items-center justify-center">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Quotations Table -->
    <div class="app-card rounded-2xl overflow-hidden shadow-xs">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-slate-100/60 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                    <th class="py-4 px-6">Quotation Number</th>
                    <th class="py-4 px-6">Customer Name</th>
                    <th class="py-4 px-6">Contact Info</th>
                    <th class="py-4 px-6">Created Date</th>
                    <th class="py-4 px-6 text-right">Total Amount</th>
                    <th class="py-4 px-6 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-850/60">
                @forelse($quotations as $q)
                    <tr class="hover:bg-slate-100/40 dark:hover:bg-slate-900/40 transition text-slate-750 dark:text-slate-300">
                        <td class="py-4 px-6 font-mono font-bold text-primary dark:text-sky-400">
                            {{ $q->quotation_number }}
                        </td>
                        <td class="py-4 px-6 font-semibold text-slate-850 dark:text-slate-200">
                            {{ $q->customer_name }}
                        </td>
                        <td class="py-4 px-6 text-xs space-y-0.5">
                            @if($q->customer_phone)
                                <div class="flex items-center gap-1"><i data-lucide="phone" class="w-3 h-3 text-slate-400 shrink-0"></i> <span>{{ $q->customer_phone }}</span></div>
                            @endif
                            @if($q->customer_email)
                                <div class="flex items-center gap-1"><i data-lucide="mail" class="w-3 h-3 text-slate-400 shrink-0"></i> <span>{{ $q->customer_email }}</span></div>
                            @endif
                            @if(!$q->customer_phone && !$q->customer_email)
                                <span class="text-slate-400 font-italic">No contact saved</span>
                            @endif
                        </td>
                        <td class="py-4 px-6 text-xs font-mono">
                            {{ $q->created_at->format('Y-m-d H:i') }}
                        </td>
                        <td class="py-4 px-6 text-right font-semibold font-mono text-slate-800 dark:text-slate-250">
                            {{ config('app.currency', 'Rs.') }}{{ number_format($q->total_amount, 2) }}
                        </td>
                        <td class="py-4 px-6 text-right flex items-center justify-end gap-2">
                            <!-- View/Print -->
                            <a href="{{ route('quotations.show', $q->id) }}"
                               class="text-xs font-bold text-primary bg-primary/10 border border-primary/20 px-2.5 py-1 rounded transition hover:bg-primary hover:text-white flex items-center gap-1">
                                <i data-lucide="printer" class="w-3 h-3"></i>
                                <span>View / Print</span>
                            </a>
                            <!-- Delete -->
                            <form action="{{ route('quotations.destroy', $q->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this quotation?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-bold text-red-650 bg-red-500/10 border border-red-500/20 px-2.5 py-1 rounded transition hover:bg-red-650 hover:text-white cursor-pointer flex items-center gap-1">
                                    <i data-lucide="trash-2" class="w-3 h-3"></i>
                                    <span>Delete</span>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-500">
                            No service quotations saved yet. Click "Draft New Quotation" to create one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $quotations->links() }}
    </div>

</div>
@endsection
