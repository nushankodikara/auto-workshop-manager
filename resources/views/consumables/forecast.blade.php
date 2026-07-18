@extends('layouts.app')

@section('title', 'Consumables Demand Forecast')

@section('content')
<div class="space-y-6">

    <!-- Sub-navigation Tabs -->
    <div class="flex border-b border-slate-200 dark:border-slate-800">
        <a href="{{ route('consumables.index') }}" 
           class="px-5 py-3 font-semibold text-sm border-b-2 border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-350 transition">
            Consumables supplies list
        </a>
        <a href="{{ route('consumables.forecast') }}" 
           class="px-5 py-3 font-semibold text-sm border-b-2 border-primary text-primary transition flex items-center gap-1.5">
            <i data-lucide="trending-up" class="w-4 h-4"></i>
            <span>Demand Forecast & Purchases</span>
        </a>
    </div>

    <!-- Filter & Configuration Panel -->
    <div class="app-card rounded-2xl p-6 shadow-xs border border-slate-200 dark:border-slate-800">
        <form action="{{ route('consumables.forecast') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label for="search" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Search Items</label>
                <input type="text" name="search" id="search" value="{{ $search }}" placeholder="e.g. Brake Cleaner"
                       class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950/20 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs">
            </div>

            <div>
                <label for="days" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Historical window</label>
                <select name="days" id="days"
                        class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950/20 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs">
                    <option value="30" {{ $days == 30 ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="60" {{ $days == 60 ? 'selected' : '' }}>Last 60 Days</option>
                    <option value="90" {{ $days == 90 ? 'selected' : '' }}>Last 90 Days</option>
                </select>
            </div>

            <div>
                <label for="safety_factor" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Safety Buffer factor</label>
                <select name="safety_factor" id="safety_factor"
                        class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-950/20 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs">
                    <option value="1.0" {{ $safetyFactor == 1.0 ? 'selected' : '' }}>1.0x (No safety buffer)</option>
                    <option value="1.2" {{ $safetyFactor == 1.2 ? 'selected' : '' }}>1.2x (20% safety buffer)</option>
                    <option value="1.5" {{ $safetyFactor == 1.5 ? 'selected' : '' }}>1.5x (50% safety buffer)</option>
                    <option value="2.0" {{ $safetyFactor == 2.0 ? 'selected' : '' }}>2.0x (Double estimated demand)</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-primary hover:bg-primary-hover text-white text-xs font-bold rounded-lg transition flex items-center justify-center gap-1 cursor-pointer border-0">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    <span>Calculate</span>
                </button>
                <a href="{{ route('consumables.forecast') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-lg transition flex items-center justify-center cursor-pointer">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Forecast Results -->
    <div class="app-card rounded-2xl overflow-hidden shadow-xs border border-slate-200 dark:border-slate-800">
        <div class="overflow-x-auto">
            <table class="datatable w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-100/60 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                        <th class="py-4 px-6">Supply Item</th>
                        <th class="py-4 px-6">Stock Level</th>
                        <th class="py-4 px-6">Usage rate ({{ $days }}d)</th>
                        <th class="py-4 px-6">Purchases this month</th>
                        <th class="py-4 px-6">Cost this month</th>
                        <th class="py-4 px-6">Estimated Next Month</th>
                        <th class="py-4 px-6">Reorder quantity</th>
                        <th class="py-4 px-6 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-850/60 font-medium text-xs">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-100/40 dark:hover:bg-slate-900/40 transition">
                            <td class="py-4 px-6">
                                <div class="font-bold text-slate-800 dark:text-slate-200 text-sm">
                                    {{ $item->name }}
                                </div>
                                <div class="text-[10px] text-slate-500 font-mono mt-0.5">{{ $item->sku ?: '—' }}</div>
                            </td>
                            <td class="py-4 px-6 font-mono font-bold text-slate-700 dark:text-slate-300">
                                {{ number_format($item->quantity, 2) }} {{ $item->unit }}
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-semibold text-slate-800 dark:text-slate-200 font-mono">
                                    {{ number_format($item->historical_usage, 2) }} {{ $item->unit }}
                                </div>
                                <div class="text-[9px] text-slate-400 leading-normal mt-0.5">
                                    @if($item->using_fallback)
                                        <span class="text-slate-500 font-semibold bg-slate-100 dark:bg-slate-800 px-1 py-0.5 rounded text-[8px]">Inflows Fallback</span>
                                    @else
                                        <span class="text-amber-500 font-semibold bg-amber-500/10 px-1 py-0.5 rounded text-[8px]">Consumption Logs</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-4 px-6 font-mono text-emerald-600 dark:text-emerald-500 font-semibold">
                                +{{ number_format($item->purchases_this_month, 2) }} {{ $item->unit }}
                            </td>
                            <td class="py-4 px-6 font-mono text-slate-800 dark:text-slate-200 font-bold">
                                {{ config('app.currency', 'Rs.') }}{{ number_format($item->cost_this_month, 2) }}
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-mono text-slate-800 dark:text-slate-200 font-bold">
                                    {{ number_format($item->target_inventory, 2) }} {{ $item->unit }}
                                </div>
                                <div class="text-[9px] text-slate-400 mt-0.5">Avg: {{ number_format($item->daily_usage, 2) }} / day</div>
                            </td>
                            <td class="py-4 px-6 font-mono text-sm font-bold {{ $item->recommended_order > 0 ? 'text-primary' : 'text-slate-500' }}">
                                {{ number_format($item->recommended_order, 2) }} {{ $item->unit }}
                            </td>
                            <td class="py-4 px-6 text-right">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $item->recommended_order > 0 ? 'bg-primary/10 text-primary' : 'bg-emerald-500/10 text-emerald-600' }}">
                                    {{ $item->recommended_order > 0 ? 'Reorder Needed' : 'Sufficient' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                <i data-lucide="trending-up" class="w-12 h-12 mx-auto mb-3 opacity-30"></i>
                                <p class="text-sm font-medium">No consumables registered yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
