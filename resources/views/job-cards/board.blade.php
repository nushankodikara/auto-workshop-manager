@extends('layouts.app')

@section('title', 'Job Cards Kanban')

@section('content')
<div class="space-y-6">

    <!-- Board Header Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-500">Live Repair Workflow</h2>
        </div>
        
        <!-- Toggle Create Drawer / Trigger -->
        <button onclick="document.getElementById('create-job-drawer').classList.remove('hidden')"
                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-lg text-sm transition transform hover:-translate-y-0.5 shadow-md shadow-indigo-600/10 self-start">
            + Initialize Job Card
        </button>
    </div>

    <!-- Kanban Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 items-start">

        <!-- Column Loop -->
        @php
            $columns = [
                'received-vehicle' => ['label' => '📥 Received', 'border' => 'border-slate-800', 'badge' => 'bg-slate-800 text-slate-400'],
                'on-going' => ['label' => '⚙️ On-Going', 'border' => 'border-indigo-500/20', 'badge' => 'bg-indigo-500/10 text-indigo-400'],
                'blocked' => ['label' => '⚠️ Blocked', 'border' => 'border-red-500/20', 'badge' => 'bg-red-500/10 text-red-400'],
                'testing' => ['label' => '🧪 Testing', 'border' => 'border-blue-500/20', 'badge' => 'bg-blue-500/10 text-blue-400'],
                'waiting-to-pickup' => ['label' => '📦 Ready to Pickup', 'border' => 'border-emerald-500/20', 'badge' => 'bg-emerald-500/10 text-emerald-400'],
            ];
        @endphp

        @foreach($columns as $statusKey => $col)
            <div class="glass-card rounded-2xl p-4 border {{ $col['border'] }} flex flex-col min-h-[500px]">
                <!-- Header -->
                <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-800/80">
                    <span class="text-sm font-semibold text-slate-200">{{ $col['label'] }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $col['badge'] }} font-semibold">
                        {{ $boardData[$statusKey]->count() }}
                    </span>
                </div>

                <!-- Cards list -->
                <div class="space-y-3 flex-1 overflow-y-auto">
                    @forelse($boardData[$statusKey] as $jobCard)
                        <div class="p-4 bg-slate-900/80 hover:bg-slate-900 border border-slate-800/60 rounded-xl transition shadow-sm flex flex-col justify-between group">
                            
                            <div>
                                <!-- Card Header -->
                                <div class="flex items-start justify-between gap-2">
                                    <a href="{{ route('job-cards.show', $jobCard->id) }}" class="text-sm font-bold text-slate-200 hover:text-indigo-400 transition min-w-0">
                                        #{{ str_pad($jobCard->id, 4, '0', STR_PAD_LEFT) }} - {{ $jobCard->vehicle->make }} {{ $jobCard->vehicle->model }}
                                    </a>
                                </div>

                                <!-- Metadata -->
                                <div class="text-[11px] text-slate-500 font-mono mt-1">
                                    {{ $jobCard->vehicle->plate_number }} • Client: {{ $jobCard->vehicle->client->name }}
                                </div>

                                <!-- Note snippet -->
                                <p class="text-xs text-slate-400 mt-2 line-clamp-2">
                                    {{ $jobCard->notes ?? 'No job notes added.' }}
                                </p>
                            </div>

                            <!-- Card Footer -->
                            <div class="mt-4 pt-3 border-t border-slate-800/50 flex flex-col gap-2">
                                <!-- Technicians list -->
                                <div class="flex flex-wrap gap-1">
                                    @forelse($jobCard->workers as $worker)
                                        <span class="text-[9px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-400 font-medium capitalize">
                                            {{ explode(' ', $worker->name)[0] }}
                                        </span>
                                    @empty
                                        <span class="text-[9px] px-1.5 py-0.5 rounded bg-yellow-500/10 text-yellow-400 font-medium">Unassigned</span>
                                    @endforelse
                                </div>

                                <!-- Action bar -->
                                <div class="flex items-center justify-between mt-1 gap-2">
                                    <!-- Estimated Cost -->
                                    <span class="text-xs font-semibold text-slate-300">
                                        ${{ number_format($jobCard->estimated_cost, 2) }}
                                    </span>

                                    <!-- Status quick change dropdown -->
                                    <form action="{{ route('job-cards.update-status', $jobCard->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" onchange="this.form.submit()" 
                                                class="text-[10px] bg-slate-800 hover:bg-slate-750 text-slate-300 font-medium py-1 px-1.5 rounded border border-slate-700/80 focus:outline-none cursor-pointer">
                                            @foreach(array_keys($columns) as $s)
                                                <option value="{{ $s }}" {{ $jobCard->status === $s ? 'selected' : '' }}>
                                                    {{ explode(' ', $columns[$s]['label'])[1] ?? $columns[$s]['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </div>
                            </div>

                        </div>
                    @empty
                        <div class="text-[11px] text-slate-600 text-center py-8">
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
                <div class="flex h-full flex-col overflow-y-scroll bg-slate-900 border-l border-slate-800 shadow-xl">
                    <div class="p-6 border-b border-slate-800 flex items-center justify-between bg-slate-950">
                        <h2 class="text-lg font-bold text-slate-200">Initialize Job Card</h2>
                        <button onclick="document.getElementById('create-job-drawer').classList.add('hidden')" class="text-slate-500 hover:text-slate-400 font-bold p-2">✕</button>
                    </div>

                    <form action="{{ route('job-cards.store') }}" method="POST" class="flex-1 p-6 space-y-5">
                        @csrf

                        <!-- Vehicle selection -->
                        <div>
                            <label for="vehicle_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Select Vehicle</label>
                            <select name="vehicle_id" id="vehicle_id" required
                                    class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500 text-sm">
                                <option value="">-- Choose registered vehicle --</option>
                                @foreach($vehicles as $veh)
                                    <option value="{{ $veh->id }}">
                                        {{ $veh->plate_number }} ({{ $veh->make }} {{ $veh->model }} - {{ $veh->client->name }})
                                    </option>
                                @endforeach
                            </select>
                            <span class="text-[11px] text-slate-500 mt-1 block">Vehicles must be registered under a client profile first.</span>
                        </div>

                        <!-- Shop selection -->
                        <div>
                            <label for="shop_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Assign Shop Location</label>
                            <select name="shop_id" id="shop_id" required
                                    class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500 text-sm">
                                <option value="">-- Select location --</option>
                                @foreach($shops as $shop)
                                    <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Estimated Cost -->
                        <div>
                            <label for="estimated_cost" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Estimated Cost ($)</label>
                            <input type="number" step="0.01" name="estimated_cost" id="estimated_cost" required value="0.00"
                                   class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500 text-sm">
                        </div>

                        <!-- Notes -->
                        <div>
                            <label for="notes" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Diagnose Notes / Instructions</label>
                            <textarea name="notes" id="notes" rows="4" placeholder="Abnormal noise, replacement instructions..."
                                      class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500 text-sm"></textarea>
                        </div>

                        <!-- Workers multi selection checkbox -->
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Assign Technicians</label>
                            <div class="space-y-2 max-h-32 overflow-y-auto p-3 bg-slate-950 border border-slate-800 rounded-lg">
                                @foreach($workers as $worker)
                                    <label class="flex items-center text-sm text-slate-350 cursor-pointer">
                                        <input type="checkbox" name="workers[]" value="{{ $worker->id }}" class="h-4 w-4 bg-slate-900 border-slate-850 text-indigo-600 rounded focus:ring-0">
                                        <span class="ml-2 capitalize">{{ $worker->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="pt-4 border-t border-slate-800 flex gap-3">
                            <button type="submit"
                                    class="flex-1 py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-lg transition text-sm">
                                Create Job Card
                            </button>
                            <button type="button" onclick="document.getElementById('create-job-drawer').classList.add('hidden')"
                                    class="py-2.5 px-4 bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium rounded-lg transition text-sm">
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
