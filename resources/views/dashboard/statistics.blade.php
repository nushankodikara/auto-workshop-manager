@extends('layouts.app')

@section('title', 'Statistics & Finance')

@section('content')
<div class="space-y-8">

    <!-- Top Date Filter Bar -->
    <div class="app-card rounded-2xl p-4 shadow-xs">
        <form action="{{ route('dashboard.statistics') }}" method="GET" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                <i data-lucide="filter" class="w-4 h-4 text-primary"></i>
                <span>Filter Financial Range</span>
            </span>

            <div class="flex items-center gap-2">
                <input type="date" name="start_date" value="{{ $startDate }}"
                       class="px-3 py-1.5 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary">
                <span class="text-xs text-slate-400">to</span>
                <input type="date" name="end_date" value="{{ $endDate }}"
                       class="px-3 py-1.5 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary">
                <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg text-xs transition shadow-sm">
                    Apply Filter
                </button>
                @if($startDate || $endDate)
                    <a href="{{ route('dashboard.statistics') }}" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold rounded-lg text-xs transition">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- High Level Cash Flow Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Card 1: Total Cash Income -->
        <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
            <div class="flex justify-between items-start">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                        <i data-lucide="arrow-up-right" class="w-4 h-4 text-green-500"></i>
                        <span>Total Income</span>
                    </span>
                    <h3 class="text-3xl font-bold mt-3 text-slate-800 dark:text-slate-100 font-mono">
                        {{ config('app.currency', 'Rs.') }} {{ number_format($totalIncome, 2) }}
                    </h3>
                </div>
            </div>
            <p class="text-xs text-slate-505 dark:text-slate-400 leading-normal">
                Sum of all paid client invoices within this period.
            </p>
        </div>

        <!-- Card 2: Total Cash Expenditure -->
        <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
            <div class="flex justify-between items-start">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                        <i data-lucide="arrow-down-left" class="w-4 h-4 text-red-500"></i>
                        <span>Total Expenditure</span>
                    </span>
                    <h3 class="text-3xl font-bold mt-3 text-slate-800 dark:text-slate-100 font-mono">
                        {{ config('app.currency', 'Rs.') }} {{ number_format($totalExpenditure, 2) }}
                    </h3>
                </div>
            </div>
            <p class="text-xs text-slate-505 dark:text-slate-400 leading-normal">
                Sum of parts inventory batch purchases and paid employee payroll.
            </p>
        </div>

        <!-- Card 3: Net Cash Profit -->
        <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
            <div class="flex justify-between items-start">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                        <i data-lucide="trending-up" class="w-4 h-4 text-primary"></i>
                        <span>Net Profit</span>
                    </span>
                    <h3 class="text-3xl font-bold mt-3 font-mono {{ $netProfit >= 0 ? 'text-primary dark:text-primary' : 'text-red-500' }}">
                        {{ config('app.currency', 'Rs.') }} {{ number_format($netProfit, 2) }}
                    </h3>
                </div>
            </div>
            <p class="text-xs text-slate-505 dark:text-slate-400 leading-normal">
                Net cash flow after subtracting expenditures from total income.
            </p>
        </div>

    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Expenditure Breakdown Details -->
        <div class="app-card rounded-2xl p-6 space-y-6 shadow-xs">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-1.5">
                <i data-lucide="pie-chart" class="w-4 h-4 text-primary"></i>
                <span>Expenditure Details</span>
            </h3>

            <div class="space-y-4">
                <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-500">Parts Batch Purchases (Total Cost)</span>
                    <span class="font-bold text-slate-750 dark:text-slate-200 font-mono">
                        {{ config('app.currency', 'Rs.') }} {{ number_format($totalStockPurchases, 2) }}
                    </span>
                </div>
                <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-500">Employee Salaries (Paid Basic)</span>
                    <span class="font-bold text-slate-750 dark:text-slate-200 font-mono">
                        {{ config('app.currency', 'Rs.') }} {{ number_format($paidBasicSalaries, 2) }}
                    </span>
                </div>
                <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-500">Employee Allowances (Paid)</span>
                    <span class="font-bold text-slate-750 dark:text-slate-200 font-mono">
                        {{ config('app.currency', 'Rs.') }} {{ number_format($paidAllowances, 2) }}
                    </span>
                </div>
                <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-500">Total Payroll Disbursements</span>
                    <span class="font-bold text-slate-750 dark:text-slate-200 font-mono">
                        {{ config('app.currency', 'Rs.') }} {{ number_format($totalPayroll, 2) }}
                    </span>
                </div>

                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-between items-center text-sm font-bold">
                    <span class="text-slate-705 dark:text-slate-350">Total Expenditure</span>
                    <span class="font-mono text-red-500">
                        {{ config('app.currency', 'Rs.') }} {{ number_format($totalExpenditure, 2) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Trading Profitability & Margins (COGS) -->
        <div class="app-card rounded-2xl p-6 space-y-6 shadow-xs">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-1.5">
                <i data-lucide="percent" class="w-4 h-4 text-primary"></i>
                <span>Trading Profitability & Margins (COGS)</span>
            </h3>

            <div class="space-y-6">
                <!-- Parts Segment -->
                <div class="space-y-2">
                    <div class="flex justify-between items-center text-xs font-bold text-slate-600 dark:text-slate-300">
                        <span>Parts Segment</span>
                        <span class="font-mono text-primary bg-primary/10 dark:bg-primary/20 px-2 py-0.5 rounded text-[11px]">
                            Margin: {{ number_format($partsMargin, 1) }}%
                        </span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center text-[11px] bg-slate-50 dark:bg-slate-950/40 rounded-xl p-3 border border-slate-200 dark:border-slate-850/60">
                        <div>
                            <span class="text-slate-500 block">Revenue</span>
                            <span class="font-semibold font-mono text-slate-700 dark:text-slate-300">
                                {{ number_format($partsRevenue, 2) }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">COGS (Cost)</span>
                            <span class="font-semibold font-mono text-red-550 dark:text-red-400">
                                {{ number_format($partsCOGS, 2) }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Gross Profit</span>
                            <span class="font-semibold font-mono text-green-600 dark:text-green-400">
                                {{ number_format($partsProfit, 2) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Labor Segment -->
                <div class="space-y-2">
                    <div class="flex justify-between items-center text-xs font-bold text-slate-600 dark:text-slate-300">
                        <span>Labor Segment</span>
                        <span class="font-mono text-primary bg-primary/10 dark:bg-primary/20 px-2 py-0.5 rounded text-[11px]">
                            Margin: {{ number_format($laborMargin, 1) }}%
                        </span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center text-[11px] bg-slate-50 dark:bg-slate-950/40 rounded-xl p-3 border border-slate-200 dark:border-slate-855">
                        <div>
                            <span class="text-slate-500 block">Revenue</span>
                            <span class="font-semibold font-mono text-slate-700 dark:text-slate-300">
                                {{ number_format($laborRevenue, 2) }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Direct Cost</span>
                            <span class="font-semibold font-mono text-red-550 dark:text-red-400">
                                {{ number_format($laborCOGS, 2) }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Gross Profit</span>
                            <span class="font-semibold font-mono text-green-600 dark:text-green-400">
                                {{ number_format($laborProfit, 2) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Outsourcing Segment -->
                <div class="space-y-2">
                    <div class="flex justify-between items-center text-xs font-bold text-slate-600 dark:text-slate-300">
                        <span>Specialist Services (Outsourced)</span>
                        <span class="font-mono text-primary bg-primary/10 dark:bg-primary/20 px-2 py-0.5 rounded text-[11px]">
                            Margin: {{ number_format($outsourcingMargin, 1) }}%
                        </span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center text-[11px] bg-slate-50 dark:bg-slate-950/40 rounded-xl p-3 border border-slate-200 dark:border-slate-855">
                        <div>
                            <span class="text-slate-500 block">Revenue</span>
                            <span class="font-semibold font-mono text-slate-700 dark:text-slate-300">
                                {{ number_format($outsourcingRevenue, 2) }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Direct Cost</span>
                            <span class="font-semibold font-mono text-red-550 dark:text-red-400">
                                {{ number_format($outsourcingCOGS, 2) }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Gross Profit</span>
                            <span class="font-semibold font-mono text-green-600 dark:text-green-400">
                                {{ number_format($outsourcingProfit, 2) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Overall Segment Summary -->
                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 space-y-2">
                    <div class="flex justify-between items-center text-xs font-bold text-slate-700 dark:text-slate-200">
                        <span>Overall Trading Performance</span>
                        <span class="font-mono text-white bg-primary px-2.5 py-0.5 rounded-full text-[11px] shadow-sm">
                            Avg Margin: {{ number_format($tradingMargin, 1) }}%
                        </span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center text-[11px] bg-primary/5 dark:bg-primary/10 rounded-xl p-3 border border-primary/10">
                        <div>
                            <span class="text-slate-500 block">Total Revenue</span>
                            <span class="font-bold font-mono text-slate-800 dark:text-slate-200">
                                {{ number_format($tradingRevenue, 2) }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Total COGS</span>
                            <span class="font-bold font-mono text-red-500">
                                {{ number_format($tradingCOGS, 2) }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Total Gross Profit</span>
                            <span class="font-bold font-mono text-primary">
                                {{ number_format($tradingProfit, 2) }}
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

</div>
@endsection
