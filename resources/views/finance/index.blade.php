@extends('layouts.app')

@section('title', 'Double-Entry Bookkeeping & Ledger')

@section('content')
<div class="space-y-6">
    <!-- Top KPI metrics with Share Value calculation -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
        <!-- Assets Card -->
        <div class="app-card rounded-2xl p-6 shadow-xs border border-blue-500/10 bg-blue-500/[0.01]">
            <span class="text-xs text-slate-500 block uppercase font-bold tracking-wider mb-2">Total Assets</span>
            <div class="flex items-baseline gap-1">
                <span class="text-2xl font-extrabold text-slate-800 dark:text-slate-200 font-mono">
                    {{ config('app.currency', '$') }}{{ number_format($assetsTotal, 2) }}
                </span>
            </div>
            <span class="text-[10px] text-slate-400 block mt-1">Cash, AR, and Stock Value</span>
        </div>

        <!-- Liabilities Card -->
        <div class="app-card rounded-2xl p-6 shadow-xs border border-red-500/10 bg-red-500/[0.01]">
            <span class="text-xs text-slate-500 block uppercase font-bold tracking-wider mb-2">Total Liabilities</span>
            <div class="flex items-baseline gap-1">
                <span class="text-2xl font-extrabold text-slate-800 dark:text-slate-200 font-mono">
                    {{ config('app.currency', '$') }}{{ number_format($liabilitiesTotal, 2) }}
                </span>
            </div>
            <span class="text-[10px] text-slate-400 block mt-1">AP and Tax Obligations</span>
        </div>

        <!-- Net Book Value Card -->
        <div class="app-card rounded-2xl p-6 shadow-xs border border-emerald-500/10 bg-emerald-500/[0.01]">
            <span class="text-xs text-slate-500 block uppercase font-bold tracking-wider mb-2">Net Book Value</span>
            <div class="flex items-baseline gap-1">
                <span class="text-2xl font-extrabold text-slate-800 dark:text-slate-200 font-mono">
                    {{ config('app.currency', '$') }}{{ number_format($netEquity, 2) }}
                </span>
            </div>
            <span class="text-[10px] text-slate-400 block mt-1">Assets minus Liabilities</span>
        </div>

        <!-- Total Shares Setting -->
        <div class="app-card rounded-2xl p-6 shadow-xs border border-slate-500/10 bg-slate-550/[0.01]">
            <span class="text-xs text-slate-500 block uppercase font-bold tracking-wider mb-2">Total Shares</span>
            <div class="flex items-baseline justify-between">
                <span class="text-2xl font-extrabold text-slate-800 dark:text-slate-200 font-mono">
                    {{ number_format($totalShares) }}
                </span>
                <a href="{{ route('settings.index') }}" class="text-[10px] font-bold text-primary hover:underline">
                    Edit Shares
                </a>
            </div>
            <span class="text-[10px] text-slate-400 block mt-1">Authorized share capital</span>
        </div>

        <!-- Share Value calculation -->
        <div class="app-card rounded-2xl p-6 shadow-xs border border-indigo-500/15 bg-indigo-550/[0.02]">
            <span class="text-xs text-indigo-500 dark:text-indigo-400 block uppercase font-bold tracking-wider mb-2">Current Share Value</span>
            <div class="flex items-baseline gap-1">
                <span class="text-3xl font-black text-indigo-600 dark:text-indigo-400 font-mono">
                    {{ config('app.currency', '$') }}{{ number_format($shareValue, 4) }}
                </span>
            </div>
            <span class="text-[10px] text-slate-400 block mt-1">Net Book Value / Total Shares</span>
        </div>
    </div>

    <!-- Navigation Tab Header -->
    <div class="border-b border-slate-200 dark:border-slate-800 flex justify-between items-center pb-px">
        <div class="flex gap-6">
            <button onclick="switchTab('tab-ledger')" id="btn-tab-ledger" class="px-4 py-2 font-semibold text-sm border-b-2 border-primary text-primary transition">
                General Ledger
            </button>
            <button onclick="switchTab('tab-accounts')" id="btn-tab-accounts" class="px-4 py-2 font-semibold text-sm border-b-2 border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-350 transition">
                Chart of Accounts
            </button>
            <button onclick="switchTab('tab-customers')" id="btn-tab-customers" class="px-4 py-2 font-semibold text-sm border-b-2 border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-355 transition">
                Customer Books
            </button>
            <button onclick="switchTab('tab-investors')" id="btn-tab-investors" class="px-4 py-2 font-semibold text-sm border-b-2 border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-355 transition">
                Investor Capital
            </button>
            <button onclick="switchTab('tab-exports')" id="btn-tab-exports" class="px-4 py-2 font-semibold text-sm border-b-2 border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-355 transition">
                Data Exports
            </button>
        </div>

        <div class="flex gap-2">
            <!-- New Journal Entry Trigger -->
            <button onclick="document.getElementById('new-journal-drawer').classList.remove('hidden')" 
                    class="px-4 py-2 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg text-xs transition flex items-center gap-1.5 shadow-sm">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Log Transaction</span>
            </button>
        </div>
    </div>

    <!-- 1. TAB: General Ledger -->
    <div id="tab-ledger" class="tab-content space-y-6">
        <div class="app-card rounded-2xl overflow-hidden shadow-xs border border-slate-205 dark:border-slate-800">
            <div class="bg-slate-50 dark:bg-slate-900/50 px-6 py-4 border-b border-slate-200 dark:border-slate-800">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-550">General Ledger Transaction Log</h3>
            </div>
            
            <div class="divide-y divide-slate-200 dark:divide-slate-800">
                @forelse($journalEntries as $entry)
                    <div class="p-6 space-y-3 hover:bg-slate-100/10 transition">
                        <!-- Header information -->
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-xs font-bold text-slate-400 font-mono">{{ $entry->entry_date->format('Y-m-d') }}</span>
                                <h4 class="font-bold text-sm text-slate-800 dark:text-slate-200 mt-0.5">{{ $entry->description }}</h4>
                            </div>
                            @if($entry->reference)
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-100 dark:bg-slate-800 text-slate-650 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                    Ref: {{ $entry->reference }}
                                </span>
                            @endif
                        </div>

                        <!-- Double Entry lines -->
                        <div class="overflow-x-auto rounded-lg border border-slate-200/60 dark:border-slate-800/80 bg-white/50 dark:bg-slate-950/20">
                            <table class="w-full text-left border-collapse text-xs font-mono">
                                <thead>
                                    <tr class="bg-slate-100/40 dark:bg-slate-900/40 border-b border-slate-200 dark:border-slate-800/80 text-slate-500 font-semibold text-[10px]">
                                        <th class="py-2 px-4">Account Code</th>
                                        <th class="py-2 px-4">Account Name</th>
                                        <th class="py-2 px-4">Customer Mobile</th>
                                        <th class="py-2 px-4 text-right">Debit</th>
                                        <th class="py-2 px-4 text-right">Credit</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-150 dark:divide-slate-850">
                                    @foreach($entry->items as $item)
                                        <tr>
                                            <td class="py-2 px-4 text-slate-450">{{ $item->account->code }}</td>
                                            <td class="py-2 px-4 font-semibold text-slate-700 dark:text-slate-300 {{ $item->credit > 0 ? 'pl-8' : '' }}">
                                                {{ $item->account->name }}
                                            </td>
                                            <td class="py-2 px-4 text-slate-500 font-mono">{{ $item->customer_mobile ?? '-' }}</td>
                                            <td class="py-2 px-4 text-right text-slate-800 dark:text-slate-200">
                                                {{ $item->debit > 0 ? number_format($item->debit, 2) : '' }}
                                            </td>
                                            <td class="py-2 px-4 text-right text-slate-800 dark:text-slate-200">
                                                {{ $item->credit > 0 ? number_format($item->credit, 2) : '' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="text-slate-500 text-sm py-12 text-center">
                        No transactions logged in the books yet.
                    </div>
                @endforelse
            </div>

            <!-- Pagination footer -->
            @if($journalEntries->hasPages())
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/40 border-t border-slate-200 dark:border-slate-800">
                    {{ $journalEntries->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- 2. TAB: Chart of Accounts -->
    <div id="tab-accounts" class="tab-content hidden space-y-6">
        <div class="flex justify-between items-center">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-550">Chart of Accounts & Balances</h3>
            <button onclick="document.getElementById('new-account-drawer').classList.remove('hidden')" 
                    class="px-4 py-2 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg text-xs transition flex items-center gap-1.5 shadow-sm">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Add Account</span>
            </button>
        </div>

        <div class="app-card rounded-2xl overflow-hidden shadow-xs border border-slate-200 dark:border-slate-800">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-100/60 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                        <th class="py-4 px-6">Account Code</th>
                        <th class="py-4 px-6">Account Name</th>
                        <th class="py-4 px-6">Type</th>
                        <th class="py-4 px-6">Description</th>
                        <th class="py-4 px-6 text-right">Net Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-850/60 font-mono text-xs">
                    @foreach($accounts as $acc)
                        <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-900/30 transition">
                            <td class="py-4 px-6 font-bold text-slate-800 dark:text-slate-200">{{ $acc->code }}</td>
                            <td class="py-4 px-6 font-bold text-slate-700 dark:text-slate-350 capitalize font-sans">{{ $acc->name }}</td>
                            <td class="py-4 px-6 capitalize font-sans">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold 
                                    {{ $acc->type === 'asset' ? 'bg-blue-500/10 text-blue-500 border border-blue-500/20' : '' }}
                                    {{ $acc->type === 'liability' ? 'bg-red-500/10 text-red-500 border border-red-500/20' : '' }}
                                    {{ $acc->type === 'equity' ? 'bg-indigo-500/10 text-indigo-500 border border-indigo-500/20' : '' }}
                                    {{ $acc->type === 'revenue' ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20' : '' }}
                                    {{ $acc->type === 'expense' ? 'bg-amber-500/10 text-amber-500 border border-amber-500/20' : '' }}
                                ">
                                    {{ $acc->type }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-slate-500 font-sans max-w-sm truncate">{{ $acc->description ?? '-' }}</td>
                            <td class="py-4 px-6 text-right font-bold text-slate-900 dark:text-slate-100">
                                {{ config('app.currency', '$') }}{{ number_format($acc->balance, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- 3. TAB: Customer Books -->
    <div id="tab-customers" class="tab-content hidden space-y-6">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-550">Customer Outstanding Accounts Receivable</h3>
        <p class="text-xs text-slate-500 -mt-4">Aggregated balances on AR Account (1200) filtered by customer mobile numbers.</p>

        <div class="app-card rounded-2xl overflow-hidden shadow-xs border border-slate-200 dark:border-slate-800">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-100/60 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                        <th class="py-4 px-6">Customer Mobile</th>
                        <th class="py-4 px-6 text-right">AR Balance (Outstanding)</th>
                        <th class="py-4 px-6 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-850/60 text-xs">
                    @forelse($customerBalances as $cust)
                        <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-900/30 transition">
                            <td class="py-4 px-6 font-mono font-bold text-slate-700 dark:text-slate-350">{{ $cust->customer_mobile }}</td>
                            <td class="py-4 px-6 text-right font-mono font-bold text-slate-800 dark:text-slate-200">
                                {{ config('app.currency', '$') }}{{ number_format($cust->balance, 2) }}
                            </td>
                            <td class="py-4 px-6 text-right font-semibold">
                                @if($cust->balance > 0)
                                    <span class="text-red-500 bg-red-500/10 px-2 py-0.5 rounded border border-red-500/20 text-[10px] font-bold">Unpaid / Owes Balance</span>
                                @elseif($cust->balance < 0)
                                    <span class="text-blue-500 bg-blue-500/10 px-2 py-0.5 rounded border border-blue-500/20 text-[10px] font-bold">Overpaid / Credit Balance</span>
                                @else
                                    <span class="text-green-600 bg-green-600/10 px-2 py-0.5 rounded border border-green-600/20 text-[10px] font-bold">Paid / Settle</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-8 text-center text-slate-500">
                                No billing records logged with customer mobile numbers yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 4. TAB: Investor Capital -->
    <div id="tab-investors" class="tab-content hidden space-y-6">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-550">Investor Capital Log</h3>
        <p class="text-xs text-slate-500 -mt-4">Capital logs tracked under Equity Account (3200: Investor Capital).</p>

        <div class="app-card rounded-2xl overflow-hidden shadow-xs border border-slate-200 dark:border-slate-800">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-100/60 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                        <th class="py-4 px-6">Date</th>
                        <th class="py-4 px-6">Entry Description</th>
                        <th class="py-4 px-6">Reference</th>
                        <th class="py-4 px-6 text-right">Contribution (Credit)</th>
                        <th class="py-4 px-6 text-right">Withdrawal (Debit)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-850/60 text-xs">
                    @forelse($investorTransactions as $txn)
                        <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-900/30 transition">
                            <td class="py-4 px-6 font-mono text-slate-500">{{ $txn->entry->entry_date->format('Y-m-d') }}</td>
                            <td class="py-4 px-6 font-bold text-slate-700 dark:text-slate-350">{{ $txn->entry->description }}</td>
                            <td class="py-4 px-6 font-mono text-slate-500">{{ $txn->entry->reference ?? '-' }}</td>
                            <td class="py-4 px-6 text-right font-mono font-bold text-green-600">
                                {{ $txn->credit > 0 ? config('app.currency', '$') . number_format($txn->credit, 2) : '-' }}
                            </td>
                            <td class="py-4 px-6 text-right font-mono font-bold text-red-500">
                                {{ $txn->debit > 0 ? config('app.currency', '$') . number_format($txn->debit, 2) : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-500">
                                No capital contributions recorded under account 3200 yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 5. TAB: Data Exports -->
    <div id="tab-exports" class="tab-content hidden space-y-6">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-550">Accounting Books Data Export</h3>
        <p class="text-xs text-slate-500 -mt-4">Export bookkeeping logs directly to raw CSV formats for compatibility with other accounting software or sheets.</p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Account balances export -->
            <div class="app-card rounded-2xl p-6 shadow-xs border border-slate-200 dark:border-slate-800 space-y-4">
                <div class="flex items-center gap-2">
                    <i data-lucide="table" class="w-5 h-5 text-primary"></i>
                    <h4 class="font-bold text-sm text-slate-800 dark:text-slate-200">Chart of Accounts</h4>
                </div>
                <p class="text-xs text-slate-500 leading-normal">
                    Download the list of all seeded/created accounts with their net balances.
                </p>
                <a href="{{ route('finance.export.accounts') }}" 
                   class="inline-flex w-full justify-center items-center gap-1.5 px-4 py-2.5 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-250 rounded-lg text-xs font-bold transition">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    <span>Export Accounts (CSV)</span>
                </a>
            </div>

            <!-- Ledger lines export -->
            <div class="app-card rounded-2xl p-6 shadow-xs border border-slate-200 dark:border-slate-800 space-y-4">
                <div class="flex items-center gap-2">
                    <i data-lucide="book-open" class="w-5 h-5 text-primary"></i>
                    <h4 class="font-bold text-sm text-slate-800 dark:text-slate-200">General Ledger Books</h4>
                </div>
                <p class="text-xs text-slate-500 leading-normal">
                    Download all journal entries and debit/credit ledger lines with customer details.
                </p>
                <a href="{{ route('finance.export.ledger') }}" 
                   class="inline-flex w-full justify-center items-center gap-1.5 px-4 py-2.5 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-250 rounded-lg text-xs font-bold transition">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    <span>Export Ledger Log (CSV)</span>
                </a>
            </div>

            <!-- Customers AR export -->
            <div class="app-card rounded-2xl p-6 shadow-xs border border-slate-200 dark:border-slate-800 space-y-4">
                <div class="flex items-center gap-2">
                    <i data-lucide="users" class="w-5 h-5 text-primary"></i>
                    <h4 class="font-bold text-sm text-slate-800 dark:text-slate-200">Customer Balances</h4>
                </div>
                <p class="text-xs text-slate-500 leading-normal">
                    Download aggregated balances grouped by customer phone/mobile numbers.
                </p>
                <a href="{{ route('finance.export.customers') }}" 
                   class="inline-flex w-full justify-center items-center gap-1.5 px-4 py-2.5 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-250 rounded-lg text-xs font-bold transition">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    <span>Export Balances (CSV)</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ==================== DRAWERS ==================== -->

<!-- 1. DRAWER: New Journal Entry Form -->
<div id="new-journal-drawer" class="fixed inset-0 z-50 overflow-hidden hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute inset-0 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-xs transition-opacity" onclick="document.getElementById('new-journal-drawer').classList.add('hidden')"></div>
        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div class="pointer-events-auto w-screen max-w-2xl bg-white dark:bg-slate-900 shadow-2xl flex flex-col">
                <div class="px-6 py-5 bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                        <i data-lucide="book-open" class="w-4 h-4 text-primary"></i>
                        <span>New Double-Entry Journal Transaction</span>
                    </h2>
                    <button onclick="document.getElementById('new-journal-drawer').classList.add('hidden')" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <form action="{{ route('finance.ledger.store') }}" method="POST" class="flex-1 flex flex-col justify-between overflow-y-auto">
                    @csrf
                    <div class="p-6 space-y-6">
                        <!-- Top details -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="entry_date" class="block text-xs font-semibold text-slate-550 mb-1.5">Transaction Date *</label>
                                <input type="date" name="entry_date" id="entry_date" required value="{{ date('Y-m-d') }}"
                                       class="w-full px-3 py-2 app-input rounded-lg text-slate-850 dark:text-slate-200 focus:outline-none focus:border-primary text-xs">
                            </div>
                            <div>
                                <label for="reference" class="block text-xs font-semibold text-slate-550 mb-1.5">Reference / Doc No. (Optional)</label>
                                <input type="text" name="reference" id="reference" placeholder="e.g. BILL-1025, SH-CAPITAL"
                                       class="w-full px-3 py-2 app-input rounded-lg text-slate-850 dark:text-slate-200 focus:outline-none focus:border-primary text-xs">
                            </div>
                        </div>

                        <div>
                            <label for="description" class="block text-xs font-semibold text-slate-550 mb-1.5">Transaction Description *</label>
                            <input type="text" name="description" id="description" placeholder="e.g. Capital injection from John, Paid utility bill" required
                                   class="w-full px-3 py-2 app-input rounded-lg text-slate-850 dark:text-slate-200 focus:outline-none focus:border-primary text-xs">
                        </div>

                        <!-- Double Entry lines list -->
                        <div class="space-y-3 pt-2">
                            <div class="flex justify-between items-center border-b border-slate-200 dark:border-slate-800 pb-2">
                                <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wide">Journal Lines</h4>
                                <button type="button" onclick="addJournalLineRow()" 
                                        class="px-2.5 py-1.5 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-250 font-bold rounded-lg text-[10px] transition flex items-center gap-1">
                                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                    <span>Add Row</span>
                                </button>
                            </div>

                            <!-- Validation Balance indicator -->
                            <div id="unbalance-warning" class="bg-red-500/10 text-red-500 border border-red-500/20 text-[11px] p-2.5 rounded-lg flex items-center gap-1.5 font-semibold">
                                <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0"></i>
                                <span>Unbalanced! Total Debits must equal Total Credits. (Diff: <span id="balance-diff" class="font-mono">0.00</span>)</span>
                            </div>

                            <!-- Header -->
                            <div class="grid grid-cols-12 gap-2 text-[10px] font-bold text-slate-450 uppercase tracking-wider px-1">
                                <span class="col-span-4">Account *</span>
                                <span class="col-span-2 text-right">Debit *</span>
                                <span class="col-span-2 text-right">Credit *</span>
                                <span class="col-span-3">Cust Mobile</span>
                                <span class="col-span-1 text-center"></span>
                            </div>

                            <!-- Rows Container -->
                            <div id="journal-lines-container" class="space-y-2">
                                <!-- Line Row 1 (Asset Debit standard) -->
                                <div class="grid grid-cols-12 gap-2 items-center journal-row">
                                    <div class="col-span-4">
                                        <select name="lines[0][account_id]" required class="w-full px-2 py-1.5 app-input rounded-lg text-slate-850 dark:text-slate-200 text-xs">
                                            @foreach($accounts as $acc)
                                                <option value="{{ $acc->id }}" {{ $acc->code === '1000' ? 'selected' : '' }}>{{ $acc->code }} - {{ $acc->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-span-2">
                                        <input type="number" name="lines[0][debit]" step="0.01" min="0" value="0.00" oninput="calculateJournalTotals()"
                                               class="w-full px-2 py-1.5 app-input rounded-lg text-slate-850 dark:text-slate-200 text-right font-mono text-xs debit-input">
                                    </div>
                                    <div class="col-span-2">
                                        <input type="number" name="lines[0][credit]" step="0.01" min="0" value="0.00" oninput="calculateJournalTotals()"
                                               class="w-full px-2 py-1.5 app-input rounded-lg text-slate-850 dark:text-slate-200 text-right font-mono text-xs credit-input">
                                    </div>
                                    <div class="col-span-3">
                                        <input type="text" name="lines[0][customer_mobile]" placeholder="e.g. 94771112222"
                                               class="w-full px-2 py-1.5 app-input rounded-lg text-slate-850 dark:text-slate-200 text-xs">
                                    </div>
                                    <div class="col-span-1 text-center"></div>
                                </div>

                                <!-- Line Row 2 (Equity Credit standard) -->
                                <div class="grid grid-cols-12 gap-2 items-center journal-row">
                                    <div class="col-span-4">
                                        <select name="lines[1][account_id]" required class="w-full px-2 py-1.5 app-input rounded-lg text-slate-850 dark:text-slate-200 text-xs">
                                            @foreach($accounts as $acc)
                                                <option value="{{ $acc->id }}" {{ $acc->code === '3200' ? 'selected' : '' }}>{{ $acc->code }} - {{ $acc->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-span-2">
                                        <input type="number" name="lines[1][debit]" step="0.01" min="0" value="0.00" oninput="calculateJournalTotals()"
                                               class="w-full px-2 py-1.5 app-input rounded-lg text-slate-850 dark:text-slate-200 text-right font-mono text-xs debit-input">
                                    </div>
                                    <div class="col-span-2">
                                        <input type="number" name="lines[1][credit]" step="0.01" min="0" value="0.00" oninput="calculateJournalTotals()"
                                               class="w-full px-2 py-1.5 app-input rounded-lg text-slate-850 dark:text-slate-200 text-right font-mono text-xs credit-input">
                                    </div>
                                    <div class="col-span-3">
                                        <input type="text" name="lines[1][customer_mobile]" placeholder="e.g. 94771112222"
                                               class="w-full px-2 py-1.5 app-input rounded-lg text-slate-850 dark:text-slate-200 text-xs">
                                    </div>
                                    <div class="col-span-1 text-center"></div>
                                </div>
                            </div>

                            <!-- Totals footer summary inside drawer -->
                            <div class="grid grid-cols-12 gap-2 font-mono text-xs font-bold border-t border-slate-200 dark:border-slate-800 pt-3 px-1">
                                <span class="col-span-4 text-right">Totals:</span>
                                <span class="col-span-2 text-right text-slate-850 dark:text-slate-200" id="total-debit-val">0.00</span>
                                <span class="col-span-2 text-right text-slate-850 dark:text-slate-200" id="total-credit-val">0.00</span>
                                <span class="col-span-4"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer actions -->
                    <div class="p-6 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3">
                        <button type="button" onclick="document.getElementById('new-journal-drawer').classList.add('hidden')"
                                class="px-4 py-2.5 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-lg text-xs hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            Cancel
                        </button>
                        <button type="submit" id="submit-journal-btn" disabled
                                class="px-4 py-2.5 bg-primary hover:bg-primary-hover disabled:bg-slate-350 dark:disabled:bg-slate-800 disabled:text-slate-500 disabled:cursor-not-allowed text-white font-bold rounded-lg text-xs transition flex items-center gap-1.5 shadow-sm">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>Save Transaction</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 2. DRAWER: New Account Form -->
<div id="new-account-drawer" class="fixed inset-0 z-50 overflow-hidden hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute inset-0 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-xs transition-opacity" onclick="document.getElementById('new-account-drawer').classList.add('hidden')"></div>
        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div class="pointer-events-auto w-screen max-w-md bg-white dark:bg-slate-900 shadow-2xl flex flex-col">
                <div class="px-6 py-5 bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                        <i data-lucide="table" class="w-4 h-4 text-primary"></i>
                        <span>Create New Ledger Account</span>
                    </h2>
                    <button onclick="document.getElementById('new-account-drawer').classList.add('hidden')" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <form action="{{ route('finance.accounts.store') }}" method="POST" class="flex-1 flex flex-col justify-between overflow-y-auto">
                    @csrf
                    <div class="p-6 space-y-4">
                        <div>
                            <label for="code" class="block text-xs font-semibold text-slate-550 mb-1.5">Account Code *</label>
                            <input type="text" name="code" id="code" required placeholder="e.g. 1010, 5210"
                                   class="w-full px-3 py-2 app-input rounded-lg text-slate-850 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono">
                        </div>

                        <div>
                            <label for="name" class="block text-xs font-semibold text-slate-550 mb-1.5">Account Name *</label>
                            <input type="text" name="name" id="name" required placeholder="e.g. Petty Cash, Office Utilities"
                                   class="w-full px-3 py-2 app-input rounded-lg text-slate-850 dark:text-slate-200 focus:outline-none focus:border-primary text-xs">
                        </div>

                        <div>
                            <label for="type" class="block text-xs font-semibold text-slate-550 mb-1.5">Account Type *</label>
                            <select name="type" id="type" required class="w-full px-3 py-2 app-input rounded-lg text-slate-850 dark:text-slate-200 focus:outline-none focus:border-primary text-xs">
                                <option value="asset">Asset (Normal Balance: Debit)</option>
                                <option value="liability">Liability (Normal Balance: Credit)</option>
                                <option value="equity">Equity (Normal Balance: Credit)</option>
                                <option value="revenue">Revenue (Normal Balance: Credit)</option>
                                <option value="expense">Expense (Normal Balance: Debit)</option>
                            </select>
                        </div>

                        <div>
                            <label for="description" class="block text-xs font-semibold text-slate-550 mb-1.5">Description (Optional)</label>
                            <textarea name="description" id="description" rows="3" placeholder="Explain the purpose of this account..."
                                      class="w-full px-3 py-2 app-input rounded-lg text-slate-850 dark:text-slate-200 focus:outline-none focus:border-primary text-xs"></textarea>
                        </div>
                    </div>

                    <!-- Footer actions -->
                    <div class="p-6 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3">
                        <button type="button" onclick="document.getElementById('new-account-drawer').classList.add('hidden')"
                                class="px-4 py-2.5 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-lg text-xs hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2.5 bg-primary hover:bg-primary-hover text-white font-bold rounded-lg text-xs transition flex items-center gap-1.5 shadow-sm">
                            <i data-lucide="check" class="w-4 h-4"></i>
                            <span>Create Account</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Dynamic rows generation script for Journal Entry lines -->
<script>
    let rowIndex = 2; // Rows 0 and 1 pre-exist

    function addJournalLineRow() {
        const container = document.getElementById('journal-lines-container');
        const rowId = rowIndex++;

        const div = document.createElement('div');
        div.className = 'grid grid-cols-12 gap-2 items-center journal-row';
        div.innerHTML = `
            <div class="col-span-4">
                <select name="lines[${rowId}][account_id]" required class="w-full px-2 py-1.5 app-input rounded-lg text-slate-850 dark:text-slate-200 text-xs">
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-2">
                <input type="number" name="lines[${rowId}][debit]" step="0.01" min="0" value="0.00" oninput="calculateJournalTotals()"
                       class="w-full px-2 py-1.5 app-input rounded-lg text-slate-850 dark:text-slate-200 text-right font-mono text-xs debit-input">
            </div>
            <div class="col-span-2">
                <input type="number" name="lines[${rowId}][credit]" step="0.01" min="0" value="0.00" oninput="calculateJournalTotals()"
                       class="w-full px-2 py-1.5 app-input rounded-lg text-slate-850 dark:text-slate-200 text-right font-mono text-xs credit-input">
            </div>
            <div class="col-span-3">
                <input type="text" name="lines[${rowId}][customer_mobile]" placeholder="e.g. 94771112222"
                       class="w-full px-2 py-1.5 app-input rounded-lg text-slate-850 dark:text-slate-200 text-xs">
            </div>
            <div class="col-span-1 text-center">
                <button type="button" onclick="removeJournalRow(this)" class="text-red-500 hover:text-red-650 p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        `;
        container.appendChild(div);
        calculateJournalTotals();
    }

    function removeJournalRow(button) {
        button.closest('.journal-row').remove();
        calculateJournalTotals();
    }

    function calculateJournalTotals() {
        let totalDebit = 0;
        let totalCredit = 0;

        document.querySelectorAll('.debit-input').forEach(input => {
            totalDebit += parseFloat(input.value) || 0;
        });

        document.querySelectorAll('.credit-input').forEach(input => {
            totalCredit += parseFloat(input.value) || 0;
        });

        document.getElementById('total-debit-val').innerText = totalDebit.toFixed(2);
        document.getElementById('total-credit-val').innerText = totalCredit.toFixed(2);

        const diff = Math.abs(totalDebit - totalCredit);
        const warnBox = document.getElementById('unbalance-warning');
        const submitBtn = document.getElementById('submit-journal-btn');

        if (diff > 0.001) {
            warnBox.classList.remove('hidden');
            document.getElementById('balance-diff').innerText = diff.toFixed(2);
            submitBtn.disabled = true;
        } else {
            // Must have transaction volume > 0 to save
            if (totalDebit > 0) {
                warnBox.classList.add('hidden');
                submitBtn.disabled = false;
            } else {
                warnBox.classList.remove('hidden');
                document.getElementById('balance-diff').innerText = 'Volume must be > 0.00';
                submitBtn.disabled = true;
            }
        }
    }

    // Tabs Manager
    function switchTab(tabId) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        
        // Remove active state classes
        const normalClass = "px-4 py-2 font-semibold text-sm border-b-2 border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-350 transition";
        document.getElementById('btn-tab-ledger').className = normalClass;
        document.getElementById('btn-tab-accounts').className = normalClass;
        document.getElementById('btn-tab-customers').className = normalClass;
        document.getElementById('btn-tab-investors').className = normalClass;
        document.getElementById('btn-tab-exports').className = normalClass;

        // Show target and activate
        document.getElementById(tabId).classList.remove('hidden');
        document.getElementById('btn-' + tabId).className = "px-4 py-2 font-semibold text-sm border-b-2 border-primary text-primary transition";

        // Save active tab
        localStorage.setItem('accounting_active_tab', tabId);
    }

    // Restore tab
    document.addEventListener('DOMContentLoaded', () => {
        const activeTab = localStorage.getItem('accounting_active_tab') || 'tab-ledger';
        switchTab(activeTab);
        calculateJournalTotals();
    });
</script>
@endsection
