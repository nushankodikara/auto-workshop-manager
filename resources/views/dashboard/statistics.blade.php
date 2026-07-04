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
                            <span class="text-slate-500 block cursor-help" title="Calculated based on daily wage (basic salary / employee required days) multiplied by worker attendance days (present/half-day).">Direct Cost</span>
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
                    <div class="text-[10px] text-slate-400 dark:text-slate-500 px-1 leading-snug">
                        * Direct cost computed from worker attendance days & basic salary (excluding managers and admins).
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

    <!-- Dynamic Advanced Financial Trend Card -->
    <div class="app-card rounded-2xl p-6 shadow-xs space-y-6">
        <!-- Card Header with Range, Frequency & Chart Type Controls -->
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 border-b border-slate-205 dark:border-slate-800 pb-4">
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                    <i data-lucide="bar-chart-3" class="w-4 h-4 text-primary"></i>
                    <span>Interactive Financial Trend Analyzer</span>
                </h3>
                <p class="text-[10px] text-slate-405 dark:text-slate-500 mt-1 leading-normal">
                    Generate multi-metric overlay reports, adjust time buckets, and trace financial regressions.
                </p>
            </div>
            
            <!-- Quick Configuration Panel -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Date Filters -->
                <div class="flex items-center gap-1.5">
                    <input type="date" id="chartStartDate" value="{{ $startDate }}"
                           class="px-2.5 py-1.5 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary font-sans">
                    <span class="text-xs text-slate-400">to</span>
                    <input type="date" id="chartEndDate" value="{{ $endDate }}"
                           class="px-2.5 py-1.5 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary font-sans">
                </div>

                <!-- Frequency Selection -->
                <select id="chartFrequency" class="px-3 py-1.5 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary font-sans">
                    <option value="days">Daily View</option>
                    <option value="weeks">Weekly View</option>
                    <option value="months">Monthly View</option>
                </select>

                <!-- Chart Style Selector -->
                <select id="chartStyle" class="px-3 py-1.5 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary font-sans">
                    <option value="line">Line Graph</option>
                    <option value="bar">Bar Graph</option>
                    <option value="pie">Share Breakdown (Pie)</option>
                </select>

                <!-- Trend Line Analysis Checkbox -->
                <div class="flex items-center gap-1 bg-slate-100/50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 px-3 py-1.5 rounded-lg">
                    <input type="checkbox" id="chartTrendAnalysis" class="rounded text-primary focus:ring-primary w-3.5 h-3.5 cursor-pointer">
                    <label for="chartTrendAnalysis" class="text-xs text-slate-650 dark:text-slate-350 select-none cursor-pointer font-semibold uppercase text-[10px]">Trend Line</label>
                </div>
            </div>
        </div>

        <!-- Metric Selectors Grid (Multi-metric toggling) -->
        <div class="space-y-3">
            <h4 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Select Metrics to Overlay & Compare:</h4>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                <!-- Group 1: General Cash flows -->
                <div class="space-y-2 border border-slate-200 dark:border-slate-850 bg-slate-50/40 dark:bg-slate-950/20 rounded-xl p-3">
                    <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider block border-b border-slate-200 dark:border-slate-850 pb-1.5 mb-2">Ledger Cash Flow</span>
                    <label class="flex items-center gap-2 cursor-pointer text-slate-650 dark:text-slate-350">
                        <input type="checkbox" name="chart_metrics" value="income" checked class="rounded text-primary focus:ring-primary w-4 h-4 cursor-pointer">
                        <span>Total Income</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-slate-650 dark:text-slate-350">
                        <input type="checkbox" name="chart_metrics" value="expenditure" checked class="rounded text-primary focus:ring-primary w-4 h-4 cursor-pointer">
                        <span>Total Expenditure</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-slate-650 dark:text-slate-350">
                        <input type="checkbox" name="chart_metrics" value="net_profit" class="rounded text-primary focus:ring-primary w-4 h-4 cursor-pointer">
                        <span>Net Profit</span>
                    </label>
                </div>

                <!-- Group 2: Parts Trading -->
                <div class="space-y-2 border border-slate-200 dark:border-slate-850 bg-slate-50/40 dark:bg-slate-950/20 rounded-xl p-3">
                    <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider block border-b border-slate-200 dark:border-slate-850 pb-1.5 mb-2">Parts Segment</span>
                    <label class="flex items-center gap-2 cursor-pointer text-slate-650 dark:text-slate-350">
                        <input type="checkbox" name="chart_metrics" value="parts_revenue" class="rounded text-primary focus:ring-primary w-4 h-4 cursor-pointer">
                        <span>Parts Revenue</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-slate-650 dark:text-slate-350">
                        <input type="checkbox" name="chart_metrics" value="parts_cogs" class="rounded text-primary focus:ring-primary w-4 h-4 cursor-pointer">
                        <span>Parts Cost (COGS)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-slate-650 dark:text-slate-350">
                        <input type="checkbox" name="chart_metrics" value="parts_margin" class="rounded text-primary focus:ring-primary w-4 h-4 cursor-pointer">
                        <span>Parts Margin (%)</span>
                    </label>
                </div>

                <!-- Group 3: Labor Trading -->
                <div class="space-y-2 border border-slate-200 dark:border-slate-850 bg-slate-50/40 dark:bg-slate-950/20 rounded-xl p-3">
                    <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider block border-b border-slate-200 dark:border-slate-850 pb-1.5 mb-2">Labor Segment</span>
                    <label class="flex items-center gap-2 cursor-pointer text-slate-650 dark:text-slate-350">
                        <input type="checkbox" name="chart_metrics" value="labor_revenue" class="rounded text-primary focus:ring-primary w-4 h-4 cursor-pointer">
                        <span>Labor Revenue</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-slate-650 dark:text-slate-350">
                        <input type="checkbox" name="chart_metrics" value="labor_cogs" class="rounded text-primary focus:ring-primary w-4 h-4 cursor-pointer">
                        <span>Labor Cost (COGS)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-slate-650 dark:text-slate-350">
                        <input type="checkbox" name="chart_metrics" value="labor_margin" class="rounded text-primary focus:ring-primary w-4 h-4 cursor-pointer">
                        <span>Labor Margin (%)</span>
                    </label>
                </div>

                <!-- Group 4: Overall Trading -->
                <div class="space-y-2 border border-slate-200 dark:border-slate-850 bg-slate-50/40 dark:bg-slate-950/20 rounded-xl p-3">
                    <span class="text-[10px] text-slate-400 uppercase font-bold tracking-wider block border-b border-slate-200 dark:border-slate-850 pb-1.5 mb-2">Total Trading</span>
                    <label class="flex items-center gap-2 cursor-pointer text-slate-650 dark:text-slate-350">
                        <input type="checkbox" name="chart_metrics" value="trading_revenue" class="rounded text-primary focus:ring-primary w-4 h-4 cursor-pointer">
                        <span>Trading Revenue</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-slate-650 dark:text-slate-350">
                        <input type="checkbox" name="chart_metrics" value="trading_cogs" class="rounded text-primary focus:ring-primary w-4 h-4 cursor-pointer">
                        <span>Trading Cost (COGS)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-slate-650 dark:text-slate-350">
                        <input type="checkbox" name="chart_metrics" value="trading_margin" class="rounded text-primary focus:ring-primary w-4 h-4 cursor-pointer">
                        <span>Trading Margin (%)</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Render Target Canvas -->
        <div class="relative w-full h-96 pt-2">
            <canvas id="financialChart"></canvas>
        </div>
    </div>

    <!-- Chart.js Advanced Generation Script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('financialChart').getContext('2d');
            const dailyTimeline = @json($dailyTimeline);
            
            // UI Component References
            const startDateInput = document.getElementById('chartStartDate');
            const endDateInput = document.getElementById('chartEndDate');
            const frequencySelector = document.getElementById('chartFrequency');
            const styleSelector = document.getElementById('chartStyle');
            const trendCheckbox = document.getElementById('chartTrendAnalysis');
            const metricCheckboxes = document.querySelectorAll('input[name="chart_metrics"]');

            let currentChart = null;

            // Global Metric Definitions & Colors
            const METRIC_CONFIG = {
                income: { label: 'Total Income (Ledger)', type: 'line', yAxisID: 'yAmount', borderColor: '#10b981', backgroundColor: 'rgba(16, 185, 129, 0.15)' },
                expenditure: { label: 'Total Expenditure (Ledger)', type: 'line', yAxisID: 'yAmount', borderColor: '#ef4444', backgroundColor: 'rgba(239, 68, 68, 0.15)' },
                net_profit: { label: 'Net Profit (Ledger)', type: 'line', yAxisID: 'yAmount', borderColor: '#6366f1', backgroundColor: 'rgba(99, 102, 241, 0.15)' },
                
                parts_revenue: { label: 'Parts Revenue (Trading)', type: 'bar', yAxisID: 'yAmount', borderColor: '#3b82f6', backgroundColor: 'rgba(59, 130, 246, 0.35)' },
                parts_cogs: { label: 'Parts Cost (COGS)', type: 'bar', yAxisID: 'yAmount', borderColor: '#f97316', backgroundColor: 'rgba(249, 115, 22, 0.35)' },
                parts_margin: { label: 'Parts Margin (%)', type: 'line', yAxisID: 'yPercent', borderColor: '#8b5cf6', backgroundColor: 'transparent', borderDash: [4, 4] },
                
                labor_revenue: { label: 'Labor Revenue (Trading)', type: 'bar', yAxisID: 'yAmount', borderColor: '#06b6d4', backgroundColor: 'rgba(6, 182, 212, 0.35)' },
                labor_cogs: { label: 'Labor Cost (COGS)', type: 'bar', yAxisID: 'yAmount', borderColor: '#ec4899', backgroundColor: 'rgba(236, 72, 153, 0.35)' },
                labor_margin: { label: 'Labor Margin (%)', type: 'line', yAxisID: 'yPercent', borderColor: '#a855f7', backgroundColor: 'transparent', borderDash: [4, 4] },
                
                trading_revenue: { label: 'Total Trading Revenue', type: 'bar', yAxisID: 'yAmount', borderColor: '#14b8a6', backgroundColor: 'rgba(20, 184, 166, 0.35)' },
                trading_cogs: { label: 'Total Trading Cost (COGS)', type: 'bar', yAxisID: 'yAmount', borderColor: '#f43f5e', backgroundColor: 'rgba(244, 63, 94, 0.35)' },
                trading_margin: { label: 'Total Trading Margin (%)', type: 'line', yAxisID: 'yPercent', borderColor: '#d946ef', backgroundColor: 'transparent', borderDash: [4, 4] }
            };

            // Date Utilities
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

            // Simple Linear Regression for Trend Line calculations
            function calculateTrendPoints(dataPoints) {
                const n = dataPoints.length;
                if (n < 2) return null;
                
                let sumX = 0;
                let sumY = 0;
                let sumXY = 0;
                let sumXX = 0;
                
                for (let i = 0; i < n; i++) {
                    sumX += i;
                    sumY += dataPoints[i];
                    sumXY += i * dataPoints[i];
                    sumXX += i * i;
                }
                
                const slope = (n * sumXY - sumX * sumY) / (n * sumXX - sumX * sumX);
                const intercept = (sumY - slope * sumX) / n;
                
                const trend = [];
                for (let i = 0; i < n; i++) {
                    trend.push(slope * i + intercept);
                }
                return trend;
            }

            // Data Processor
            function getAggregatedData() {
                const startDateStr = startDateInput.value;
                const endDateStr = endDateInput.value;
                const freq = frequencySelector.value;

                // 1. Date Range Filtering
                let filtered = dailyTimeline;
                if (startDateStr) {
                    filtered = filtered.filter(item => item.date >= startDateStr);
                }
                if (endDateStr) {
                    filtered = filtered.filter(item => item.date <= endDateStr);
                }

                // 2. Bucketing
                const grouped = {};
                filtered.forEach(item => {
                    let key = item.date;
                    if (freq === 'weeks') {
                        key = getMonday(item.date);
                    } else if (freq === 'months') {
                        key = getMonthYear(item.date);
                    }

                    if (!grouped[key]) {
                        grouped[key] = {
                            key: key,
                            income: 0,
                            expenditure: 0,
                            parts_revenue: 0,
                            parts_cogs: 0,
                            labor_revenue: 0,
                            labor_cogs: 0,
                            outsourcing_revenue: 0,
                            outsourcing_cogs: 0
                        };
                    }

                    grouped[key].income += item.income;
                    grouped[key].expenditure += item.expenditure;
                    grouped[key].parts_revenue += item.parts_revenue;
                    grouped[key].parts_cogs += item.parts_cogs;
                    grouped[key].labor_revenue += item.labor_revenue;
                    grouped[key].labor_cogs += item.labor_cogs;
                    grouped[key].outsourcing_revenue += item.outsourcing_revenue;
                    grouped[key].outsourcing_cogs += item.outsourcing_cogs;
                });

                // Sort keys chronologically
                const keys = Object.keys(grouped);
                if (freq === 'months') {
                    keys.sort((a, b) => new Date('01 ' + a) - new Date('01 ' + b));
                } else {
                    keys.sort();
                }

                // Compile final aggregates & derived metrics
                return keys.map(key => {
                    const bin = grouped[key];
                    const netProfit = bin.income - bin.expenditure;
                    const partsProfit = bin.parts_revenue - bin.parts_cogs;
                    const partsMargin = bin.parts_revenue > 0 ? (partsProfit / bin.parts_revenue) * 100 : 0;
                    const laborProfit = bin.labor_revenue - bin.labor_cogs;
                    const laborMargin = bin.labor_revenue > 0 ? (laborProfit / bin.labor_revenue) * 100 : 0;
                    
                    const tradingRev = bin.parts_revenue + bin.labor_revenue + bin.outsourcing_revenue;
                    const tradingCogs = bin.parts_cogs + bin.labor_cogs + bin.outsourcing_cogs;
                    const tradingProfit = tradingRev - tradingCogs;
                    const tradingMargin = tradingRev > 0 ? (tradingProfit / tradingRev) * 100 : 0;

                    return {
                        label: freq === 'weeks' ? 'W/c ' + key : key,
                        income: bin.income,
                        expenditure: bin.expenditure,
                        net_profit: netProfit,
                        parts_revenue: bin.parts_revenue,
                        parts_cogs: bin.parts_cogs,
                        parts_margin: partsMargin,
                        labor_revenue: bin.labor_revenue,
                        labor_cogs: bin.labor_cogs,
                        labor_margin: laborMargin,
                        trading_revenue: tradingRev,
                        trading_cogs: tradingCogs,
                        trading_margin: tradingMargin
                    };
                });
            }

            // Chart Render function
            function updateChart() {
                if (currentChart) {
                    currentChart.destroy();
                }

                // 1. Get current configuration
                const chartStyle = styleSelector.value;
                const enableTrend = trendCheckbox.checked;
                const selectedMetrics = Array.from(metricCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                if (selectedMetrics.length === 0) {
                    // Render clean state if nothing selected
                    ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
                    return;
                }

                const data = getAggregatedData();
                const labels = data.map(item => item.label);

                // Check theme colors
                const isDark = document.documentElement.classList.contains('dark');
                const gridColor = isDark ? '#334155' : '#e2e8f0';
                const textColor = isDark ? '#94a3b8' : '#64748b';

                let datasets = [];
                let options = {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                color: textColor,
                                font: { family: 'Outfit', size: 11, weight: '500' }
                            }
                        },
                        tooltip: {
                            titleFont: { family: 'Outfit', size: 12 },
                            bodyFont: { family: 'Outfit', size: 12 }
                        }
                    }
                };

                // 2. PIE CHART RENDER LOGIC
                if (chartStyle === 'pie') {
                    // Pie chart shows the period-wide breakdown sum of selected metrics
                    const sums = selectedMetrics.map(metricKey => {
                        return data.reduce((acc, curr) => acc + (curr[metricKey] || 0), 0);
                    });

                    const pieLabels = selectedMetrics.map(k => METRIC_CONFIG[k].label);
                    const borderColors = selectedMetrics.map(k => METRIC_CONFIG[k].borderColor);
                    const bgColors = selectedMetrics.map(k => METRIC_CONFIG[k].borderColor + '44');

                    datasets.push({
                        data: sums,
                        backgroundColor: borderColors, // Use borderColors directly for vibrant solid slices
                        borderColor: isDark ? '#020617' : '#ffffff',
                        borderWidth: 2
                    });

                    currentChart = new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: pieLabels,
                            datasets: datasets
                        },
                        options: {
                            ...options,
                            plugins: {
                                ...options.plugins,
                                tooltip: {
                                    ...options.plugins.tooltip,
                                    callbacks: {
                                        label: function(context) {
                                            const val = context.raw;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const pct = total > 0 ? ((val / total) * 100).toFixed(1) + '%' : '0%';
                                            const formattedVal = new Intl.NumberFormat('en-US').format(val);
                                            return ` ${context.label}: Rs. ${formattedVal} (${pct})`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                    return;
                }

                // 3. LINE & BAR CHART MULTI-AXES RENDER LOGIC
                let showAmountAxis = false;
                let showPercentAxis = false;

                selectedMetrics.forEach(metricKey => {
                    const cfg = METRIC_CONFIG[metricKey];
                    if (cfg.yAxisID === 'yAmount') showAmountAxis = true;
                    if (cfg.yAxisID === 'yPercent') showPercentAxis = true;

                    const datasetData = data.map(item => item[metricKey] || 0);

                    // Add primary metric dataset
                    datasets.push({
                        label: cfg.label,
                        data: datasetData,
                        type: chartStyle === 'line' ? 'line' : cfg.type,
                        borderColor: cfg.borderColor,
                        backgroundColor: cfg.backgroundColor,
                        borderWidth: 2,
                        yAxisID: cfg.yAxisID,
                        fill: chartStyle === 'line' ? false : (cfg.type === 'line' ? false : true),
                        tension: 0.35,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    });

                    // Add trend line if requested
                    if (enableTrend) {
                        const trendData = calculateTrendPoints(datasetData);
                        if (trendData) {
                            datasets.push({
                                label: `${cfg.label} Trend`,
                                data: trendData,
                                type: 'line',
                                borderColor: cfg.borderColor,
                                borderWidth: 1.5,
                                borderDash: [6, 6],
                                fill: false,
                                pointRadius: 0,
                                yAxisID: cfg.yAxisID,
                                tension: 0
                            });
                        }
                    }
                });

                // Configure Scales & Tooltips
                options.scales = {
                    x: {
                        grid: { color: gridColor },
                        ticks: { color: textColor, font: { family: 'Outfit', size: 10 } }
                    }
                };

                if (showAmountAxis) {
                    options.scales.yAmount = {
                        type: 'linear',
                        position: 'left',
                        grid: { color: gridColor },
                        ticks: {
                            color: textColor,
                            font: { family: 'Outfit', size: 10 },
                            callback: function(value) {
                                return 'Rs. ' + new Intl.NumberFormat('en-US').format(value);
                            }
                        }
                    };
                }

                if (showPercentAxis) {
                    options.scales.yPercent = {
                        type: 'linear',
                        position: 'right',
                        grid: { drawOnChartArea: !showAmountAxis, color: gridColor }, // only draw grid lines if there's no amount axis
                        ticks: {
                            color: textColor,
                            font: { family: 'Outfit', size: 10 },
                            callback: function(value) {
                                return value.toFixed(0) + '%';
                            }
                        },
                        min: 0
                    };
                }

                options.plugins.tooltip.callbacks = {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed.y !== null) {
                            const isPercent = context.dataset.yAxisID === 'yPercent';
                            if (isPercent) {
                                label += context.parsed.y.toFixed(1) + '%';
                            } else {
                                label += 'Rs. ' + new Intl.NumberFormat('en-US').format(context.parsed.y);
                            }
                        }
                        return label;
                    }
                };

                currentChart = new Chart(ctx, {
                    data: {
                        labels: labels,
                        datasets: datasets
                    },
                    options: options
                });
            }

            // Setup Change Listeners
            startDateInput.addEventListener('change', updateChart);
            endDateInput.addEventListener('change', updateChart);
            frequencySelector.addEventListener('change', updateChart);
            styleSelector.addEventListener('change', updateChart);
            trendCheckbox.addEventListener('change', updateChart);
            metricCheckboxes.forEach(cb => cb.addEventListener('change', updateChart));

            // Theme Mutation Observer
            const themeObserver = new MutationObserver(updateChart);
            themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

            // Initial Render
            updateChart();
        });
    </script>
</div>
@endsection
