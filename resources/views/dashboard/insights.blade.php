@extends('layouts.app')

@section('title', 'Data Insights & Analytics')

@section('content')
<div class="space-y-8">

    <!-- Top Date Filter Bar -->
    <div class="app-card rounded-2xl p-4 shadow-xs">
        <form action="{{ route('dashboard.insights') }}" method="GET" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                <i data-lucide="filter" class="w-4 h-4 text-primary"></i>
                <span>Filter Reporting Range</span>
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
            </div>
        </form>
    </div>

    <!-- Insights Summary Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Card 1: Revenue Division -->
        <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-405 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-1.5">
                <i data-lucide="coins" class="w-4 h-4 text-primary"></i>
                <span>Revenue Allocation</span>
            </h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-500">Parts Revenue</span>
                    <span class="font-bold text-slate-750 dark:text-slate-200 font-mono">
                        {{ config('app.currency', '$') }}{{ number_format($partsRevenue, 2) }}
                    </span>
                </div>
                <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-500">Labor / Services Revenue</span>
                    <span class="font-bold text-slate-750 dark:text-slate-200 font-mono">
                        {{ config('app.currency', '$') }}{{ number_format($laborRevenue, 2) }}
                    </span>
                </div>
                <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex justify-between items-center text-sm font-bold">
                    <span class="text-slate-705 dark:text-slate-350">Total Income</span>
                    <span class="font-mono text-primary">
                        {{ config('app.currency', '$') }}{{ number_format($partsRevenue + $laborRevenue, 2) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Card 2: Top Technicians Activity -->
        <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-405 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-1.5">
                <i data-lucide="users" class="w-4 h-4 text-primary"></i>
                <span>Staff Repairs Completed</span>
            </h3>
            <div class="space-y-2.5 max-h-36 overflow-y-auto pr-1">
                @forelse($technicianJobs as $tech)
                    <div class="flex justify-between items-center text-xs">
                        <span class="capitalize text-slate-700 dark:text-slate-350">{{ $tech->name }}</span>
                        <span class="font-bold text-slate-800 dark:text-slate-250 bg-slate-100 dark:bg-slate-950 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-800">
                            {{ $tech->job_count }} jobs
                        </span>
                    </div>
                @empty
                    <div class="text-center text-slate-500 text-xs py-4">No active technicians.</div>
                @endforelse
            </div>
        </div>

        <!-- Card 3: Stock Status -->
        <div class="app-card rounded-2xl p-6 flex flex-col justify-between shadow-xs">
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-405 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-1.5">
                    <i data-lucide="package-x" class="w-4 h-4 text-red-500"></i>
                    <span>Low Stock Warning</span>
                </h3>
                <h4 class="text-3xl font-bold mt-4 {{ $lowStockCount > 0 ? 'text-red-550' : 'text-green-600' }}">
                    {{ $lowStockCount }} Items
                </h4>
            </div>
            <div class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider mt-4">
                {{ $lowStockCount > 0 ? 'Urgent reorder required' : 'Inventory levels are secure' }}
            </div>
        </div>

    </div>

    <!-- Custom SQL Queries explorer console -->
    <div class="app-card rounded-2xl p-6 space-y-6 shadow-xs">
        <div class="border-b border-slate-200 dark:border-slate-800 pb-3">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-550 dark:text-slate-350 flex items-center gap-1.5">
                <i data-lucide="terminal" class="w-4 h-4 text-primary"></i>
                <span>Interactive Custom SQL Explorer</span>
            </h3>
            <p class="text-xs text-slate-500 mt-1 leading-normal">
                Execute custom read-only data queries directly on the SQLite database schema. Write pure SELECT statements.
            </p>
        </div>

        <!-- Form for query -->
        <form action="{{ route('dashboard.insights') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="sql_query" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Write SQLite SELECT Statement</label>
                <textarea name="sql_query" id="sql_query" rows="4" required placeholder="SELECT name, email, role FROM users WHERE role = 'worker';"
                          class="w-full px-4 py-3 bg-slate-950 text-slate-100 border border-slate-800 rounded-xl font-mono text-sm focus:outline-none focus:border-primary">{{ $sqlQuery }}</textarea>
            </div>

            <!-- Suggestion helpers -->
            <div class="text-xs text-slate-500 space-y-1">
                <span class="block font-bold">Quick templates (click to copy):</span>
                <div class="flex flex-wrap gap-2 pt-1">
                    <button type="button" onclick="setQuery(this.innerText)" class="px-2 py-1 bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-850 hover:bg-slate-200 dark:hover:bg-slate-800 rounded transition font-mono">SELECT name, email, role FROM users;</button>
                    <button type="button" onclick="setQuery(this.innerText)" class="px-2 py-1 bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-850 hover:bg-slate-200 dark:hover:bg-slate-800 rounded transition font-mono">SELECT make, model, plate_number, mileage FROM vehicles ORDER BY mileage DESC;</button>
                    <button type="button" onclick="setQuery(this.innerText)" class="px-2 py-1 bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-850 hover:bg-slate-200 dark:hover:bg-slate-800 rounded transition font-mono">SELECT status, COUNT(*) as qty FROM job_cards GROUP BY status;</button>
                </div>
            </div>

            <div class="flex justify-between items-center pt-2">
                <span class="text-[11px] text-slate-500 flex items-center gap-1">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5 text-green-500"></i>
                    <span>Read-Only mode active. Destructive mutations are blocked.</span>
                </span>
                <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg text-xs transition shadow-sm">
                    Execute Query
                </button>
            </div>
        </form>

        <!-- Display Errors -->
        @if($queryError)
            <div class="p-4 rounded-lg bg-red-500/10 border border-red-500/20 text-red-600 dark:text-red-400 text-xs flex items-start gap-2">
                <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0 mt-0.5"></i>
                <div class="space-y-1">
                    <div class="font-bold uppercase tracking-wider">Execution Failed</div>
                    <p class="font-mono text-[11px] select-all leading-normal">{{ $queryError }}</p>
                </div>
            </div>
        @endif

        <!-- Query results -->
        @if($queryResult !== null && !$queryError)
            <div class="space-y-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                <div class="flex justify-between items-center text-xs font-semibold text-slate-500">
                    <span>Query Results</span>
                    <span>Returned: {{ count($queryResult) }} rows</span>
                </div>

                @if(empty($queryResult))
                    <div class="text-slate-500 text-xs py-8 text-center bg-slate-50 dark:bg-slate-955/20 rounded-xl border border-slate-200 dark:border-slate-800 border-dashed">
                        Query executed successfully, but returned 0 rows.
                    </div>
                @else
                    <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl max-h-96">
                        <table class="w-full text-left text-xs border-collapse font-mono">
                            <thead class="bg-slate-100 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 text-slate-550 dark:text-slate-400 font-semibold sticky top-0">
                                <tr>
                                    @foreach($headers as $head)
                                        <th class="py-3 px-4">{{ $head }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-850/60 bg-white dark:bg-slate-900/30">
                                @foreach($queryResult as $row)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-950/20 text-slate-700 dark:text-slate-300">
                                        @foreach($headers as $head)
                                            <td class="py-3 px-4 select-all">{{ is_null($row[$head]) ? 'NULL' : $row[$head] }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif

    </div>

</div>

<script>
    function setQuery(text) {
        document.getElementById('sql_query').value = text;
        document.getElementById('sql_query').focus();
    }
</script>
@endsection
