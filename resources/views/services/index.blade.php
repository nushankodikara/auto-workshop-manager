@extends('layouts.app')

@section('title', 'Predefined Services')

@section('content')
<div class="space-y-6">

    <!-- Header Actions -->
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Predefined Labor Services (Service Catalogue)</h2>
        <button onclick="document.getElementById('create-service-drawer').classList.remove('hidden')"
                class="px-4 py-2 bg-primary hover:bg-primary-hover text-white font-medium rounded-lg text-sm transition flex items-center gap-1.5 shadow-sm cursor-pointer">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>Add Predefined Service</span>
        </button>
    </div>

    <!-- Services Table -->
    <div class="app-card rounded-2xl overflow-hidden shadow-xs">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-slate-100/60 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                    <th class="py-4 px-6">Service Name</th>
                    <th class="py-4 px-6">Description</th>
                    <th class="py-4 px-6">Cost Price</th>
                    <th class="py-4 px-6">Default Selling Price</th>
                    <th class="py-4 px-6 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-850/60">
                @forelse($services as $service)
                    <tr class="hover:bg-slate-100/40 dark:hover:bg-slate-900/40 transition text-slate-750 dark:text-slate-300">
                        <td class="py-4 px-6 font-semibold text-slate-850 dark:text-slate-200">
                            {{ $service->name }}
                        </td>
                        <td class="py-4 px-6 text-xs">{{ $service->description ?? 'N/A' }}</td>
                        <td class="py-4 px-6 font-mono text-xs">{{ config('app.currency', 'Rs.') }}{{ number_format($service->cost_price, 2) }}</td>
                        <td class="py-4 px-6 font-semibold font-mono text-xs">{{ config('app.currency', 'Rs.') }}{{ number_format($service->selling_price, 2) }}</td>
                        <td class="py-4 px-6 text-right flex items-center justify-end gap-2">
                            <!-- Edit Trigger -->
                            <button onclick="openEditServiceDrawer({{ $service->id }}, '{{ addslashes($service->name) }}', '{{ addslashes($service->description) }}', {{ $service->cost_price }}, {{ $service->selling_price }})"
                                    class="text-xs font-bold text-primary bg-primary/10 border border-primary/20 px-2.5 py-1 rounded transition hover:bg-primary hover:text-white cursor-pointer">
                                Edit
                            </button>
                            <!-- Delete Trigger -->
                            <form action="{{ route('services.destroy', $service->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to remove this predefined service?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-bold text-red-600 bg-red-500/10 border border-red-500/20 px-2.5 py-1 rounded transition hover:bg-red-650 hover:text-white cursor-pointer">
                                    Remove
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-slate-500">
                            No predefined labor services registered yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

<!-- Sidebar Drawer Modal: Add Predefined Service -->
<div id="create-service-drawer" class="fixed inset-0 z-50 overflow-hidden hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute inset-0 bg-slate-955/75 transition-opacity" onclick="document.getElementById('create-service-drawer').classList.add('hidden')"></div>

        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div class="pointer-events-auto w-screen max-w-md">
                <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-xl">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-955/40">
                        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <i data-lucide="clipboard-list" class="w-5 h-5 text-primary"></i>
                            <span>Add Predefined Service</span>
                        </h2>
                        <button onclick="document.getElementById('create-service-drawer').classList.add('hidden')" class="text-slate-500 hover:text-slate-400 font-bold p-2 cursor-pointer">✕</button>
                    </div>

                    <form action="{{ route('services.store') }}" method="POST" class="flex-1 p-6 space-y-5">
                        @csrf

                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Service Name</label>
                            <input type="text" name="name" id="name" required placeholder="e.g., Full Engine Diagnostic Check"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Description</label>
                            <textarea name="description" id="description" rows="3" placeholder="Description of operations included..."
                                      class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm"></textarea>
                        </div>

                        <!-- Cost Price -->
                        <div>
                            <label for="cost_price" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Cost to Company ({{ config('app.currency', 'Rs.') }})</label>
                            <input type="number" step="0.01" name="cost_price" id="cost_price" required placeholder="0.00" oninput="autoFillSellingPrice(this.value)"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                        </div>

                        <!-- Selling Price -->
                        <div>
                            <label for="selling_price" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Selling Price to Client ({{ config('app.currency', 'Rs.') }})</label>
                            <input type="number" step="0.01" name="selling_price" id="selling_price" required placeholder="0.00"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                        </div>

                        <!-- Buttons -->
                        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex gap-3">
                            <button type="submit"
                                    class="flex-1 py-2.5 px-4 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg transition text-sm cursor-pointer">
                                Add Predefined Service
                            </button>
                            <button type="button" onclick="document.getElementById('create-service-drawer').classList.add('hidden')"
                                    class="py-2.5 px-4 bg-slate-200 dark:bg-slate-855 hover:bg-slate-300 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-350 font-medium rounded-lg transition text-sm cursor-pointer">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sidebar Drawer Modal: Edit Predefined Service -->
<div id="edit-service-drawer" class="fixed inset-0 z-50 overflow-hidden hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute inset-0 bg-slate-955/75 transition-opacity" onclick="document.getElementById('edit-service-drawer').classList.add('hidden')"></div>

        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div class="pointer-events-auto w-screen max-w-md">
                <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-xl">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-955/40">
                        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <i data-lucide="edit" class="w-5 h-5 text-primary"></i>
                            <span>Edit Predefined Service</span>
                        </h2>
                        <button onclick="document.getElementById('edit-service-drawer').classList.add('hidden')" class="text-slate-500 hover:text-slate-400 font-bold p-2 cursor-pointer">✕</button>
                    </div>

                    <form id="edit-service-form" action="" method="POST" class="flex-1 p-6 space-y-5">
                        @csrf
                        @method('PUT')

                        <!-- Name -->
                        <div>
                            <label for="edit_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Service Name</label>
                            <input type="text" name="name" id="edit_name" required
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="edit_description" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Description</label>
                            <textarea name="description" id="edit_description" rows="3"
                                      class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm"></textarea>
                        </div>

                        <!-- Cost Price -->
                        <div>
                            <label for="edit_cost_price" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Cost to Company ({{ config('app.currency', 'Rs.') }})</label>
                            <input type="number" step="0.01" name="cost_price" id="edit_cost_price" required oninput="autoFillEditSellingPrice(this.value)"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                        </div>

                        <!-- Selling Price -->
                        <div>
                            <label for="edit_selling_price" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Selling Price to Client ({{ config('app.currency', 'Rs.') }})</label>
                            <input type="number" step="0.01" name="selling_price" id="edit_selling_price" required
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                        </div>

                        <!-- Buttons -->
                        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex gap-3">
                            <button type="submit"
                                    class="flex-1 py-2.5 px-4 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg transition text-sm cursor-pointer font-sans">
                                Save Details
                            </button>
                            <button type="button" onclick="document.getElementById('edit-service-drawer').classList.add('hidden')"
                                    class="py-2.5 px-4 bg-slate-200 dark:bg-slate-855 hover:bg-slate-300 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-350 font-medium rounded-lg transition text-sm cursor-pointer font-sans">
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
    function autoFillSellingPrice(val) {
        document.getElementById('selling_price').value = val;
    }
    
    function autoFillEditSellingPrice(val) {
        document.getElementById('edit_selling_price').value = val;
    }

    function openEditServiceDrawer(id, name, description, cost_price, selling_price) {
        document.getElementById('edit-service-form').action = `/predefined-services/${id}`;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_description').value = description || '';
        document.getElementById('edit_cost_price').value = cost_price;
        document.getElementById('edit_selling_price').value = selling_price;
        document.getElementById('edit-service-drawer').classList.remove('hidden');
    }
</script>
@endsection
