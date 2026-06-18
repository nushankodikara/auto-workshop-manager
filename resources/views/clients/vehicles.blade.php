@extends('layouts.app')

@section('title', 'Vehicles Directory')

@section('content')
<div class="space-y-6">

    <!-- Actions and Search -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Search bar -->
        <form action="{{ route('vehicles.index') }}" method="GET" class="w-full md:max-w-md flex gap-2">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search plate, make, model or owner..."
                   class="flex-1 px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:border-primary text-sm">
            <button type="submit" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-lg text-xs font-semibold flex items-center gap-1.5 text-slate-700 dark:text-slate-200">
                <i data-lucide="search" class="w-3.5 h-3.5"></i>
                <span>Search</span>
            </button>
            @if($search)
                <a href="{{ route('vehicles.index') }}" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 rounded-lg text-xs font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 flex items-center">
                    Reset
                </a>
            @endif
        </form>

        <button onclick="document.getElementById('create-vehicle-drawer').classList.remove('hidden')"
                class="px-4 py-2 bg-primary hover:bg-primary-hover text-white font-medium rounded-lg text-sm transition flex items-center gap-1.5 shadow-sm">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>Register Vehicle</span>
        </button>
    </div>

    <!-- Vehicles List Table -->
    <div class="app-card rounded-2xl overflow-hidden shadow-xs">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-slate-100/60 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                    <th class="py-4 px-6">Registration (Plate)</th>
                    <th class="py-4 px-6">Make & Model</th>
                    <th class="py-4 px-6">Year</th>
                    <th class="py-4 px-6">Mileage</th>
                    <th class="py-4 px-6">Owner</th>
                    <th class="py-4 px-6">Repair History</th>
                    <th class="py-4 px-6 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                @forelse($vehicles as $veh)
                    <tr class="hover:bg-slate-100/40 dark:hover:bg-slate-900/40 transition">
                        <td class="py-4 px-6 font-mono font-bold text-slate-800 dark:text-slate-200">
                            {{ $veh->plate_number }}
                        </td>
                        <td class="py-4 px-6 text-slate-700 dark:text-slate-300">
                            {{ $veh->make }} {{ $veh->model }}
                        </td>
                        <td class="py-4 px-6 text-slate-500 font-mono">{{ $veh->year }}</td>
                        <td class="py-4 px-6 font-mono text-slate-700 dark:text-slate-300 text-xs">
                            {{ $veh->mileage ? number_format($veh->mileage) . ' km' : 'N/A' }}
                        </td>
                        <td class="py-4 px-6">
                            @if($veh->client)
                                <a href="{{ route('clients.show', $veh->client->id) }}" class="font-semibold text-primary hover:underline">
                                    {{ $veh->client->name }}
                                </a>
                                <span class="block text-xs text-slate-500 font-mono mt-0.5">{{ $veh->client->phone }}</span>
                            @else
                                <span class="text-slate-400">No Owner</span>
                            @endif
                        </td>
                        <td class="py-4 px-6 text-slate-655 dark:text-slate-400">
                            <span class="px-2 py-0.5 rounded bg-primary/10 text-primary border border-primary/20 text-xs font-medium inline-flex items-center gap-1">
                                <i data-lucide="wrench" class="w-3.5 h-3.5"></i>
                                {{ $veh->jobCards->count() }} Repairs
                            </span>
                        </td>
                        <td class="py-4 px-6 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="{{ route('vehicles.history', $veh->id) }}"
                                   class="text-xs text-primary hover:bg-primary/10 font-semibold px-2.5 py-1 rounded transition border border-transparent hover:border-primary/20"
                                   title="View repair and services history report">
                                    History
                                </a>
                                <button onclick='openEditVehicleDrawer(@json($veh))'
                                        class="text-xs text-slate-600 dark:text-slate-400 hover:bg-slate-250 dark:hover:bg-slate-800 font-semibold px-2.5 py-1 rounded transition border border-transparent hover:border-slate-300 dark:hover:border-slate-700">
                                    Edit
                                </button>
                                <form action="{{ route('vehicles.destroy', $veh->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Delete vehicle record?')"
                                            class="text-xs text-red-650 dark:text-red-400 hover:bg-red-500/10 hover:text-red-500 font-semibold px-2.5 py-1 rounded transition border border-transparent hover:border-red-500/20">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-slate-500">
                            No vehicle records found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $vehicles->appends(['search' => $search])->links() }}
    </div>

</div>

<!-- Sidebar Drawer Modal: Create Vehicle -->
<div id="create-vehicle-drawer" class="fixed inset-0 z-50 overflow-hidden hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute inset-0 bg-slate-950/75 transition-opacity" onclick="document.getElementById('create-vehicle-drawer').classList.add('hidden')"></div>

        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div class="pointer-events-auto w-screen max-w-md">
                <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-xl">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950/40">
                        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <i data-lucide="car" class="w-5 h-5 text-primary"></i>
                            <span>Register Vehicle</span>
                        </h2>
                        <button onclick="document.getElementById('create-vehicle-drawer').classList.add('hidden')" class="text-slate-500 hover:text-slate-400 font-bold p-2">✕</button>
                    </div>

                    <form action="{{ route('vehicles.store') }}" method="POST" class="flex-1 p-6 space-y-5">
                        @csrf

                        <!-- Owner Dropdown -->
                        <div>
                            <label for="client_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Owner (Client)</label>
                            <select name="client_id" id="client_id" required
                                    class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm cursor-pointer">
                                <option value="" disabled selected>Select Client...</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->phone }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Make -->
                        <div>
                            <label for="make" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Make</label>
                            <input type="text" name="make" id="make" required placeholder="Toyota"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <!-- Model -->
                        <div>
                            <label for="model" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Model</label>
                            <input type="text" name="model" id="model" required placeholder="Prius"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <!-- Year -->
                        <div>
                            <label for="year" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Year</label>
                            <input type="number" name="year" id="year" required min="1900" max="{{ date('Y') + 1 }}" value="2018"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <!-- Plate Number -->
                        <div>
                            <label for="plate_number" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Vehicle Reg Number (Plate)</label>
                            <input type="text" name="plate_number" id="plate_number" required placeholder="HH-5788"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <!-- VIN -->
                        <div>
                            <label for="vin" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">VIN / Chassis Number (Optional)</label>
                            <input type="text" name="vin" id="vin" placeholder="JTDKN3DU..."
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <!-- Mileage -->
                        <div>
                            <label for="mileage" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Odometer / Mileage (Optional)</label>
                            <input type="number" name="mileage" id="mileage" placeholder="e.g., 75000" min="0"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <!-- Buttons -->
                        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex gap-3">
                            <button type="submit"
                                    class="flex-1 py-2.5 px-4 bg-primary hover:bg-primary-hover text-white font-medium rounded-lg transition text-sm">
                                Register Vehicle
                            </button>
                            <button type="button" onclick="document.getElementById('create-vehicle-drawer').classList.add('hidden')"
                                    class="py-2.5 px-4 bg-slate-200 dark:bg-slate-850 hover:bg-slate-300 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-350 font-medium rounded-lg transition text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
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
                    <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950/40">
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
