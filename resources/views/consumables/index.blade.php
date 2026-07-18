@extends('layouts.app')

@section('title', 'Consumables Supplies')

@section('content')
<div class="space-y-6">

    <!-- Sub-navigation Tabs -->
    <div class="flex border-b border-slate-200 dark:border-slate-800">
        <a href="{{ route('consumables.index') }}" 
           class="px-5 py-3 font-semibold text-sm border-b-2 border-primary text-primary transition">
            Consumables supplies list
        </a>
        <a href="{{ route('consumables.forecast') }}" 
           class="px-5 py-3 font-semibold text-sm border-b-2 border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-350 transition flex items-center gap-1.5">
            <i data-lucide="trending-up" class="w-4 h-4"></i>
            <span>Demand Forecast & Purchases</span>
        </a>
    </div>

    <!-- Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-end gap-4">
        <button onclick="document.getElementById('create-consumable-modal').classList.remove('hidden')"
                class="px-4 py-2 bg-primary hover:bg-primary-hover text-white font-medium rounded-lg text-sm transition flex items-center gap-1.5 shadow-sm cursor-pointer ml-auto sm:ml-0">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span>Register Consumable Supply</span>
        </button>
    </div>

    <!-- Consumables Table Card -->
    <div class="app-card rounded-2xl overflow-hidden shadow-xs border border-slate-200 dark:border-slate-800">
        <div class="overflow-x-auto">
            <table class="datatable w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-100/60 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                        <th class="py-4 px-6">Supply Name</th>
                        <th class="py-4 px-6">SKU</th>
                        <th class="py-4 px-6">Current Stock level</th>
                        <th class="py-4 px-6">Description</th>
                        <th class="py-4 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-850/60">
                    @forelse($consumables as $item)
                        <tr class="hover:bg-slate-100/40 dark:hover:bg-slate-900/40 transition">
                            <td class="py-4 px-6">
                                <div class="font-bold text-slate-800 dark:text-slate-200 text-sm">
                                    {{ $item->name }}
                                </div>
                            </td>
                            <td class="py-4 px-6 font-mono text-xs text-slate-500">
                                {{ $item->sku ?: '—' }}
                            </td>
                            <td class="py-4 px-6">
                                <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $item->quantity <= 0 ? 'bg-red-500/10 text-red-650' : 'bg-emerald-500/10 text-emerald-600' }}">
                                    {{ number_format($item->quantity, 2) }} {{ $item->unit }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-500 max-w-xs truncate">
                                {{ $item->description ?: 'No description provided' }}
                            </td>
                            <td class="py-4 px-6 text-right">
                                <a href="{{ route('consumables.show', $item->id) }}" 
                                   class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-350 hover:bg-primary hover:text-white dark:hover:bg-primary dark:hover:text-white text-xs font-bold rounded-lg transition inline-flex items-center gap-1">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    <span>Manage Logs</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-450 dark:text-slate-500">
                                <i data-lucide="package" class="w-12 h-12 mx-auto mb-3 opacity-30"></i>
                                <p class="text-sm font-medium">No consumables supplies registered yet.</p>
                                <p class="text-xs text-slate-400 mt-1">Click the "Register Consumable Supply" button above to add one.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Consumable Modal -->
    <div id="create-consumable-modal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" onclick="document.getElementById('create-consumable-modal').classList.add('hidden')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-middle bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 dark:border-slate-800">
                <div class="bg-slate-50 dark:bg-slate-950/20 px-6 py-4 border-b border-slate-200 dark:border-slate-800/80 flex items-center justify-between">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200" id="modal-title">Register Consumable Supply</h3>
                    <button onclick="document.getElementById('create-consumable-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-655 dark:hover:text-slate-300">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form action="{{ route('consumables.store') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Supply Item Name</label>
                        <input type="text" name="name" id="name" required placeholder="e.g. Wurth Brake Cleaner"
                               class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="sku" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">SKU / Code</label>
                            <input type="text" name="sku" id="sku" placeholder="e.g. WURTH-BC-500"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono uppercase">
                        </div>
                        <div>
                            <label for="unit" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Measurement Unit</label>
                            <input type="text" name="unit" id="unit" required placeholder="e.g. cans, bottles, liters"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Description</label>
                        <textarea name="description" id="description" rows="3" placeholder="Additional details or supplier information..."
                                  class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm"></textarea>
                    </div>

                    <div class="border-t border-slate-200 dark:border-slate-800 pt-4 mt-6 flex justify-end gap-3">
                        <button type="button" onclick="document.getElementById('create-consumable-modal').classList.add('hidden')"
                                class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-semibold rounded-lg text-xs transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2.5 bg-primary hover:bg-primary-hover text-white font-bold rounded-lg text-xs transition shadow-sm cursor-pointer border-0">
                            Register Supply
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
