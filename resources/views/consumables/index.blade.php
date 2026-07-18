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

    <!-- Sidebar Drawer Modal: Register Consumable -->
    <div id="create-consumable-modal" class="fixed inset-0 z-50 overflow-hidden hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute inset-0 bg-slate-950/75 transition-opacity" onclick="document.getElementById('create-consumable-modal').classList.add('hidden')"></div>

            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                <div class="pointer-events-auto w-screen max-w-md">
                    <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-xl">
                        <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950/40">
                            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                <i data-lucide="sparkles" class="w-5 h-5 text-primary"></i>
                                <span>Register Consumable Supply</span>
                            </h2>
                            <button onclick="document.getElementById('create-consumable-modal').classList.add('hidden')" class="text-slate-500 hover:text-slate-400 font-bold p-2">✕</button>
                        </div>

                        <form action="{{ route('consumables.store') }}" method="POST" class="flex-1 p-6 space-y-5">
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

                            <div class="border-t border-slate-200 dark:border-slate-800 pt-5 flex justify-end gap-3">
                                <button type="button" onclick="document.getElementById('create-consumable-modal').classList.add('hidden')"
                                        class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-semibold rounded-lg text-xs transition cursor-pointer border-0">
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
    </div>
</div>
@endsection
