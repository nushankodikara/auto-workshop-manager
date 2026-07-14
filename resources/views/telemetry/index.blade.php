@extends('layouts.app')

@section('title', 'Tracker Telemetry & Sync')

@section('content')
<div class="space-y-6">

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-4">
        <div>
            <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <i data-lucide="activity" class="w-6 h-6 text-primary"></i>
                <span>Tracker Telemetry & Sync</span>
            </h2>
            <p class="text-xs text-slate-500 mt-1">Monitor companion PWA metrics, approve client-added vehicles, and verify fuel/expense telemetry.</p>
        </div>
        <div class="flex items-center gap-3">
            <form action="{{ route('telemetry.sync') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2.5 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg text-xs transition shadow-sm flex items-center gap-2 cursor-pointer">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    <span>Sync Telemetry Now</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Last Synced -->
        <div class="app-card rounded-2xl p-5 shadow-xs flex items-center gap-4">
            <div class="p-3.5 bg-blue-500/10 rounded-xl">
                <i data-lucide="calendar" class="w-5 h-5 text-blue-500"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Last Synced</p>
                <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-0.5">
                    @if($lastSyncTime)
                        {{ date('Y-m-d H:i:s', $lastSyncTime) }}
                    @else
                        Never Synced
                    @endif
                </h4>
            </div>
        </div>

        <!-- Synced Vehicles -->
        <div class="app-card rounded-2xl p-5 shadow-xs flex items-center gap-4">
            <div class="p-3.5 bg-emerald-500/10 rounded-xl">
                <i data-lucide="car" class="w-5 h-5 text-emerald-500"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Synced Vehicles</p>
                <h4 class="text-lg font-bold text-slate-800 dark:text-slate-100 mt-0.5">{{ $syncedVehiclesCount }}</h4>
            </div>
        </div>

        <!-- Pending Approvals -->
        <div class="app-card rounded-2xl p-5 shadow-xs flex items-center gap-4">
            <div class="p-3.5 bg-amber-500/10 rounded-xl">
                <i data-lucide="clock" class="w-5 h-5 text-amber-500"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pending Approvals</p>
                <h4 class="text-lg font-bold text-slate-800 dark:text-slate-100 mt-0.5">{{ $pendingVehiclesCount }}</h4>
            </div>
        </div>

        <!-- Total Expenditure -->
        <div class="app-card rounded-2xl p-5 shadow-xs flex items-center gap-4">
            <div class="p-3.5 bg-red-500/10 rounded-xl">
                <i data-lucide="trending-up" class="w-5 h-5 text-red-500"></i>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Telemetry cost</p>
                <h4 class="text-lg font-bold text-slate-800 dark:text-slate-100 mt-0.5">Rs. {{ number_format($totalExpenditure, 2) }}</h4>
            </div>
        </div>
    </div>

    <!-- 1. Pending Approvals Panel -->
    <div class="app-card rounded-2xl overflow-hidden shadow-xs border border-amber-500/20">
        <div class="p-6 border-b border-slate-200 dark:border-slate-800 bg-amber-500/5 flex items-center gap-2">
            <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-500"></i>
            <h3 class="text-sm font-bold uppercase tracking-wider text-amber-600 dark:text-amber-500">Pending Vehicle Approvals ({{ count($pendingVehicles) }})</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-950/20 text-slate-500 border-b border-slate-200 dark:border-slate-800">
                        <th class="p-4 font-semibold">Make & Model</th>
                        <th class="p-4 font-semibold">Plate Number</th>
                        <th class="p-4 font-semibold">Odometer</th>
                        <th class="p-4 font-semibold">Tracker User / Owner</th>
                        <th class="p-4 font-semibold">CRM Match Status</th>
                        <th class="p-4 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-900">
                    @forelse($pendingVehicles as $veh)
                        <tr class="hover:bg-slate-50/30 dark:hover:bg-slate-900/10">
                            <td class="p-4">
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $veh->make }}</span>
                                <span class="text-slate-500 text-xs ml-1">{{ $veh->model }} ({{ $veh->year ?: 'N/A' }})</span>
                            </td>
                            <td class="p-4 font-mono font-bold text-slate-700 dark:text-slate-300">{{ $veh->plate_number }}</td>
                            <td class="p-4 font-mono">{{ number_format($veh->current_odometer) }} km</td>
                            <td class="p-4">
                                @if($veh->user)
                                    <div class="font-medium text-slate-800 dark:text-slate-200">{{ $veh->user->first_name }} {{ $veh->user->last_name }}</div>
                                    <div class="text-xs text-slate-500 font-mono">{{ $veh->user->phone }}</div>
                                @else
                                    <span class="text-red-500 text-xs">Missing Owner Profile</span>
                                @endif
                            </td>
                            <td class="p-4">
                                @if($veh->matched_client)
                                    <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 bg-emerald-500/10 text-emerald-500 font-semibold rounded-full">
                                        <i data-lucide="check" class="w-3 h-3"></i>
                                        <span>Matched CRM Client: {{ $veh->matched_client->name }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 bg-amber-500/10 text-amber-500 font-semibold rounded-full">
                                        <i data-lucide="plus" class="w-3 h-3"></i>
                                        <span>Will create new Client Profile</span>
                                    </span>
                                @endif
                            </td>
                            <td class="p-4 text-right">
                                <form action="{{ route('telemetry.approve', $veh->id) }}" method="POST" onsubmit="return confirm('Approve vehicle & link to customer profile?')" class="inline-block">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs transition shadow-sm cursor-pointer">
                                        Approve & Link
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400 dark:text-slate-655 font-semibold">
                                No pending client vehicle additions.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. Synced Vehicles Directory -->
    <div class="app-card rounded-2xl overflow-hidden shadow-xs">
        <div class="p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/20 flex items-center justify-between">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Synced companion Vehicles ({{ count($verifiedVehicles) }})</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-950/20 text-slate-500 border-b border-slate-200 dark:border-slate-800">
                        <th class="p-4 font-semibold">Vehicle</th>
                        <th class="p-4 font-semibold">Plate Number</th>
                        <th class="p-4 font-semibold">Odometer</th>
                        <th class="p-4 font-semibold">Owner (Customer Directory)</th>
                        <th class="p-4 font-semibold">Expenditure</th>
                        <th class="p-4 font-semibold">Fuel Economy</th>
                        <th class="p-4 font-semibold text-right">Telemetry logs</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-900">
                    @forelse($verifiedVehicles as $veh)
                        <tr class="hover:bg-slate-50/30 dark:hover:bg-slate-900/10">
                            <td class="p-4">
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $veh->make }}</span>
                                <span class="text-slate-500 text-xs ml-1">{{ $veh->model }} ({{ $veh->year ?: 'N/A' }})</span>
                            </td>
                            <td class="p-4 font-mono font-bold text-slate-700 dark:text-slate-300">{{ $veh->plate_number }}</td>
                            <td class="p-4 font-mono">{{ number_format($veh->current_odometer) }} km</td>
                            <td class="p-4">
                                @if($veh->matched_client)
                                    <a href="{{ route('clients.show', $veh->matched_client->id) }}" class="text-primary hover:underline font-semibold flex items-center gap-1">
                                        <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                        <span>{{ $veh->matched_client->name }}</span>
                                    </a>
                                    <span class="text-slate-500 text-xs font-mono">{{ $veh->matched_client->phone }}</span>
                                @else
                                    <span class="text-red-500 text-xs font-semibold">Unlinked CRM Customer</span>
                                @endif
                            </td>
                            <td class="p-4 font-semibold text-red-500 font-mono">
                                Rs. {{ number_format($veh->total_spent, 2) }}
                            </td>
                            <td class="p-4 font-mono">
                                @if($veh->fuel_economy)
                                    <span class="inline-flex items-center gap-1 font-bold text-emerald-500">
                                        <i data-lucide="gauge" class="w-3.5 h-3.5"></i>
                                        <span>{{ number_format($veh->fuel_economy, 2) }} km/L</span>
                                    </span>
                                @else
                                    <span class="text-slate-500 text-xs">Insufficient fuel logs</span>
                                @endif
                            </td>
                            <td class="p-4 text-right">
                                <button type="button" onclick="toggleModal('modal-{{ $veh->id }}')" class="px-3 py-1.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-750 dark:text-slate-250 font-bold rounded-lg text-xs transition border border-transparent shadow-xs cursor-pointer flex items-center gap-1 ml-auto">
                                    <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                    <span>View Logs ({{ $veh->fuelLogs->count() + $veh->expenseLogs->count() }})</span>
                                </button>
                            </td>
                        </tr>

                        <!-- Modal Overlay for Logs -->
                        <div id="modal-{{ $veh->id }}" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
                            <div class="app-card rounded-2xl w-full max-w-4xl max-h-[85vh] overflow-hidden shadow-2xl flex flex-col border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                                <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-850 dark:text-slate-100 flex items-center gap-2">
                                            <i data-lucide="file-spreadsheet" class="w-5 h-5 text-primary"></i>
                                            <span>Telemetry logs: {{ $veh->make }} {{ $veh->model }} ({{ $veh->plate_number }})</span>
                                        </h3>
                                        <p class="text-xs text-slate-500 mt-1">Historical driver-submitted refuels and expense logs.</p>
                                    </div>
                                    <button type="button" onclick="toggleModal('modal-{{ $veh->id }}')" class="p-2 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition">
                                        <i data-lucide="x" class="w-5 h-5"></i>
                                    </button>
                                </div>

                                <div class="flex-1 overflow-y-auto p-6 space-y-6">
                                    <!-- Fuel Logs Section -->
                                    <div>
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-1.5">
                                            <i data-lucide="gauge" class="w-4 h-4 text-emerald-500"></i>
                                            <span>Fuel refuel Logs ({{ $veh->fuelLogs->count() }})</span>
                                        </h4>
                                        <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl">
                                            <table class="w-full text-left text-xs border-collapse">
                                                <thead>
                                                    <tr class="bg-slate-50 dark:bg-slate-955/20 text-slate-500 border-b border-slate-200 dark:border-slate-800">
                                                        <th class="p-3 font-semibold">Date</th>
                                                        <th class="p-3 font-semibold">Odometer</th>
                                                        <th class="p-3 font-semibold">Fuel Type</th>
                                                        <th class="p-3 font-semibold">Liters</th>
                                                        <th class="p-3 font-semibold">Price per L</th>
                                                        <th class="p-3 font-semibold text-right">Total Cost</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100 dark:divide-slate-900">
                                                    @forelse($veh->fuelLogs->sortByDesc('logged_at') as $fl)
                                                        <tr>
                                                            <td class="p-3 font-medium">{{ date('Y-m-d', $fl->logged_at) }}</td>
                                                            <td class="p-3 font-mono">{{ number_format($fl->odometer_km) }} km</td>
                                                            <td class="p-3">{{ $fl->fuel_type }}</td>
                                                            <td class="p-3 font-mono">{{ $fl->liters ? number_format($fl->liters, 2) . ' L' : 'N/A' }}</td>
                                                            <td class="p-3 font-mono">@if($fl->price_per_liter) Rs.{{ number_format($fl->price_per_liter, 2) }} @else N/A @endif</td>
                                                            <td class="p-3 font-mono font-bold text-emerald-600 dark:text-emerald-500 text-right">Rs. {{ number_format($fl->total_cost ?: 0, 2) }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="6" class="p-4 text-center text-slate-450 dark:text-slate-600">No fuel records logged.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Expense Logs Section -->
                                    <div>
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-1.5">
                                            <i data-lucide="credit-card" class="w-4 h-4 text-red-500"></i>
                                            <span>General Expenses logs ({{ $veh->expenseLogs->count() }})</span>
                                        </h4>
                                        <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl">
                                            <table class="w-full text-left text-xs border-collapse">
                                                <thead>
                                                    <tr class="bg-slate-50 dark:bg-slate-955/20 text-slate-500 border-b border-slate-200 dark:border-slate-800">
                                                        <th class="p-3 font-semibold">Date</th>
                                                        <th class="p-3 font-semibold">Odometer</th>
                                                        <th class="p-3 font-semibold">Category</th>
                                                        <th class="p-3 font-semibold">Notes</th>
                                                        <th class="p-3 font-semibold text-right">Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100 dark:divide-slate-900">
                                                    @forelse($veh->expenseLogs->sortByDesc('logged_at') as $el)
                                                        <tr>
                                                            <td class="p-3 font-medium">{{ date('Y-m-d', $el->logged_at) }}</td>
                                                            <td class="p-3 font-mono">{{ number_format($el->odometer_km) }} km</td>
                                                            <td class="p-3 font-bold uppercase tracking-wider text-xs text-red-500">{{ $el->category }}</td>
                                                            <td class="p-3 text-slate-500">{{ $el->notes ?: '—' }}</td>
                                                            <td class="p-3 font-mono font-bold text-red-500 text-right">Rs. {{ number_format($el->amount, 2) }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="p-4 text-center text-slate-450 dark:text-slate-600">No general expenses logged.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-6 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                                    <button type="button" onclick="toggleModal('modal-{{ $veh->id }}')" class="px-4 py-2 bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-semibold rounded-lg text-sm transition cursor-pointer">
                                        Close Logs
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400 dark:text-slate-655 font-semibold">
                                No verified vehicles linked to companion app yet. Approve pending vehicles or link clients.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function toggleModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.toggle('hidden');
        }
    }
</script>
@endsection
