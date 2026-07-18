@extends('layouts.app')

@section('title', 'Job Cards Board')

@section('content')
<div class="space-y-6">

    <!-- Board Header Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500">Live Repair Workflow</h2>
        </div>
        
        <!-- Toggle Create Drawer / Trigger -->
        <button onclick="document.getElementById('create-job-drawer').classList.remove('hidden')"
                class="px-4 py-2.5 bg-primary hover:bg-primary-hover text-white font-medium rounded-lg text-sm transition shadow-sm flex items-center gap-1.5 cursor-pointer">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>Initialize Job Card</span>
        </button>
    </div>

    <!-- Date Filter Bar -->
    <div class="app-card rounded-2xl p-4 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form action="{{ route('job-cards.board') }}" method="GET" class="w-full flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div class="flex flex-wrap items-center gap-4 text-xs">
                <!-- Start Date -->
                <div class="flex flex-col gap-1">
                    <label for="start_date" class="font-semibold text-slate-505 dark:text-slate-400">Start Date</label>
                    <input type="date" name="start_date" id="start_date" value="{{ $startDate }}"
                           class="px-3 py-1.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs">
                </div>

                <!-- End Date -->
                <div class="flex flex-col gap-1">
                    <label for="end_date" class="font-semibold text-slate-550 dark:text-slate-400">End Date</label>
                    <input type="date" name="end_date" id="end_date" value="{{ $endDate }}"
                           class="px-3 py-1.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs">
                </div>

                <!-- Quick Selection Buttons -->
                <div class="flex items-center gap-2 pt-4">
                    <button type="button" onclick="setQuickDates('{{ date('Y-m-d') }}', '{{ date('Y-m-d') }}')"
                            class="px-2.5 py-1.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-[10px] font-bold rounded text-slate-700 dark:text-slate-300 transition cursor-pointer">
                        Today
                    </button>
                    <button type="button" onclick="setQuickDates('{{ date('Y-m-d', strtotime('-7 days')) }}', '{{ date('Y-m-d') }}')"
                            class="px-2.5 py-1.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-[10px] font-bold rounded text-slate-700 dark:text-slate-300 transition cursor-pointer">
                        Last 7 Days
                    </button>
                </div>
            </div>

            <div class="flex gap-2">
                @if($startDate !== date('Y-m-d') || $endDate !== date('Y-m-d'))
                    <a href="{{ route('job-cards.board') }}" 
                       class="px-4 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-850 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold rounded-lg text-xs flex items-center justify-center transition">
                        Reset
                    </a>
                @endif
                <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg text-xs flex items-center gap-1.5 shadow-sm transition cursor-pointer">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                    <span>Filter Board</span>
                </button>
            </div>
        </form>
    </div>

    <script>
        function setQuickDates(start, end) {
            document.getElementById('start_date').value = start;
            document.getElementById('end_date').value = end;
            document.getElementById('start_date').closest('form').submit();
        }
    </script>

    <!-- Kanban Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 items-start">

        <!-- Column Loop -->
        @php
            $columns = [
                'received-vehicle' => ['label' => 'Received', 'icon' => 'download', 'iconColor' => 'text-slate-550', 'border' => 'border-slate-200 dark:border-slate-800', 'badge' => 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400'],
                'on-going' => ['label' => 'Ongoing', 'icon' => 'wrench', 'iconColor' => 'text-primary', 'border' => 'border-primary/20', 'badge' => 'bg-primary/10 text-primary'],
                'blocked' => ['label' => 'Blocked', 'icon' => 'alert-triangle', 'iconColor' => 'text-red-500', 'border' => 'border-red-500/20', 'badge' => 'bg-red-500/10 text-red-500'],
                'testing' => ['label' => 'Testing', 'icon' => 'shield-alert', 'iconColor' => 'text-indigo-500', 'border' => 'border-indigo-500/20', 'badge' => 'bg-indigo-500/10 text-indigo-500'],
                'waiting-to-pickup' => ['label' => 'Ready', 'icon' => 'check-square', 'iconColor' => 'text-green-600 dark:text-green-400', 'border' => 'border-green-500/20', 'badge' => 'bg-green-500/10 text-green-600 dark:text-green-400'],
            ];
        @endphp

        @foreach($columns as $statusKey => $col)
            <div class="app-card rounded-2xl p-4 flex flex-col min-h-[500px] shadow-xs">
                <!-- Header -->
                <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-200 dark:border-slate-800">
                    <span class="text-sm font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                        <i data-lucide="{{ $col['icon'] }}" class="w-4 h-4 {{ $col['iconColor'] }}"></i>
                        <span>{{ $col['label'] }}</span>
                    </span>
                    <span class="text-xs px-2 py-0.5 rounded {{ $col['badge'] }} font-bold">
                        {{ $boardData[$statusKey]->count() }}
                    </span>
                </div>

                <!-- Cards list -->
                <div class="space-y-3 flex-1 overflow-y-auto">
                    @forelse($boardData[$statusKey] as $jobCard)
                        <div class="p-4 bg-slate-50/50 hover:bg-slate-100/50 dark:bg-slate-950/40 dark:hover:bg-slate-950/80 border border-slate-200 dark:border-slate-800/80 rounded-xl transition shadow-xs flex flex-col justify-between group">
                            
                            <div>
                                <!-- Card Header -->
                                <div class="flex items-start justify-between gap-2">
                                    <a href="{{ route('job-cards.show', $jobCard->id) }}" class="text-sm font-bold text-slate-800 dark:text-slate-200 hover:text-primary transition min-w-0">
                                        {{ $jobCard->card_number ?? '#' . str_pad($jobCard->id, 4, '0', STR_PAD_LEFT) }} - {{ $jobCard->vehicle->make }} {{ $jobCard->vehicle->model }}
                                    </a>
                                </div>

                                <!-- Metadata -->
                                <div class="text-[11px] text-slate-500 font-mono mt-1 leading-normal">
                                    {{ $jobCard->vehicle->plate_number }} • Client: {{ $jobCard->vehicle->client->name }}
                                </div>

                                <!-- Note snippet -->
                                <p class="text-xs text-slate-550 dark:text-slate-400 mt-2 line-clamp-2 leading-relaxed">
                                    {{ $jobCard->notes ?? 'No job notes added.' }}
                                </p>
                            </div>

                            <!-- Card Footer -->
                            <div class="mt-4 pt-3 border-t border-slate-200 dark:border-slate-800/50 flex flex-col gap-2">
                                <!-- Technicians list -->
                                <div class="flex flex-wrap gap-1">
                                    @forelse($jobCard->workers as $worker)
                                        <span class="text-[9px] px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-semibold capitalize">
                                            {{ explode(' ', $worker->name)[0] }}
                                        </span>
                                    @empty
                                        <span class="text-[9px] px-1.5 py-0.5 rounded bg-yellow-500/10 text-yellow-600 dark:text-yellow-400 font-semibold uppercase tracking-wider">Unassigned</span>
                                    @endforelse
                                </div>

                                <!-- Action bar -->
                                <div class="flex items-center justify-between mt-1 gap-2">
                                    <!-- Ticket Sum -->
                                    <span class="text-xs font-bold font-mono text-slate-700 dark:text-slate-300" title="Current Ticket Sum (Services + Parts)">
                                        {{ config('app.currency', 'Rs.') }}{{ number_format($jobCard->ticket_sum, 2) }}
                                    </span>

                                    <!-- Status quick change dropdown -->
                                    <form action="{{ route('job-cards.update-status', $jobCard->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" onchange="this.form.submit()" 
                                                class="text-[10px] bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-750 dark:text-slate-300 font-semibold py-1 px-1.5 rounded border border-slate-300 dark:border-slate-700/80 focus:outline-none cursor-pointer">
                                            @foreach(array_keys($columns) as $s)
                                                <option value="{{ $s }}" {{ $jobCard->status === $s ? 'selected' : '' }}>
                                                    {{ $columns[$s]['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </div>
                            </div>

                        </div>
                    @empty
                        <div class="text-[11px] text-slate-500 text-center py-8">
                            No job cards
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach

    </div>

</div>

<!-- Sidebar Drawer Modal: Create Job Card -->
<div id="create-job-drawer" class="fixed inset-0 z-50 overflow-hidden hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
    <div class="absolute inset-0 overflow-hidden">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-950/75 transition-opacity" onclick="document.getElementById('create-job-drawer').classList.add('hidden')"></div>

        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div class="pointer-events-auto w-screen max-w-md">
                <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-xl">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-955/40">
                        <h2 class="text-lg font-bold text-slate-850 dark:text-slate-200 flex items-center gap-2">
                            <i data-lucide="clipboard" class="w-5 h-5 text-primary"></i>
                            <span>Initialize Job Card</span>
                        </h2>
                        <button onclick="document.getElementById('create-job-drawer').classList.add('hidden')" class="text-slate-500 hover:text-slate-400 font-bold p-2">✕</button>
                    </div>

                    <form action="{{ route('job-cards.store') }}" method="POST" class="flex-1 p-6 space-y-5">
                        @csrf

                        <!-- Vehicle selection -->
                        <div>
                            <label for="vehicle_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Select Vehicle</label>
                            <select name="vehicle_id" id="vehicle_id" required
                                    class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm cursor-pointer">
                                <option value="">-- Choose registered vehicle --</option>
                                @foreach($vehicles as $veh)
                                    <option value="{{ $veh->id }}">
                                        {{ $veh->plate_number }} ({{ $veh->make }} {{ $veh->model }} - {{ $veh->client->name }})
                                    </option>
                                @endforeach
                            </select>
                            <span class="text-[11px] text-slate-500 mt-1 block leading-normal">Vehicles must be registered under a client profile first.</span>
                        </div>

                        <!-- Shop selection -->
                        <div>
                            <label for="shop_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Assign Shop Location</label>
                            <select name="shop_id" id="shop_id" required
                                    class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm cursor-pointer">
                                <option value="">-- Select location --</option>
                                @foreach($shops as $shop)
                                    <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Mileage -->
                        <div>
                            <label for="mileage" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Odometer / Mileage (Optional)</label>
                            <input type="number" name="mileage" id="mileage" placeholder="e.g., 75000" min="0"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <!-- Transportation Details -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="transportation_fee" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Transportation Fee (Rs.)</label>
                                <input type="number" step="0.01" name="transportation_fee" id="transportation_fee" placeholder="0.00" value="0.00" min="0"
                                       class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                            </div>
                            <div>
                                <label for="transportation_type" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Transport Mode / Type</label>
                                <select name="transportation_type" id="transportation_type"
                                        class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm cursor-pointer">
                                    <option value="provided">Company Provided</option>
                                    <option value="hire">Third-Party Hire</option>
                                </select>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label for="notes" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Diagnose Notes / Instructions</label>
                            <textarea name="notes" id="notes" rows="4" placeholder="Abnormal noise, replacement instructions..."
                                      class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm"></textarea>
                        </div>

                        <!-- Workers multi selection checkbox -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Assign Technicians</label>
                            <div class="space-y-2 max-h-32 overflow-y-auto p-3 bg-white dark:bg-slate-950 border border-slate-350 dark:border-slate-800 rounded-lg">
                                @foreach($workers as $worker)
                                    <label class="flex items-center text-sm text-slate-700 dark:text-slate-350 cursor-pointer">
                                        <input type="checkbox" name="workers[]" value="{{ $worker->id }}" class="h-4 w-4 text-primary focus:ring-primary rounded border border-slate-350 dark:border-slate-800">
                                        <span class="ml-2 capitalize">{{ $worker->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex gap-3">
                            <button type="submit"
                                    class="flex-1 py-2.5 px-4 bg-primary hover:bg-primary-hover text-white font-medium rounded-lg transition text-sm">
                                Create Job Card
                            </button>
                            <button type="button" onclick="document.getElementById('create-job-drawer').classList.add('hidden')"
                                    class="py-2.5 px-4 bg-slate-200 dark:bg-slate-850 hover:bg-slate-350 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-350 font-medium rounded-lg transition text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
