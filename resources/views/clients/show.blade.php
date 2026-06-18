@extends('layouts.app')

@section('title', 'Client Profile')

@section('content')
<div class="space-y-8">

    <!-- Header navigation -->
    <div class="flex items-center gap-3 border-b border-slate-800 pb-4">
        <a href="{{ route('clients.index') }}" class="text-sm font-semibold text-indigo-400 hover:text-indigo-300">
            &larr; Directory
        </a>
        <span class="text-slate-600">|</span>
        <span class="text-slate-350 font-semibold text-sm">{{ $client->name }}</span>
    </div>

    <!-- Main Workspace Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left: Edit profile and Vehicle listing -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- 1. Edit Profile Form -->
            <div class="glass-card rounded-2xl p-6">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800/80 pb-3 mb-6">
                    👤 Customer Profile Details
                </h3>

                <form action="{{ route('clients.update', $client->id) }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="name" class="block text-xs text-slate-500 mb-1">Full Name</label>
                        <input type="text" name="name" id="name" required value="{{ $client->name }}"
                               class="w-full px-4 py-2 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500 text-sm">
                    </div>

                    <div>
                        <label for="phone" class="block text-xs text-slate-500 mb-1">Phone Number</label>
                        <input type="text" name="phone" id="phone" required value="{{ $client->phone }}"
                               class="w-full px-4 py-2 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500 text-sm">
                    </div>

                    <div class="md:col-span-2">
                        <label for="email" class="block text-xs text-slate-500 mb-1">Email Address</label>
                        <input type="email" name="email" id="email" value="{{ $client->email }}"
                               class="w-full px-4 py-2 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500 text-sm">
                    </div>

                    <div class="md:col-span-2">
                        <label for="address" class="block text-xs text-slate-500 mb-1">Physical Address</label>
                        <textarea name="address" id="address" rows="3"
                                  class="w-full px-4 py-2 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500 text-sm">{{ $client->address }}</textarea>
                    </div>

                    <div class="md:col-span-2 flex items-center justify-between pt-4 border-t border-slate-800/50">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-lg text-xs transition">
                            Save Changes
                        </button>
                        
                        <!-- Delete customer profile link -->
                        <button type="button" 
                                onclick="if(confirm('Are you sure you want to delete this profile and all vehicles linked to it?')) { document.getElementById('delete-client-form').submit(); }"
                                class="text-xs text-red-500 hover:text-red-400 font-semibold p-2 hover:bg-red-500/10 rounded-lg transition">
                            Delete Profile
                        </button>
                    </div>
                </form>

                <form id="delete-client-form" action="{{ route('clients.destroy', $client->id) }}" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            </div>

            <!-- 2. Registered Vehicles list -->
            <div class="glass-card rounded-2xl p-6">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800/80 pb-3 mb-4">
                    🚗 Registered Vehicles
                </h3>

                <div class="space-y-4">
                    @forelse($client->vehicles as $veh)
                        <div class="p-4 bg-slate-900/40 rounded-xl border border-slate-850 flex items-start justify-between gap-4">
                            <div>
                                <span class="text-sm font-bold text-slate-200">{{ $veh->make }} {{ $veh->model }} ({{ $veh->year }})</span>
                                <span class="text-xs text-slate-500 font-mono block mt-1">Plate: {{ $veh->plate_number }} • VIN: {{ $veh->vin ?? 'N/A' }}</span>
                                
                                <!-- History links -->
                                <div class="flex items-center gap-2 mt-3 text-[10px] text-slate-500 font-medium">
                                    <span>Active Jobs: {{ $veh->jobCards->where('status', '!=', 'waiting-to-pickup')->count() }}</span>
                                    <span>•</span>
                                    <span>Total Repairs: {{ $veh->jobCards->count() }}</span>
                                </div>
                            </div>
                            
                            <!-- Delete vehicle form -->
                            <form action="{{ route('vehicles.destroy', $veh->id) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Remove vehicle record?')"
                                        class="text-xs text-red-500 hover:text-red-450 p-2 hover:bg-red-500/10 rounded-lg transition">
                                    Delete
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="text-slate-500 text-sm py-8 text-center bg-slate-900/20 rounded-xl border border-slate-850 border-dashed">
                            No vehicles registered for this client.
                        </div>
                    @endendif
                </div>
            </div>

        </div>

        <!-- Right: Register new Vehicle & Job History -->
        <div class="space-y-8">
            
            <!-- 1. Register Vehicle form -->
            <div class="glass-card rounded-2xl p-6 space-y-4">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800/80 pb-3">
                    + Register Vehicle
                </h3>

                <form action="{{ route('vehicles.store') }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <input type="hidden" name="client_id" value="{{ $client->id }}">

                    <div>
                        <label for="make" class="block text-slate-500 mb-1">Make (e.g. Toyota, Honda)</label>
                        <input type="text" name="make" id="make" required placeholder="Toyota"
                               class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500">
                    </div>

                    <div>
                        <label for="model" class="block text-slate-500 mb-1">Model (e.g. Prius, Civic)</label>
                        <input type="text" name="model" id="model" required placeholder="Prius"
                               class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="year" class="block text-slate-500 mb-1">Year</label>
                            <input type="number" name="year" id="year" required min="1900" max="{{ date('Y') + 1 }}" value="2018"
                                   class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label for="plate_number" class="block text-slate-500 mb-1">Plate Number</label>
                            <input type="text" name="plate_number" id="plate_number" required placeholder="CAD-1234"
                                   class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>

                    <div>
                        <label for="vin" class="block text-slate-500 mb-1">Chassis / VIN (Optional)</label>
                        <input type="text" name="vin" id="vin" placeholder="JTDKN3DU..."
                               class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500">
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-lg text-xs transition">
                        Register Vehicle to Client
                    </button>
                </form>
            </div>

            <!-- 2. Client Job Card history -->
            <div class="glass-card rounded-2xl p-6">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800/80 pb-3 mb-4">
                    📋 Repair History
                </h3>

                <div class="space-y-3 max-h-[300px] overflow-y-auto pr-2">
                    @forelse($client->jobCards as $jc)
                        <div class="p-3 bg-slate-900/35 rounded-xl border border-slate-850 flex items-center justify-between gap-3 text-xs">
                            <div>
                                <a href="{{ route('job-cards.show', $jc->id) }}" class="font-bold text-slate-200 hover:text-indigo-400">
                                    Job #{{ str_pad($jc->id, 4, '0', STR_PAD_LEFT) }}
                                </a>
                                <div class="text-[10px] text-slate-500 mt-0.5 capitalize">{{ $jc->vehicle->make }} • Status: {{ str_replace('-', ' ', $jc->status) }}</div>
                            </div>
                            <div class="text-right">
                                <span class="font-semibold text-slate-300 block">${{ number_format($jc->estimated_cost, 2) }}</span>
                                <span class="text-[9px] text-slate-500 block mt-0.5">{{ $jc->created_at->format('Y-m-d') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-slate-650 text-xs text-center py-4">
                            No repair history found.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
