@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-8">

    <!-- KPI Summary Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        
        <!-- Metrics 1: Active Job Cards -->
        <div class="glass-card rounded-2xl p-6 flex flex-col justify-between">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-indigo-400">Active Jobs</span>
                <h3 class="text-3xl font-bold mt-2 text-slate-100">{{ $activeJobCardsCount }}</h3>
            </div>
            <div class="text-xs text-slate-500 mt-4">Currently inside the shop</div>
        </div>

        <!-- Metrics 2: Total Clients -->
        <div class="glass-card rounded-2xl p-6 flex flex-col justify-between">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-blue-400">Total Clients</span>
                <h3 class="text-3xl font-bold mt-2 text-slate-100">{{ $clientsCount }}</h3>
            </div>
            <div class="text-xs text-slate-500 mt-4">Registered profiles</div>
        </div>

        <!-- Metrics 3: Total Vehicles -->
        <div class="glass-card rounded-2xl p-6 flex flex-col justify-between">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-400">Vehicles Serviced</span>
                <h3 class="text-3xl font-bold mt-2 text-slate-100">{{ $vehiclesCount }}</h3>
            </div>
            <div class="text-xs text-slate-500 mt-4">Linked to client profiles</div>
        </div>

        <!-- Metrics 4: Monthly Revenue -->
        <div class="glass-card rounded-2xl p-6 flex flex-col justify-between">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-amber-400">Monthly Revenue</span>
                <h3 class="text-3xl font-bold mt-2 text-slate-100">${{ number_format($monthlyRevenue, 2) }}</h3>
            </div>
            <div class="text-xs text-slate-500 mt-4">Sum of paid invoices this month</div>
        </div>

    </div>

    <!-- Job Cards State Distribution -->
    <div class="glass-card rounded-2xl p-6">
        <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-6">Job Cards Status Overview</h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            
            <div class="bg-slate-900/60 rounded-xl p-4 border border-slate-800 text-center">
                <span class="text-lg font-bold text-slate-200 block">{{ $receivedCount }}</span>
                <span class="text-xs text-slate-500 font-medium">📥 Received</span>
            </div>

            <div class="bg-slate-900/60 rounded-xl p-4 border border-slate-800 text-center">
                <span class="text-lg font-bold text-indigo-400 block">{{ $ongoingCount }}</span>
                <span class="text-xs text-slate-500 font-medium">⚙️ Ongoing</span>
            </div>

            <div class="bg-slate-900/60 rounded-xl p-4 border border-slate-800 text-center">
                <span class="text-lg font-bold text-red-400 block">{{ $blockedCount }}</span>
                <span class="text-xs text-slate-500 font-medium">⚠️ Blocked</span>
            </div>

            <div class="bg-slate-900/60 rounded-xl p-4 border border-slate-800 text-center">
                <span class="text-lg font-bold text-blue-400 block">{{ $testingCount }}</span>
                <span class="text-xs text-slate-500 font-medium">🧪 Testing</span>
            </div>

            <div class="bg-slate-900/60 rounded-xl p-4 border border-slate-800 text-center">
                <span class="text-lg font-bold text-emerald-400 block">{{ $pickupCount }}</span>
                <span class="text-xs text-slate-500 font-medium">📦 Ready to Pickup</span>
            </div>

        </div>
    </div>

    <!-- Alert / Activities Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Column 1: Low Stock Alerts -->
        <div class="glass-card rounded-2xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400">⚠️ Low Stock Alerts</h3>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-red-500/10 text-red-400 border border-red-500/20 font-medium">
                    {{ $lowStockItems->count() }} Items Alerted
                </span>
            </div>

            @if($lowStockItems->isEmpty())
                <div class="text-slate-500 text-sm py-8 text-center bg-slate-900/20 rounded-xl border border-slate-800 border-dashed">
                    All parts inventory stocks are healthy.
                </div>
            @else
                <div class="space-y-3 max-h-80 overflow-y-auto pr-2">
                    @foreach($lowStockItems as $item)
                        <div class="flex items-center justify-between p-3.5 bg-slate-900/50 rounded-xl border border-slate-800/80">
                            <div>
                                <div class="text-sm font-semibold text-slate-200">{{ $item->name }}</div>
                                <div class="text-xs font-mono text-slate-500 mt-0.5">SKU: {{ $item->sku }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-bold text-red-400">{{ $item->quantity }} {{ $item->unit }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">Price: ${{ $item->price }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Column 2: Recent Activities -->
        <div class="glass-card rounded-2xl p-6">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-4">📝 Recent Activity Feed</h3>

            @if($recentActivities->isEmpty())
                <div class="text-slate-500 text-sm py-8 text-center bg-slate-900/20 rounded-xl border border-slate-800 border-dashed">
                    No workshop activities logged yet.
                </div>
            @else
                <div class="space-y-4 max-h-80 overflow-y-auto pr-2">
                    @foreach($recentActivities as $act)
                        <div class="flex items-start gap-3.5 pb-3.5 border-b border-slate-900 last:border-0 last:pb-0">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-800 text-xs shrink-0">
                                🔧
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-slate-300">
                                    <span class="font-semibold text-slate-200">{{ $act->user->name }}</span>
                                    {{ $act->details }}
                                </p>
                                <div class="flex items-center gap-2 mt-1 text-xs text-slate-500">
                                    <span>Job #{{ $act->job_card_id }}</span>
                                    <span>•</span>
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
