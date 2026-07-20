@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-8">

    <!-- KPI Summary Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        
        <!-- Metrics 1: Active Job Cards -->
        <div class="app-card rounded-2xl p-6 flex flex-col justify-between shadow-xs">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                    <i data-lucide="clipboard-list" class="w-3.5 h-3.5 text-primary"></i>
                    <span>Active Jobs</span>
                </span>
                <h3 class="text-3xl font-bold mt-2 text-slate-800 dark:text-slate-100">{{ $activeJobCardsCount }}</h3>
            </div>
            <div class="text-xs text-slate-500 mt-4 font-semibold uppercase tracking-wider">Currently inside the shop</div>
        </div>

        <!-- Metrics 2: Total Clients -->
        <div class="app-card rounded-2xl p-6 flex flex-col justify-between shadow-xs">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                    <i data-lucide="users" class="w-3.5 h-3.5 text-primary"></i>
                    <span>Total Clients</span>
                </span>
                <h3 class="text-3xl font-bold mt-2 text-slate-800 dark:text-slate-100">{{ $clientsCount }}</h3>
            </div>
            <div class="text-xs text-slate-500 mt-4 font-semibold uppercase tracking-wider">Registered profiles</div>
        </div>

        <!-- Metrics 3: Total Vehicles -->
        <div class="app-card rounded-2xl p-6 flex flex-col justify-between shadow-xs">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                    <i data-lucide="car" class="w-3.5 h-3.5 text-primary"></i>
                    <span>Vehicles Serviced</span>
                </span>
                <h3 class="text-3xl font-bold mt-2 text-slate-800 dark:text-slate-100">{{ $vehiclesCount }}</h3>
            </div>
            <div class="text-xs text-slate-500 mt-4 font-semibold uppercase tracking-wider">Linked to client profiles</div>
        </div>

        <!-- Metrics 4: Total Jobs of the Month -->
        <div class="app-card rounded-2xl p-6 flex flex-col justify-between shadow-xs">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                    <i data-lucide="wrench" class="w-3.5 h-3.5 text-primary"></i>
                    <span>Total Jobs of the Month</span>
                </span>
                <h3 class="text-3xl font-bold mt-2 text-slate-805 dark:text-slate-100 font-mono">
                    {{ $monthlyJobsCount }}
                </h3>
            </div>
            <div class="text-xs text-slate-500 mt-4 font-semibold uppercase tracking-wider">Job cards created this month</div>
        </div>

    </div>

    <!-- Job Cards State Distribution -->
    <div class="app-card rounded-2xl p-6 shadow-xs">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-6">Job Cards Status Overview</h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            
            <div class="bg-slate-50 dark:bg-slate-950/60 rounded-xl p-4 border border-slate-200 dark:border-slate-800 text-center flex flex-col items-center">
                <span class="text-lg font-bold text-slate-700 dark:text-slate-200 block">{{ $receivedCount }}</span>
                <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider mt-1.5 flex items-center gap-1">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                    <span>Received</span>
                </span>
            </div>

            <div class="bg-slate-50 dark:bg-slate-950/60 rounded-xl p-4 border border-slate-200 dark:border-slate-800 text-center flex flex-col items-center">
                <span class="text-lg font-bold text-primary block">{{ $ongoingCount }}</span>
                <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider mt-1.5 flex items-center gap-1">
                    <i data-lucide="wrench" class="w-3.5 h-3.5"></i>
                    <span>Ongoing</span>
                </span>
            </div>

            <div class="bg-slate-50 dark:bg-slate-950/60 rounded-xl p-4 border border-slate-200 dark:border-slate-800 text-center flex flex-col items-center">
                <span class="text-lg font-bold text-red-550 block">{{ $blockedCount }}</span>
                <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider mt-1.5 flex items-center gap-1">
                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                    <span>Blocked</span>
                </span>
            </div>

            <div class="bg-slate-50 dark:bg-slate-950/60 rounded-xl p-4 border border-slate-200 dark:border-slate-800 text-center flex flex-col items-center">
                <span class="text-lg font-bold text-indigo-500 block">{{ $testingCount }}</span>
                <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider mt-1.5 flex items-center gap-1">
                    <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i>
                    <span>Testing</span>
                </span>
            </div>

            <div class="bg-slate-50 dark:bg-slate-950/60 rounded-xl p-4 border border-slate-200 dark:border-slate-800 text-center flex flex-col items-center">
                <span class="text-lg font-bold text-green-600 dark:text-green-400 block">{{ $pickupCount }}</span>
                <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider mt-1.5 flex items-center gap-1">
                    <i data-lucide="check-square" class="w-3.5 h-3.5"></i>
                    <span>Ready</span>
                </span>
            </div>

        </div>
    </div>

    <!-- Alert / Activities Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Column 1: Low Stock Alerts -->
        <div class="app-card rounded-2xl p-6 shadow-xs">
            <div class="flex items-center justify-between mb-4 border-b border-slate-200 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-red-500"></i>
                    <span>Low Stock Alerts</span>
                </h3>
                <span class="text-xs px-2.5 py-0.5 rounded bg-red-550/10 text-red-650 dark:text-red-400 border border-red-500/20 font-bold uppercase">
                    {{ $lowStockItems->count() }} Items
                </span>
            </div>

            @if($lowStockItems->isEmpty())
                <div class="text-slate-500 text-sm py-8 text-center bg-slate-50 dark:bg-slate-950/10 rounded-xl border border-slate-200 dark:border-slate-800 border-dashed">
                    All parts inventory stocks are healthy.
                </div>
            @else
                <div class="space-y-3 max-h-80 overflow-y-auto pr-2">
                    @foreach($lowStockItems as $item)
                        <div class="flex items-center justify-between p-3.5 bg-slate-50 dark:bg-slate-950/40 rounded-xl border border-slate-200 dark:border-slate-800/80">
                            <div>
                                <div class="text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $item->name }}</div>
                                <div class="text-xs font-mono text-slate-500 mt-0.5">SKU: {{ $item->sku }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-bold text-red-600 dark:text-red-400">{{ $item->quantity }} {{ $item->unit }}</div>
                                <div class="text-xs text-slate-500 mt-0.5 font-mono">Price: {{ config('app.currency', '$') }}{{ $item->price }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Column 2: Recent Activities -->
        <div class="app-card rounded-2xl p-6 shadow-xs">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-4 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-1.5">
                <i data-lucide="activity" class="w-4 h-4 text-primary"></i>
                <span>Recent Activity Feed</span>
            </h3>

            @if($recentActivities->isEmpty())
                <div class="text-slate-500 text-sm py-8 text-center bg-slate-50 dark:bg-slate-950/10 rounded-xl border border-slate-200 dark:border-slate-800 border-dashed">
                    No workshop activities logged yet.
                </div>
            @else
                <div class="space-y-4 max-h-80 overflow-y-auto pr-2">
                    @foreach($recentActivities as $act)
                        <div class="flex items-start gap-3.5 pb-3.5 border-b border-slate-100 dark:border-slate-950 last:border-0 last:pb-0">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-200 dark:bg-slate-800 text-xs shrink-0 text-slate-655 dark:text-slate-300">
                                <i data-lucide="wrench" class="w-3.5 h-3.5"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-slate-650 dark:text-slate-300">
                                    <span class="font-bold text-slate-800 dark:text-slate-100 capitalize">{{ $act->user->name }}</span>
                                    {{ $act->details }}
                                </p>
                                <div class="flex items-center gap-2 mt-1 text-xs text-slate-500">
                                    @if($act->job_card_id)
                                        <span>Job #{{ $act->job_card_id }}</span>
                                        <span>•</span>
                                    @endif
                                    <span>{{ $act->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

</div>
@endsection
