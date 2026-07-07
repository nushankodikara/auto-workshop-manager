@extends('layouts.app')

@section('title', 'Client Profile')

@section('content')
<div class="space-y-6">

    <!-- Header navigation -->
    <div class="flex items-center gap-3 border-b border-slate-200 dark:border-slate-800 pb-4">
        <a href="{{ route('clients.index') }}" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            <span>Directory</span>
        </a>
        <span class="text-slate-400">|</span>
        <span class="text-slate-650 dark:text-slate-350 font-semibold text-sm">{{ $client->name }}</span>
    </div>

    <!-- Main Workspace Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left: Edit profile and Vehicle listing -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- 1. Edit Profile Form -->
            <div class="app-card rounded-2xl p-6 shadow-xs">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-450 border-b border-slate-200 dark:border-slate-800 pb-3 mb-6 flex items-center gap-2">
                    <i data-lucide="user" class="w-4 h-4 text-primary"></i>
                    <span>Customer Profile Details</span>
                </h3>

                <form action="{{ route('clients.update', $client->id) }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="name" class="block text-xs text-slate-500 mb-1 font-semibold">Full Name</label>
                        <input type="text" name="name" id="name" required value="{{ $client->name }}"
                               class="w-full px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                    </div>

                    <div>
                        <label for="phone" class="block text-xs text-slate-500 mb-1 font-semibold">Phone Number</label>
                        <input type="text" name="phone" id="phone" required value="{{ $client->phone }}"
                               class="w-full px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                    </div>

                    <div class="md:col-span-2">
                        <label for="email" class="block text-xs text-slate-500 mb-1 font-semibold">Email Address</label>
                        <input type="email" name="email" id="email" value="{{ $client->email }}"
                               class="w-full px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                    </div>

                    <div class="md:col-span-2">
                        <label for="address" class="block text-xs text-slate-500 mb-1 font-semibold">Physical Address</label>
                        <textarea name="address" id="address" rows="3"
                                  class="w-full px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">{{ $client->address }}</textarea>
                    </div>

                    <div class="md:col-span-2 flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-800">
                        <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg text-xs transition shadow-sm">
                            Save Changes
                        </button>
                        
                        <!-- Delete customer profile link -->
                        <button type="button" 
                                onclick="if(confirm('Are you sure you want to delete this profile and all vehicles linked to it?')) { document.getElementById('delete-client-form').submit(); }"
                                class="text-xs text-red-550 dark:text-red-400 font-semibold p-2 hover:bg-red-500/10 rounded-lg transition border border-transparent hover:border-red-500/20">
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
            <div class="app-card rounded-2xl p-6 shadow-xs">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-455 border-b border-slate-200 dark:border-slate-800 pb-3 mb-4 flex items-center gap-2">
                    <i data-lucide="car" class="w-4 h-4 text-primary"></i>
                    <span>Registered Vehicles</span>
                </h3>

                <div class="space-y-4">
                    @forelse($client->vehicles as $veh)
                        <div class="p-4 bg-slate-50 dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                            <div>
                                <span class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ $veh->make }} {{ $veh->model }} ({{ $veh->year }})</span>
                                <span class="text-xs text-slate-500 font-mono block mt-1">
                                    Plate: {{ $veh->plate_number }} • VIN: {{ $veh->vin ?? 'N/A' }} • Odo: {{ $veh->mileage ? number_format($veh->mileage) . ' km' : 'N/A' }}
                                </span>
                                
                                <!-- History links -->
                                <div class="flex items-center gap-2 mt-3 text-[10px] text-slate-500 font-semibold uppercase tracking-wider">
                                    <span>Active Jobs: {{ $veh->jobCards->where('status', '!=', 'waiting-to-pickup')->count() }}</span>
                                    <span>•</span>
                                    <span>Total Repairs: {{ $veh->jobCards->count() }}</span>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2 mt-2 sm:mt-0">
                                <a href="{{ route('vehicles.history', $veh->id) }}"
                                   class="text-xs text-primary hover:bg-primary/10 font-semibold px-2.5 py-1 rounded transition border border-transparent hover:border-primary/20">
                                    History
                                </a>
                                <button onclick='openEditVehicleDrawer(@json($veh))'
                                        class="text-xs text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 font-semibold px-2.5 py-1 rounded transition border border-transparent hover:border-slate-300 dark:hover:border-slate-700">
                                    Edit
                                </button>
                                <form action="{{ route('vehicles.destroy', $veh->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Remove vehicle record?')"
                                            class="text-xs text-red-650 dark:text-red-400 hover:bg-red-500/10 hover:text-red-500 font-semibold px-2.5 py-1 rounded transition border border-transparent hover:border-red-500/20">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="text-slate-500 text-sm py-8 text-center bg-slate-50 dark:bg-slate-950/20 rounded-xl border border-slate-200 dark:border-slate-800 border-dashed">
                            No vehicles registered for this client.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Right: Register new Vehicle & Job History -->
        <div class="space-y-8">
            
            <!-- 1. Register Vehicle form -->
            <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-455 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-2">
                    <i data-lucide="plus-circle" class="w-4 h-4 text-primary"></i>
                    <span>Register Vehicle</span>
                </h3>

                <form action="{{ route('vehicles.store') }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <input type="hidden" name="client_id" value="{{ $client->id }}">

                    <div>
                        <label for="make" class="block text-slate-500 mb-1 font-semibold">Make (e.g. Toyota, Honda)</label>
                        <input type="text" name="make" id="make" required placeholder="Toyota"
                               class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary">
                    </div>

                    <div>
                        <label for="model" class="block text-slate-500 mb-1 font-semibold">Model (e.g. Prius, Civic)</label>
                        <input type="text" name="model" id="model" required placeholder="Prius"
                               class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="year" class="block text-slate-500 mb-1 font-semibold">Year</label>
                            <input type="number" name="year" id="year" required min="1900" max="{{ date('Y') + 1 }}" value="2018"
                                   class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label for="plate_number" class="block text-slate-500 mb-1 font-semibold">Plate Number</label>
                            <input type="text" name="plate_number" id="plate_number" required placeholder="CAD-1234"
                                   class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary">
                        </div>
                    </div>

                    <div>
                        <label for="vin" class="block text-slate-500 mb-1 font-semibold">Chassis / VIN (Optional)</label>
                        <input type="text" name="vin" id="vin" placeholder="JTDKN3DU..."
                               class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary">
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg text-xs transition shadow-sm">
                        Register Vehicle to Client
                    </button>
                </form>
            </div>

            <!-- 2. Client Job Card history -->
            <div class="app-card rounded-2xl p-6 shadow-xs">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-455 border-b border-slate-200 dark:border-slate-800 pb-3 mb-4 flex items-center gap-2">
                    <i data-lucide="history" class="w-4 h-4 text-primary"></i>
                    <span>Repair History</span>
                </h3>

                <div class="space-y-3 max-h-[300px] overflow-y-auto pr-2">
                    @forelse($client->jobCards as $jc)
                        <div class="p-3 bg-slate-50 dark:bg-slate-900/35 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3 text-xs">
                            <div>
                                <a href="{{ route('job-cards.show', $jc->id) }}" class="font-bold text-slate-800 dark:text-slate-200 hover:text-primary">
                                    Job #{{ str_pad($jc->id, 4, '0', STR_PAD_LEFT) }}
                                </a>
                                <div class="text-[10px] text-slate-500 mt-0.5 capitalize">{{ $jc->vehicle->make }} • Status: {{ str_replace('-', ' ', $jc->status) }}</div>
                            </div>
                            <div class="text-right">
                                <span class="font-semibold text-slate-700 dark:text-slate-350 block font-mono">{{ config('app.currency', 'Rs.') }}{{ number_format($jc->ticket_sum, 2) }}</span>
                                <span class="text-[9px] text-slate-500 block mt-0.5">{{ $jc->created_at->format('Y-m-d') }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-slate-400 text-xs text-center py-4">
                            No repair history found.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>

<!-- Sidebar Drawer Modal: Edit Vehicle -->
<div id="edit-vehicle-drawer" class="fixed inset-0 z-50 overflow-hidden hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute inset-0 bg-slate-955/75 transition-opacity" onclick="document.getElementById('edit-vehicle-drawer').classList.add('hidden')"></div>

        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div class="pointer-events-auto w-screen max-w-md">
                <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-xl">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-955/40">
                        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <i data-lucide="edit" class="w-5 h-5 text-primary"></i>
                            <span>Edit Vehicle Details</span>
                        </h2>
                        <button onclick="document.getElementById('edit-vehicle-drawer').classList.add('hidden')" class="text-slate-500 hover:text-slate-400 font-bold p-2">✕</button>
                    </div>

                    <form id="edit-vehicle-form" action="" method="POST" class="flex-1 p-6 space-y-5">
                        @csrf
                        @method('PUT')

                        <!-- Make -->
                        <div>
                            <label for="edit_make" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Make</label>
                            <input type="text" name="make" id="edit_make" required
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <!-- Model -->
                        <div>
                            <label for="edit_model" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Model</label>
                            <input type="text" name="model" id="edit_model" required
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <!-- Year -->
                        <div>
                            <label for="edit_year" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Year</label>
                            <input type="number" name="year" id="edit_year" required min="1900" max="{{ date('Y') + 1 }}"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <!-- Plate Number -->
                        <div>
                            <label for="edit_plate_number" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Vehicle Reg Number (Plate)</label>
                            <input type="text" name="plate_number" id="edit_plate_number" required
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <!-- VIN -->
                        <div>
                            <label for="edit_vin" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">VIN / Chassis Number (Optional)</label>
                            <input type="text" name="vin" id="edit_vin"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <!-- Mileage -->
                        <div>
                            <label for="edit_mileage" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Odometer / Mileage (Optional)</label>
                            <input type="number" name="mileage" id="edit_mileage" min="0"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <!-- Buttons -->
                        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex gap-3">
                            <button type="submit"
                                    class="flex-1 py-2.5 px-4 bg-primary hover:bg-primary-hover text-white font-medium rounded-lg transition text-sm">
                                Save Changes
                            </button>
                            <button type="button" onclick="document.getElementById('edit-vehicle-drawer').classList.add('hidden')"
                                    class="py-2.5 px-4 bg-slate-200 dark:bg-slate-855 hover:bg-slate-300 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-350 font-medium rounded-lg transition text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function openEditVehicleDrawer(vehicle) {
        document.getElementById('edit_make').value = vehicle.make;
        document.getElementById('edit_model').value = vehicle.model;
        document.getElementById('edit_year').value = vehicle.year;
        document.getElementById('edit_plate_number').value = vehicle.plate_number;
        document.getElementById('edit_vin').value = vehicle.vin || '';
        document.getElementById('edit_mileage').value = vehicle.mileage || '';
        
        // Dynamically set the form submit URL to point to /vehicles/{id}
        const form = document.getElementById('edit-vehicle-form');
        form.action = '/vehicles/' + vehicle.id;
        
        document.getElementById('edit-vehicle-drawer').classList.remove('hidden');
    }
</script>
@endsection
