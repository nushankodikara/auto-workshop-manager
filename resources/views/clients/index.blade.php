@extends('layouts.app')

@section('title', 'Clients Directory')

@section('content')
<div class="space-y-6">

    <!-- Actions and Search -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Search bar -->
        <form action="{{ route('clients.index') }}" method="GET" class="w-full md:max-w-md flex gap-2">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search client name, phone or email..."
                   class="flex-1 px-4 py-2 bg-slate-900 border border-slate-800 rounded-lg text-slate-200 placeholder-slate-650 focus:outline-none focus:border-indigo-500 text-sm">
            <button type="submit" class="px-4 py-2 bg-slate-850 hover:bg-slate-800 border border-slate-750 rounded-lg text-xs font-semibold">
                Search
            </button>
            @if($search)
                <a href="{{ route('clients.index') }}" class="px-3 py-2 bg-slate-900 border border-slate-800 rounded-lg text-xs font-semibold text-slate-500 hover:text-slate-350 flex items-center">
                    Reset
                </a>
            @endif
        </form>

        <button onclick="document.getElementById('create-client-drawer').classList.remove('hidden')"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-lg text-sm transition">
            + Register Client Profile
        </button>
    </div>

    <!-- Clients List Table -->
    <div class="glass-card rounded-2xl overflow-hidden border border-slate-900">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-slate-900/60 border-b border-slate-850/80 text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                    <th class="py-4 px-6">Name</th>
                    <th class="py-4 px-6">Phone Number</th>
                    <th class="py-4 px-6">Email Address</th>
                    <th class="py-4 px-6">Vehicles</th>
                    <th class="py-4 px-6 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-850/60">
                @forelse($clients as $client)
                    <tr class="hover:bg-slate-900/40 transition">
                        <td class="py-4 px-6 font-semibold text-slate-200">
                            <a href="{{ route('clients.show', $client->id) }}" class="hover:text-indigo-400">
                                {{ $client->name }}
                            </a>
                        </td>
                        <td class="py-4 px-6 text-slate-400 font-mono">{{ $client->phone }}</td>
                        <td class="py-4 px-6 text-slate-450">{{ $client->email ?? 'N/A' }}</td>
                        <td class="py-4 px-6">
                            <span class="px-2 py-0.5 rounded bg-indigo-600/10 text-indigo-400 border border-indigo-500/20 text-xs font-medium">
                                {{ $client->vehicles_count }} Vehicles
                            </span>
                        </td>
                        <td class="py-4 px-6 text-right">
                            <a href="{{ route('clients.show', $client->id) }}" 
                               class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 bg-indigo-500/10 border border-indigo-500/25 px-2.5 py-1 rounded transition">
                                Manage Profile
                            </a>
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

    <!-- Pagination -->
    <div class="mt-4">
        {{ $clients->appends(['search' => $search])->links() }}
    </div>

</div>

<!-- Sidebar Drawer Modal: Create Client -->
<div id="create-client-drawer" class="fixed inset-0 z-50 overflow-hidden hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute inset-0 bg-slate-950/75 transition-opacity" onclick="document.getElementById('create-client-drawer').classList.add('hidden')"></div>

        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div class="pointer-events-auto w-screen max-w-md">
                <div class="flex h-full flex-col overflow-y-scroll bg-slate-900 border-l border-slate-800 shadow-xl">
                    <div class="p-6 border-b border-slate-800 flex items-center justify-between bg-slate-950">
                        <h2 class="text-lg font-bold text-slate-200">Register Client</h2>
                        <button onclick="document.getElementById('create-client-drawer').classList.add('hidden')" class="text-slate-500 hover:text-slate-400 font-bold p-2">✕</button>
                    </div>

                    <form action="{{ route('clients.store') }}" method="POST" class="flex-1 p-6 space-y-5">
                        @csrf

                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Customer Full Name</label>
                            <input type="text" name="name" id="name" required placeholder="John Doe"
                                   class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500 text-sm">
                        </div>

                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Phone Number</label>
                            <input type="text" name="phone" id="phone" required placeholder="+94771234567"
                                   class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500 text-sm">
                            <span class="text-[10px] text-slate-500 mt-1 block">Requires country code format for FitSMS text notifications.</span>
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Email Address</label>
                            <input type="email" name="email" id="email" placeholder="john@example.com"
                                   class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500 text-sm">
                        </div>

                        <!-- Address -->
                        <div>
                            <label for="address" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Physical Address</label>
                            <textarea name="address" id="address" rows="3" placeholder="No. 12, High Street..."
                                      class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 focus:outline-none focus:border-indigo-500 text-sm"></textarea>
                        </div>

                        <!-- Buttons -->
                        <div class="pt-4 border-t border-slate-800 flex gap-3">
                            <button type="submit"
                                    class="flex-1 py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-lg transition text-sm">
                                Register Client
                            </button>
                            <button type="button" onclick="document.getElementById('create-client-drawer').classList.add('hidden')"
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
