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
                <input type="date" name="start_date" id="global_start_date" value="{{ $startDate }}"
                       class="px-3 py-1.5 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary">
                <span class="text-xs text-slate-400">to</span>
                <input type="date" name="end_date" id="global_end_date" value="{{ $endDate }}"
                       class="px-3 py-1.5 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary">
                <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg text-xs transition shadow-sm cursor-pointer">
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

    <!-- Tab Navigation (3 Streamlined Tabs) -->
    <div class="flex border-b border-slate-200 dark:border-slate-800 gap-2">
        <button onclick="switchStatsTab('dashboard-overview')" id="btn-tab-overview" class="px-4 py-2.5 font-semibold text-xs border-b-2 border-primary text-primary transition uppercase tracking-wider cursor-pointer flex items-center gap-2">
            <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
            <span>Overview & Cash Flow</span>
        </button>
        <button onclick="switchStatsTab('dashboard-calendar')" id="btn-tab-calendar" class="px-4 py-2.5 font-semibold text-xs border-b-2 border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300 transition uppercase tracking-wider cursor-pointer flex items-center gap-2">
            <i data-lucide="calendar" class="w-4 h-4"></i>
            <span>Activity Calendar</span>
        </button>
        <button onclick="switchStatsTab('dashboard-jobcards')" id="btn-tab-jobcards" class="px-4 py-2.5 font-semibold text-xs border-b-2 border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300 transition uppercase tracking-wider cursor-pointer flex items-center gap-2">
            <i data-lucide="clipboard-list" class="w-4 h-4"></i>
            <span>Job Cards Detail</span>
        </button>
    </div>

    <!-- 1. TAB: Overview & Cash Flow -->
    <div id="dashboard-overview" class="tab-content space-y-8">
        
        <!-- High Level Cash Flow Metrics Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

            <!-- Card 1: Total Cash In -->
            <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs border-l-4 border-l-emerald-500">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                            <i data-lucide="arrow-up-right" class="w-4 h-4 text-emerald-500"></i>
                            <span>Total Cash In</span>
                        </span>
                        <h3 class="text-3xl font-bold mt-3 text-slate-800 dark:text-slate-100 font-mono">
                            {{ config('app.currency', 'Rs.') }} {{ number_format($totalIncome, 2) }}
                        </h3>
                    </div>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-normal">
                    Total revenue collected from paid client invoices in this timeframe.
                </p>
            </div>

            <!-- Card 2: Total Cash Out -->
            <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs border-l-4 border-l-rose-500">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                            <i data-lucide="arrow-down-left" class="w-4 h-4 text-rose-500"></i>
                            <span>Total Cash Out</span>
                        </span>
                        <h3 class="text-3xl font-bold mt-3 text-slate-800 dark:text-slate-100 font-mono">
                            {{ config('app.currency', 'Rs.') }} {{ number_format($totalExpenditure, 2) }}
                        </h3>
                    </div>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-normal">
                    All business outflows: direct parts, outsourcing, stock, payroll & overheads.
                </p>
            </div>

            <!-- Card 3: Net Cash Profit -->
            <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs border-l-4 {{ $netProfit >= 0 ? 'border-l-primary' : 'border-l-rose-500' }}">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                            <i data-lucide="trending-up" class="w-4 h-4 {{ $netProfit >= 0 ? 'text-primary' : 'text-rose-500' }}"></i>
                            <span>Net Cash Flow</span>
                        </span>
                        <h3 class="text-3xl font-bold mt-3 font-mono {{ $netProfit >= 0 ? 'text-primary dark:text-primary' : 'text-rose-500' }}">
                            {{ config('app.currency', 'Rs.') }} {{ number_format($netProfit, 2) }}
                        </h3>
                    </div>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-normal">
                    Net cash margin (Total Cash In minus Total Cash Out).
                </p>
            </div>

            <!-- Card 4: Daily Break-Even Target -->
            <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs border border-primary/20 bg-primary/[0.02]">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                            <i data-lucide="target" class="w-4 h-4 text-primary"></i>
                            <span>Daily Break-Even</span>
                        </span>
                        <h3 class="text-3xl font-bold mt-3 text-slate-800 dark:text-slate-100 font-mono">
                            {{ config('app.currency', 'Rs.') }} {{ number_format($dailyBreakEvenTarget, 2) }}
                        </h3>
                    </div>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 leading-normal">
                    Required daily revenue to cover {{ $breakEvenMonthName }} fixed costs & overheads.
                </p>
            </div>

        </div>

        <!-- Cash Flow Timeline Chart -->
        <div class="app-card rounded-2xl p-6 shadow-xs space-y-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-200 dark:border-slate-800 pb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <i data-lucide="bar-chart-3" class="w-5 h-5 text-primary"></i>
                        <span>Cash Flow & Performance Timeline</span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-normal">
                        Unified visualization of daily Cash In, Cash Out, and Net Cash Flow matching the ledger and calendar.
                    </p>
                </div>
                
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2 bg-slate-100 dark:bg-slate-800/80 p-1 rounded-xl">
                        <span class="text-[11px] font-semibold text-slate-500 px-2">Group By:</span>
                        <select id="chartFrequency" onchange="updateChart()" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 px-2.5 py-1 rounded-lg text-xs font-medium text-slate-700 dark:text-slate-300 focus:outline-none cursor-pointer">
                            <option value="days" selected>Daily</option>
                            <option value="weeks">Weekly</option>
                            <option value="months">Monthly</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2 bg-slate-100 dark:bg-slate-800/80 p-1 rounded-xl">
                        <span class="text-[11px] font-semibold text-slate-500 px-2">Type:</span>
                        <select id="chartStyle" onchange="updateChart()" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 px-2.5 py-1 rounded-lg text-xs font-medium text-slate-700 dark:text-slate-300 focus:outline-none cursor-pointer">
                            <option value="bar" selected>Bar Comparison</option>
                            <option value="line">Line Trend</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Legend Pills -->
            <div class="flex flex-wrap items-center gap-4 text-xs font-semibold">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <span>Cash In (Income)</span>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-500/10 text-rose-500">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                    <span>Cash Out (Expenditure)</span>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                    <span>Net Cash Flow</span>
                </span>
            </div>

            <!-- Render Target Canvas -->
            <div class="relative w-full h-80 pt-2">
                <canvas id="financialChart"></canvas>
            </div>
        </div>

        <!-- Structured Expenditure & Outflow Breakdown -->
        <div class="app-card rounded-2xl p-6 shadow-xs space-y-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-200 dark:border-slate-800 pb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <i data-lucide="receipt" class="w-5 h-5 text-rose-500"></i>
                        <span>Expenditure Overview & Cost Structure</span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-normal">
                        All business costs and outgoing transactions itemized under this section, boiling up to Total Cash Out.
                    </p>
                </div>
                <div class="text-right">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Total Outflow</span>
                    <span class="text-lg font-bold font-mono text-rose-500">
                        {{ config('app.currency', 'Rs.') }} {{ number_format($totalExpenditure, 2) }}
                    </span>
                </div>
            </div>

            <!-- Main Expenditure Category Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-100/70 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                            <th class="py-3 px-4">Cost Category</th>
                            <th class="py-3 px-4">Account Code</th>
                            <th class="py-3 px-4">Transaction Details</th>
                            <th class="py-3 px-4 text-right">Amount</th>
                            <th class="py-3 px-4 text-right">Share (%)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @foreach($expenditureBreakdown as $cat)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-900/20 transition">
                                <td class="py-3 px-4 font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                    <div class="p-1.5 rounded-lg bg-rose-500/10 text-rose-500">
                                        <i data-lucide="{{ $cat['icon'] }}" class="w-4 h-4"></i>
                                    </div>
                                    <span>{{ $cat['category'] }}</span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded font-mono text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-bold">
                                        {{ $cat['code'] }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-500 dark:text-slate-400 text-xs">
                                    {{ $cat['description'] }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-slate-800 dark:text-slate-200">
                                    {{ config('app.currency', 'Rs.') }} {{ number_format($cat['amount'], 2) }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-xs">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="w-16 h-1.5 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                                            <div class="h-full bg-rose-500 rounded-full" style="width: {{ min(100, $cat['percentage']) }}%"></div>
                                        </div>
                                        <span class="w-12 text-slate-600 dark:text-slate-400 font-semibold">{{ number_format($cat['percentage'], 1) }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-50 dark:bg-slate-900/50 font-bold border-t-2 border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-100">
                            <td colspan="3" class="py-3 px-4 uppercase text-[11px] tracking-wider">
                                Total Consolidated Cash Out
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-sm text-rose-500">
                                {{ config('app.currency', 'Rs.') }} {{ number_format($totalExpenditure, 2) }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-xs text-rose-500">
                                100.0%
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Operating Expenses Detail Sub-Table (if any overhead accounts logged) -->
            @if($expendituresByAccount->count() > 0)
                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 space-y-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">
                        Itemized Operating Overhead Accounts:
                    </span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($expendituresByAccount as $acc)
                            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800 flex justify-between items-center text-xs">
                                <div>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 block">{{ $acc->name }}</span>
                                    <span class="text-[10px] text-slate-400 font-mono">Code {{ $acc->code }} • {{ $acc->count }} {{ Str::plural('entry', $acc->count) }}</span>
                                </div>
                                <span class="font-mono font-bold text-rose-500">
                                    {{ config('app.currency', 'Rs.') }} {{ number_format($acc->total, 2) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

    </div>

    <!-- 2. TAB: Activity Calendar -->
    <div id="dashboard-calendar" class="tab-content hidden space-y-6">
        <div class="app-card rounded-2xl p-6 shadow-xs space-y-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-2">
                        <i data-lucide="calendar" class="w-4 h-4 text-primary"></i>
                        <span>Activity & Cash Calendar</span>
                    </h3>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 leading-normal">Daily breakdown for job cards, cash in, and cash outflow.</p>
                </div>
                <div class="flex items-center gap-2">
                    <select id="calendarMonth" onchange="renderCalendar()" class="px-2.5 py-1.5 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary cursor-pointer">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ sprintf('%02d', $m) }}" {{ $m == date('m') ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                        @endfor
                    </select>
                    <select id="calendarYear" onchange="renderCalendar()" class="px-2.5 py-1.5 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary cursor-pointer">
                        @for($y = date('Y') - 2; $y <= date('Y') + 2; $y++)
                            <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            
            <!-- Calendar headers -->
            <div class="grid grid-cols-7 gap-2 text-center text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-200 dark:border-slate-800 pb-2">
                <div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div><div>Sun</div>
            </div>
            
            <!-- Calendar days grid -->
            <div id="calendar-days-grid" class="grid grid-cols-7 gap-2.5">
                <!-- Dynamically built by JS -->
            </div>
        </div>

        <!-- Weekly & Monthly Summaries -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="app-card rounded-2xl p-6 shadow-xs space-y-4">
                <h4 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800 pb-2 flex items-center gap-1.5">
                    <i data-lucide="calendar-range" class="w-4 h-4 text-primary"></i>
                    <span>Weekly Performance Summaries</span>
                </h4>
                <div id="weekly-summary-list" class="divide-y divide-slate-200 dark:divide-slate-800 text-xs max-h-[300px] overflow-y-auto pr-1">
                    <!-- Dynamically populated -->
                </div>
            </div>
            
            <div class="app-card rounded-2xl p-6 shadow-xs space-y-4">
                <h4 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-800 pb-2 flex items-center gap-1.5">
                    <i data-lucide="layers" class="w-4 h-4 text-primary"></i>
                    <span>Monthly Performance Summaries</span>
                </h4>
                <div id="monthly-summary-list" class="divide-y divide-slate-200 dark:divide-slate-800 text-xs max-h-[300px] overflow-y-auto pr-1">
                    <!-- Dynamically populated -->
                </div>
            </div>
        </div>
    </div>

    <!-- 3. TAB: Job Cards Detail (NO LABOUR) -->
    <div id="dashboard-jobcards" class="tab-content hidden space-y-6">
        @php
            $statusBadges = [
                'received-vehicle' => ['label' => 'Received', 'badge' => 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400'],
                'on-going' => ['label' => 'Ongoing', 'badge' => 'bg-primary/10 text-primary'],
                'blocked' => ['label' => 'Blocked', 'badge' => 'bg-red-500/10 text-red-500'],
                'testing' => ['label' => 'Testing', 'badge' => 'bg-indigo-500/10 text-indigo-500'],
                'waiting-to-pickup' => ['label' => 'Ready', 'badge' => 'bg-green-500/10 text-green-600 dark:text-green-400'],
            ];
        @endphp

        @if(empty($formattedJobCardsByDay))
            <div class="text-slate-500 text-sm py-12 text-center bg-slate-50 dark:bg-slate-900/20 rounded-xl border border-slate-200 dark:border-slate-800 border-dashed">
                No Job Cards found in the selected date range.
            </div>
        @else
            @foreach($formattedJobCardsByDay as $dateStr => $dayJobs)
                @php
                    $dayIncome = collect($dayJobs)->sum('income');
                    $dayOutgoing = collect($dayJobs)->sum('outgoing');
                    $dayProfit = $dayIncome - $dayOutgoing;
                @endphp
                <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs border border-slate-200 dark:border-slate-800">
                    <!-- Day Header Card -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-200 dark:border-slate-800 pb-3">
                        <div>
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                <i data-lucide="calendar" class="w-4 h-4 text-primary"></i>
                                <span>{{ \Carbon\Carbon::parse($dateStr)->format('F d, Y') }}</span>
                                <span class="text-xs font-normal text-slate-500">({{ count($dayJobs) }} Job Cards)</span>
                            </h3>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 text-xs font-mono font-semibold">
                            <span class="text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-2.5 py-1 rounded">
                                In: {{ config('app.currency', 'Rs.') }} {{ number_format($dayIncome, 2) }}
                            </span>
                            <span class="text-rose-500 bg-rose-500/10 px-2.5 py-1 rounded">
                                Out: {{ config('app.currency', 'Rs.') }} {{ number_format($dayOutgoing, 2) }}
                            </span>
                            <span class="{{ $dayProfit >= 0 ? 'text-primary' : 'text-rose-500' }} bg-primary/10 px-2.5 py-1 rounded">
                                Net: {{ config('app.currency', 'Rs.') }} {{ number_format($dayProfit, 2) }}
                            </span>
                        </div>
                    </div>

                    <!-- Job Cards Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-100/60 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                                    <th class="py-3 px-4">Job Card No</th>
                                    <th class="py-3 px-4">Vehicle</th>
                                    <th class="py-3 px-4">Client</th>
                                    <th class="py-3 px-4">Status</th>
                                    <th class="py-3 px-4 text-right">Income</th>
                                    <th class="py-3 px-4 text-right">Parts Cost (Stock)</th>
                                    <th class="py-3 px-4 text-right">Parts Cost (Misc)</th>
                                    <th class="py-3 px-4 text-right">Outsource Cost</th>
                                    <th class="py-3 px-4 text-right">Consumables</th>
                                    <th class="py-3 px-4 text-right">Total Outgoing</th>
                                    <th class="py-3 px-4 text-right">Profit / Loss</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                @foreach($dayJobs as $job)
                                    <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-900/20 transition">
                                        <td class="py-3 px-4 font-mono font-bold text-primary">
                                            <a href="{{ route('job-cards.show', $job['id']) }}" class="hover:underline">
                                                {{ $job['card_number'] }}
                                            </a>
                                            @if($job['bill_number'])
                                                <span class="block text-[10px] font-normal text-slate-400">{{ $job['bill_number'] }}</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 font-medium text-slate-700 dark:text-slate-300">
                                            {{ $job['plate_number'] }}
                                        </td>
                                        <td class="py-3 px-4 text-slate-600 dark:text-slate-400">
                                            {{ $job['client_name'] }}
                                        </td>
                                        <td class="py-3 px-4">
                                            @php $badge = $statusBadges[$job['status']] ?? ['label' => ucfirst($job['status']), 'badge' => 'bg-slate-100 text-slate-600']; @endphp
                                            <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold {{ $badge['badge'] }}">
                                                {{ $badge['label'] }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-800 dark:text-slate-200">
                                            {{ $job['income'] > 0 ? 'Rs. ' . number_format($job['income'], 2) : '-' }}
                                        </td>
                                        <td class="py-3 px-4 text-right font-mono text-slate-500">
                                            {{ $job['parts_inventory'] > 0 ? 'Rs. ' . number_format($job['parts_inventory'], 2) : '-' }}
                                        </td>
                                        <td class="py-3 px-4 text-right font-mono text-slate-500">
                                            {{ $job['parts_misc'] > 0 ? 'Rs. ' . number_format($job['parts_misc'], 2) : '-' }}
                                        </td>
                                        <td class="py-3 px-4 text-right font-mono text-slate-500">
                                            {{ $job['outsourcing'] > 0 ? 'Rs. ' . number_format($job['outsourcing'], 2) : '-' }}
                                        </td>
                                        <td class="py-3 px-4 text-right font-mono text-slate-500">
                                            {{ $job['consumables'] > 0 ? 'Rs. ' . number_format($job['consumables'], 2) : '-' }}
                                        </td>
                                        <td class="py-3 px-4 text-right font-mono text-rose-500 font-semibold">
                                            {{ $job['outgoing'] > 0 ? 'Rs. ' . number_format($job['outgoing'], 2) : '-' }}
                                        </td>
                                        <td class="py-3 px-4 text-right font-mono font-semibold {{ $job['profit'] >= 0 ? 'text-primary' : 'text-rose-500' }}">
                                            Rs. {{ number_format($job['profit'], 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

</div>

<!-- Chart.js Advanced Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('financialChart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const dailyTimeline = @json($dailyTimeline);
        const startDateStr = "{{ $startDate }}";
        const endDateStr = "{{ $endDate }}";
        const frequencySelector = document.getElementById('chartFrequency');
        const styleSelector = document.getElementById('chartStyle');

        let currentChart = null;

        function getMonday(dateStr) {
            const d = new Date(dateStr);
            const day = d.getDay();
            const diff = d.getDate() - day + (day === 0 ? -6 : 1);
            const monday = new Date(d.setDate(diff));
            return monday.toISOString().split('T')[0];
        }

        function getMonthYear(dateStr) {
            const d = new Date(dateStr);
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return `${months[d.getMonth()]} ${d.getFullYear()}`;
        }

        function getAggregatedData() {
            let filtered = dailyTimeline;
            if (startDateStr) {
                filtered = filtered.filter(item => item.date >= startDateStr);
            }
            if (endDateStr) {
                filtered = filtered.filter(item => item.date <= endDateStr);
            }

            const freq = frequencySelector ? frequencySelector.value : 'days';
            const grouped = {};

            filtered.forEach(item => {
                let key = item.date;
                if (freq === 'weeks') {
                    key = getMonday(item.date);
                } else if (freq === 'months') {
                    key = getMonthYear(item.date);
                }

                if (!grouped[key]) {
                    grouped[key] = { key: key, income: 0, expenditure: 0, net_profit: 0 };
                }

                grouped[key].income += (item.income || 0);
                grouped[key].expenditure += (item.expenditure || 0);
                grouped[key].net_profit += ((item.income || 0) - (item.expenditure || 0));
            });

            const keys = Object.keys(grouped);
            if (freq === 'months') {
                keys.sort((a, b) => new Date('01 ' + a) - new Date('01 ' + b));
            } else {
                keys.sort();
            }

            return keys.map(key => {
                const bin = grouped[key];
                return {
                    label: freq === 'weeks' ? 'W/c ' + key : key,
                    income: Math.round(bin.income),
                    expenditure: Math.round(bin.expenditure),
                    net_profit: Math.round(bin.net_profit)
                };
            });
        }

        window.updateChart = function() {
            if (currentChart) {
                currentChart.destroy();
            }

            const chartStyle = styleSelector ? styleSelector.value : 'bar';
            const data = getAggregatedData();
            const labels = data.map(i => i.label);

            const isDark = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? '#334155' : '#e2e8f0';
            const textColor = isDark ? '#94a3b8' : '#64748b';

            const incomeData = data.map(i => i.income);
            const expenditureData = data.map(i => i.expenditure);
            const netData = data.map(i => i.net_profit);

            const datasets = [
                {
                    label: 'Cash In (Income)',
                    data: incomeData,
                    type: chartStyle === 'bar' ? 'bar' : 'line',
                    borderColor: '#10b981',
                    backgroundColor: chartStyle === 'bar' ? 'rgba(16, 185, 129, 0.7)' : 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    borderRadius: 6,
                    tension: 0.3,
                    fill: chartStyle === 'line'
                },
                {
                    label: 'Cash Out (Expenditure)',
                    data: expenditureData,
                    type: chartStyle === 'bar' ? 'bar' : 'line',
                    borderColor: '#ef4444',
                    backgroundColor: chartStyle === 'bar' ? 'rgba(239, 68, 68, 0.7)' : 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 2,
                    borderRadius: 6,
                    tension: 0.3,
                    fill: chartStyle === 'line'
                },
                {
                    label: 'Net Cash Flow',
                    data: netData,
                    type: 'line',
                    borderColor: '#6366f1',
                    backgroundColor: 'transparent',
                    borderWidth: 2.5,
                    borderDash: [4, 4],
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.3
                }
            ];

            currentChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        x: {
                            grid: { color: gridColor, drawBorder: false },
                            ticks: { color: textColor, font: { family: 'Outfit', size: 10 } }
                        },
                        y: {
                            grid: { color: gridColor, drawBorder: false },
                            ticks: {
                                color: textColor,
                                font: { family: 'Outfit', size: 10 },
                                callback: val => 'Rs. ' + val.toLocaleString()
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: textColor,
                                font: { family: 'Outfit', size: 11, weight: '500' }
                            }
                        },
                        tooltip: {
                            titleFont: { family: 'Outfit', size: 12 },
                            bodyFont: { family: 'Outfit', size: 12 },
                            callbacks: {
                                label: function(context) {
                                    return ` ${context.dataset.label}: Rs. ${context.raw.toLocaleString()}`;
                                }
                            }
                        }
                    }
                }
            });
        };

        updateChart();
    });

    // -------------------------------------------------------------
    // TAB SWITCHING LOGIC
    // -------------------------------------------------------------
    function switchStatsTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));
        const activeContent = document.getElementById(tabId);
        if (activeContent) activeContent.classList.remove('hidden');

        document.getElementById('btn-tab-overview').className = "px-4 py-2.5 font-semibold text-xs border-b-2 border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300 transition uppercase tracking-wider cursor-pointer flex items-center gap-2";
        document.getElementById('btn-tab-calendar').className = "px-4 py-2.5 font-semibold text-xs border-b-2 border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300 transition uppercase tracking-wider cursor-pointer flex items-center gap-2";
        document.getElementById('btn-tab-jobcards').className = "px-4 py-2.5 font-semibold text-xs border-b-2 border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300 transition uppercase tracking-wider cursor-pointer flex items-center gap-2";

        let activeBtnId = 'btn-tab-overview';
        if (tabId === 'dashboard-calendar') activeBtnId = 'btn-tab-calendar';
        if (tabId === 'dashboard-jobcards') activeBtnId = 'btn-tab-jobcards';

        const activeBtn = document.getElementById(activeBtnId);
        if (activeBtn) {
            activeBtn.className = "px-4 py-2.5 font-semibold text-xs border-b-2 border-primary text-primary transition uppercase tracking-wider cursor-pointer flex items-center gap-2";
        }
        localStorage.setItem('stats_active_tab', tabId);
        
        if (tabId === 'dashboard-calendar') {
            renderCalendar();
        } else if (tabId === 'dashboard-overview') {
            if (window.updateChart) window.updateChart();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        let statsTab = localStorage.getItem('stats_active_tab') || 'dashboard-overview';
        if (statsTab === 'dashboard-trends') statsTab = 'dashboard-overview';
        switchStatsTab(statsTab);
    });

    // -------------------------------------------------------------
    // CALENDAR VIEWER LOGIC (Tab 2)
    // -------------------------------------------------------------
    function renderCalendar() {
        const monthElem = document.getElementById('calendarMonth');
        const yearElem = document.getElementById('calendarYear');
        if (!monthElem || !yearElem) return;

        const selectedMonth = monthElem.value;
        const selectedYear = parseInt(yearElem.value);
        const daysContainer = document.getElementById('calendar-days-grid');
        if (!daysContainer) return;
        daysContainer.innerHTML = '';

        const timeline = @json($dailyTimeline);

        const firstDayOfMonth = new Date(selectedYear, parseInt(selectedMonth) - 1, 1);
        const lastDayOfMonth = new Date(selectedYear, parseInt(selectedMonth), 0);
        
        let firstDayIndex = firstDayOfMonth.getDay() - 1;
        if (firstDayIndex < 0) firstDayIndex = 6;

        const totalDays = lastDayOfMonth.getDate();

        for (let i = 0; i < firstDayIndex; i++) {
            const pad = document.createElement('div');
            pad.className = "bg-slate-100/30 dark:bg-slate-900/10 rounded-xl min-h-[90px] border border-transparent";
            daysContainer.appendChild(pad);
        }

        for (let day = 1; day <= totalDays; day++) {
            const dateStr = `${selectedYear}-${selectedMonth}-${String(day).padStart(2, '0')}`;
            const stats = timeline.find(item => item.date === dateStr) || { income: 0, expenditure: 0, jobs: 0 };

            const cell = document.createElement('div');
            cell.className = "app-card rounded-xl p-2 min-h-[90px] text-[10px] space-y-1 flex flex-col justify-between hover:border-primary transition duration-150";

            const header = document.createElement('div');
            header.className = "font-bold text-slate-800 dark:text-slate-200 border-b border-slate-200 dark:border-slate-800 pb-0.5 mb-1";
            header.innerText = day;
            cell.appendChild(header);

            const metrics = document.createElement('div');
            metrics.className = "space-y-1 flex-1 flex flex-col justify-center";

            if (stats.jobs > 0) {
                const j = document.createElement('div');
                j.className = "text-primary font-bold flex justify-between";
                j.innerHTML = `<span>Jobs:</span><span>${stats.jobs}</span>`;
                metrics.appendChild(j);
            }
            if (stats.income > 0) {
                const inc = document.createElement('div');
                inc.className = "text-emerald-600 dark:text-emerald-400 font-mono font-semibold flex justify-between";
                inc.innerHTML = `<span>In:</span><span>Rs. ${Math.round(stats.income).toLocaleString()}</span>`;
                metrics.appendChild(inc);
            }
            if (stats.expenditure > 0) {
                const exp = document.createElement('div');
                exp.className = "text-rose-500 font-mono font-semibold flex justify-between";
                exp.innerHTML = `<span>Out:</span><span>Rs. ${Math.round(stats.expenditure).toLocaleString()}</span>`;
                metrics.appendChild(exp);
            }

            if (metrics.children.length === 0) {
                const empty = document.createElement('div');
                empty.className = "text-slate-400 text-center py-2 italic text-[9px]";
                empty.innerText = "No activity";
                metrics.appendChild(empty);
            }

            cell.appendChild(metrics);
            daysContainer.appendChild(cell);
        }

        populateWeeklyAndMonthlySummaries(timeline);
    }

    function populateWeeklyAndMonthlySummaries(timeline) {
        const weeklyContainer = document.getElementById('weekly-summary-list');
        const monthlyContainer = document.getElementById('monthly-summary-list');
        if (!weeklyContainer || !monthlyContainer) return;
        weeklyContainer.innerHTML = '';
        monthlyContainer.innerHTML = '';

        function getWeekNumber(d) {
            d = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
            d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay() || 7));
            const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
            return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
        }

        const weeklyGroup = {};
        timeline.forEach(item => {
            const d = new Date(item.date);
            const yr = d.getFullYear();
            const wk = getWeekNumber(d);
            const key = `${yr}-W${String(wk).padStart(2, '0')}`;

            if (!weeklyGroup[key]) {
                weeklyGroup[key] = { key, income: 0, expenditure: 0, jobs: 0, count: 0 };
            }
            weeklyGroup[key].income += (item.income || 0);
            weeklyGroup[key].expenditure += (item.expenditure || 0);
            weeklyGroup[key].jobs += item.jobs || 0;
            weeklyGroup[key].count++;
        });

        Object.keys(weeklyGroup).sort().reverse().forEach(key => {
            const w = weeklyGroup[key];
            const row = document.createElement('div');
            row.className = "py-3 flex justify-between items-center text-xs";
            row.innerHTML = `
                <div>
                    <span class="font-bold text-slate-800 dark:text-slate-200">${w.key}</span>
                    <span class="block text-[9px] text-slate-500">Days Logged: ${w.count} • Jobs: ${w.jobs}</span>
                </div>
                <div class="text-right font-mono font-semibold">
                    <span class="text-emerald-600 dark:text-emerald-400 block">+Rs. ${Math.round(w.income).toLocaleString()}</span>
                    <span class="text-rose-500 block">-Rs. ${Math.round(w.expenditure).toLocaleString()}</span>
                </div>
            `;
            weeklyContainer.appendChild(row);
        });

        if (weeklyContainer.children.length === 0) {
            weeklyContainer.innerHTML = '<div class="text-slate-500 text-center py-6">No weekly activity found.</div>';
        }

        const monthlyGroup = {};
        timeline.forEach(item => {
            const key = item.date.substring(0, 7);
            if (!monthlyGroup[key]) {
                monthlyGroup[key] = { key, income: 0, expenditure: 0, jobs: 0, count: 0 };
            }
            monthlyGroup[key].income += (item.income || 0);
            monthlyGroup[key].expenditure += (item.expenditure || 0);
            monthlyGroup[key].jobs += item.jobs || 0;
            monthlyGroup[key].count++;
        });

        Object.keys(monthlyGroup).sort().reverse().forEach(key => {
            const m = monthlyGroup[key];
            const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            const yr = m.key.substring(0, 4);
            const mIdx = parseInt(m.key.substring(5, 7)) - 1;
            const label = `${months[mIdx]} ${yr}`;

            const row = document.createElement('div');
            row.className = "py-3 flex justify-between items-center text-xs";
            row.innerHTML = `
                <div>
                    <span class="font-bold text-slate-800 dark:text-slate-200">${label}</span>
                    <span class="block text-[9px] text-slate-500">Days Logged: ${m.count} • Jobs: ${m.jobs}</span>
                </div>
                <div class="text-right font-mono font-semibold">
                    <span class="text-emerald-600 dark:text-emerald-400 block">+Rs. ${Math.round(m.income).toLocaleString()}</span>
                    <span class="text-rose-500 block">-Rs. ${Math.round(m.expenditure).toLocaleString()}</span>
                </div>
            `;
            monthlyContainer.appendChild(row);
        });

        if (monthlyContainer.children.length === 0) {
            monthlyContainer.innerHTML = '<div class="text-slate-500 text-center py-6">No monthly activity found.</div>';
        }
        
        if (window.lucide) {
            lucide.createIcons();
        }
    }
</script>
@endsection
