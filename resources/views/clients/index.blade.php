@extends('layouts.app')

@section('title', 'Clients Directory')

@section('content')
<div class="space-y-6">

    <!-- Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-4">
        <div>
            <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <i data-lucide="users" class="w-6 h-6 text-primary"></i>
                <span>Clients Directory</span>
            </h2>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('clients.duplicates') }}"
               class="px-4 py-2 bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 dark:text-amber-400 font-medium rounded-lg text-sm transition flex items-center gap-1.5 border border-amber-500/25">
                <i data-lucide="copy" class="w-4 h-4"></i>
                <span>Find Duplicates</span>
            </a>

            <form action="{{ route('clients.sync-all') }}" method="POST" onsubmit="return confirm('Sync all clients and vehicles to TDC Tracker?')">
                @csrf
                <button type="submit"
                        class="px-4 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-medium rounded-lg text-sm transition flex items-center gap-1.5 shadow-sm border border-slate-300 dark:border-slate-700">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    <span>Sync All with Tracker</span>
                </button>
            </form>

            <button onclick="document.getElementById('create-client-drawer').classList.remove('hidden')"
                    class="px-4 py-2 bg-primary hover:bg-primary-hover text-white font-medium rounded-lg text-sm transition flex items-center gap-1.5 shadow-sm shadow-primary/20 cursor-pointer">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                <span>Register Client Profile</span>
            </button>
        </div>
    </div>

    <!-- Clients List Table -->
    <div class="app-card rounded-2xl overflow-hidden shadow-xs">
        <table class="datatable w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-slate-100/60 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                    <th class="py-4 px-6">Name</th>
                    <th class="py-4 px-6">Phone (Mobile)</th>
                    <th class="py-4 px-6">Email Address</th>
                    <th class="py-4 px-6">Vehicles</th>
                    <th class="py-4 px-6 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-850/60">
                @forelse($clients as $client)
                    <tr class="hover:bg-slate-100/40 dark:hover:bg-slate-900/40 transition">
                        <td class="py-4 px-6 font-semibold text-slate-800 dark:text-slate-200">
                            <a href="{{ route('clients.show', $client->id) }}" class="hover:text-primary">
                                {{ $client->name }}
                            </a>
                        </td>
                        <td class="py-4 px-6 text-slate-550 dark:text-slate-400 font-mono">{{ $client->phone }}</td>
                        <td class="py-4 px-6 text-slate-500">{{ $client->email ?? 'N/A' }}</td>
                        <td class="py-4 px-6">
                            <span class="px-2 py-0.5 rounded bg-primary/10 text-primary border border-primary/20 text-xs font-medium inline-flex items-center gap-1">
                                <i data-lucide="car" class="w-3 h-3"></i>
                                <span>{{ $client->vehicles_count }} Vehicles</span>
                            </span>
                        </td>
                        <td class="py-4 px-6 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('clients.show', $client->id) }}" 
                                   class="text-xs font-semibold text-primary bg-primary/10 border border-primary/25 px-2.5 py-1 rounded transition hover:bg-primary hover:text-white">
                                    Manage Profile
                                </a>
                                <form action="{{ route('clients.destroy', $client->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            onclick="return confirm('Delete {{ addslashes($client->name) }} and all their vehicles? This cannot be undone.')"
                                            class="inline-flex items-center justify-center w-7 h-7 rounded text-red-500 hover:bg-red-500/10 border border-transparent hover:border-red-500/20 transition"
                                            title="Delete client">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-slate-500">
                            No client records found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Sidebar Drawer Modal: Create Client -->
<div id="create-client-drawer" class="fixed inset-0 z-50 overflow-hidden hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute inset-0 bg-slate-950/75 transition-opacity" onclick="document.getElementById('create-client-drawer').classList.add('hidden')"></div>

        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div class="pointer-events-auto w-screen max-w-md">
                <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-xl">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950/40">
                        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <i data-lucide="user-plus" class="w-5 h-5 text-primary"></i>
                            <span>Register Client</span>
                        </h2>
                        <button onclick="document.getElementById('create-client-drawer').classList.add('hidden')" class="text-slate-500 hover:text-slate-400 font-bold p-2">✕</button>
                    </div>

                    <form action="{{ route('clients.store') }}" method="POST" class="flex-1 p-6 space-y-5">
                        @csrf

                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Customer Full Name</label>
                            <input type="text" name="name" id="name" required placeholder="John Doe"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Phone Number</label>
                            <input type="text" name="phone" id="phone" required placeholder="+94771234567"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                            <span class="text-[10px] text-slate-500 mt-1 block leading-normal">Requires country code format for FitSMS text notifications. Search is indexed by this number.</span>
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Email Address</label>
                            <input type="email" name="email" id="email" placeholder="john@example.com"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <!-- Address -->
                        <div>
                            <label for="address" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Physical Address</label>
                            <textarea name="address" id="address" rows="3" placeholder="No. 12, High Street..."
                                      class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm"></textarea>
                        </div>

                        <!-- Buttons -->
                        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex gap-3">
                            <button type="submit"
                                    class="flex-1 py-2.5 px-4 bg-primary hover:bg-primary-hover text-white font-medium rounded-lg transition text-sm">
                                Register Client
                            </button>
                            <button type="button" onclick="document.getElementById('create-client-drawer').classList.add('hidden')"
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
