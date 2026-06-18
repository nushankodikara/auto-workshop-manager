@extends('layouts.app')

@section('title', 'Job Card Details')

@section('content')
<div class="space-y-8">

    <!-- Top Navigation & Summary -->
    <div class="flex items-center justify-between border-b border-slate-800 pb-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('job-cards.board') }}" class="text-sm font-semibold text-indigo-400 hover:text-indigo-300">
                &larr; Back to Kanban
            </a>
            <span class="text-slate-600">|</span>
            <span class="text-slate-300 font-semibold text-sm">Job Card #{{ str_pad($jobCard->id, 4, '0', STR_PAD_LEFT) }}</span>
        </div>

        <!-- Invoice / Billing Link -->
        <div>
            @if($jobCard->bill)
                <a href="{{ route('billing.show', $jobCard->id) }}" 
                   class="px-4 py-2 bg-emerald-600/20 text-emerald-400 border border-emerald-500/30 rounded-lg text-xs font-bold hover:bg-emerald-600/35 transition">
                    📄 View Invoice ({{ capitalize($jobCard->bill->status) }})
                </a>
            @else
                <a href="{{ route('billing.workspace', $jobCard->id) }}" 
                   class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold transition">
                    💰 Billing Workspace & Invoice
                </a>
            @endif
        </div>
    </div>

    <!-- Main Grid Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left 2-Columns: Details, Parts, Comments -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- 1. Diagnostic Card -->
            <div class="glass-card rounded-2xl p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Diagnostic Info</h3>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-800 text-slate-450 border border-slate-700 capitalize">
                        Status: {{ str_replace('-', ' ', $jobCard->status) }}
                    </span>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mt-3">
                    <div>
                        <span class="text-slate-500 block text-xs">Vehicle</span>
                        <span class="font-semibold text-slate-200 mt-0.5 block">{{ $jobCard->vehicle->make }} {{ $jobCard->vehicle->model }} ({{ $jobCard->vehicle->year }})</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-xs">Plate Number</span>
                        <span class="font-semibold text-slate-200 mt-0.5 block">{{ $jobCard->vehicle->plate_number }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-xs">Client</span>
                        <a href="{{ route('clients.show', $jobCard->vehicle->client_id) }}" class="font-semibold text-indigo-400 hover:text-indigo-300 mt-0.5 block">
                            {{ $jobCard->vehicle->client->name }}
                        </a>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-xs">Assigned Shop</span>
                        <span class="font-semibold text-slate-200 mt-0.5 block">{{ $jobCard->shop->name }}</span>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-800/50">
                    <span class="text-slate-500 block text-xs">Job Notes & Diagnostic Logs</span>
                    <p class="text-slate-300 mt-1.5 text-sm leading-relaxed bg-slate-900/50 p-4 rounded-xl border border-slate-850">
                        {{ $jobCard->notes ?? 'No diagnostic notes added.' }}
                    </p>
                </div>
            </div>

            <!-- 2. Parts Allocated Grid -->
            <div class="glass-card rounded-2xl p-6 space-y-4">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800/80 pb-3">
                    🔧 Parts Allocated to this Repair
                </h3>

                <!-- Allocated list -->
                <div class="space-y-3">
                    @php $hasParts = false; @endphp
                    @foreach($jobCard->stockMovements as $mov)
                        @if($mov->type === 'out')
                            @php $hasParts = true; @endphp
                            <div class="flex items-center justify-between p-3.5 bg-slate-900/40 rounded-xl border border-slate-800/80 text-sm">
                                <div>
                                    <span class="font-semibold text-slate-200">{{ $mov->inventory->name }}</span>
                                    <span class="text-xs text-slate-500 font-mono block mt-0.5">SKU: {{ $mov->inventory->sku }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="font-bold text-slate-200">{{ abs($mov->quantity) }} {{ $mov->inventory->unit }}</span>
                                    <span class="text-xs text-slate-500 block mt-0.5">Cost: ${{ number_format(abs($mov->quantity) * $mov->inventory->price, 2) }}</span>
                                </div>
                            </div>
                        @endif
                    @endforeach

                    @if(!$hasParts)
                        <div class="text-slate-500 text-sm py-6 text-center bg-slate-900/20 rounded-xl border border-slate-850 border-dashed">
                            No parts allocated to this job card yet. Use the form below to allocate parts from stock.
                        </div>
                    @endif
                </div>

                <!-- Part allocation form (Only show if invoice doesn't exist) -->
                @if(!$jobCard->bill)
                    <form action="{{ route('job-cards.allocate-parts', $jobCard->id) }}" method="POST" class="pt-4 border-t border-slate-800/50 grid grid-cols-1 md:grid-cols-3 gap-4">
                        @csrf
                        <div>
                            <label for="inventory_id" class="block text-xs text-slate-500 mb-1">Select Parts In Stock</label>
                            <select name="inventory_id" id="inventory_id" required
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 text-xs focus:outline-none focus:border-indigo-500">
                                <option value="">-- Choose part --</option>
                                @foreach($inventoryItems as $item)
                                    <option value="{{ $item->id }}">
                                        {{ $item->name }} (SKU: {{ $item->sku }} - Avail: {{ $item->quantity }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="quantity" class="block text-xs text-slate-500 mb-1">Quantity Needed</label>
                            <input type="number" name="quantity" id="quantity" required min="1" value="1"
                                   class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 text-xs focus:outline-none focus:border-indigo-500">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" 
                                    class="w-full py-2 px-4 bg-slate-800 hover:bg-slate-700 text-indigo-400 font-semibold rounded-lg text-xs transition border border-indigo-500/25">
                                Allocate Part
                            </button>
                        </div>
                    </form>
                @else
                    <div class="text-xs text-slate-500 bg-slate-950/30 p-3 rounded-lg border border-slate-900 mt-2">
                        💡 Parts allocation is locked because an invoice has already been generated.
                    </div>
                @endif
            </div>

            <!-- 3. Discussion Feed (Comments) -->
            <div class="glass-card rounded-2xl p-6 space-y-6">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800/80 pb-3">
                    💬 Discussion & Staff Comments
                </h3>

                <!-- Form -->
                <form action="{{ route('job-cards.comment', $jobCard->id) }}" method="POST" class="space-y-3">
                    @csrf
                    <textarea name="content" required rows="3" placeholder="Post progress updates or diagnostics remarks..."
                              class="w-full px-4 py-3 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 text-sm focus:outline-none focus:border-indigo-500 placeholder-slate-650"></textarea>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-medium rounded-lg text-xs transition">
                        Post Comment
                    </button>
                </form>

                <!-- Comments list -->
                <div class="space-y-4 pt-4 border-t border-slate-850">
                    @forelse($jobCard->comments as $com)
                        <div class="bg-slate-900/35 p-4 rounded-xl border border-slate-850 flex items-start gap-3 text-sm">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-800 text-xs shrink-0 font-bold text-slate-300 uppercase">
                                {{ substr($com->user->name, 0, 2) }}
                            </span>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-slate-200 capitalize">{{ $com->user->name }}</span>
                                    <span class="text-[10px] text-slate-500">{{ $com->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-slate-350 text-xs leading-relaxed">
                                    {{ $com->content }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="text-slate-600 text-xs text-center py-4">
                            No comments posted on this card yet.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Right 1-Column: Assignees & Status Timeline -->
        <div class="space-y-8">
            
            <!-- 1. Assigned Technicians Panel -->
            <div class="glass-card rounded-2xl p-6 space-y-4">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800/80 pb-3">
                    👥 Staff Assignments
                </h3>

                <!-- Active Workers list -->
                <div class="space-y-2">
                    @forelse($jobCard->workers as $worker)
                        <div class="flex items-center gap-2.5 p-2 bg-slate-900/50 rounded-lg border border-slate-850 text-xs capitalize">
                            <span class="h-2 w-2 rounded-full bg-green-500"></span>
                            <span class="text-slate-300 font-medium">{{ $worker->name }}</span>
                            <span class="text-slate-500 ml-auto">{{ $worker->role }}</span>
                        </div>
                    @empty
                        <div class="text-yellow-400 text-xs p-3 bg-yellow-500/5 rounded-lg border border-yellow-500/10">
                            ⚠️ No mechanics assigned to this job card.
                        </div>
                    @endforelse
                </div>

                <!-- Update Assignees form -->
                <form action="{{ route('job-cards.workers', $jobCard->id) }}" method="POST" class="pt-4 border-t border-slate-800/50 space-y-3">
                    @csrf
                    <label class="block text-[11px] font-semibold text-slate-500 uppercase">Manage Assignments</label>
                    <div class="space-y-1.5 max-h-40 overflow-y-auto p-3 bg-slate-950 border border-slate-800 rounded-lg">
                        @foreach($allWorkers as $worker)
                            <label class="flex items-center text-xs text-slate-350 cursor-pointer">
                                <input type="checkbox" name="workers[]" value="{{ $worker->id }}" 
                                       {{ $jobCard->workers->contains($worker->id) ? 'checked' : '' }}
                                       class="h-3.5 w-3.5 bg-slate-900 border-slate-850 text-indigo-600 rounded focus:ring-0">
                                <span class="ml-2 capitalize">{{ $worker->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <button type="submit" class="w-full py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold rounded-lg text-xs transition border border-slate-700/80">
                        Update Technicians
                    </button>
                </form>
            </div>

            <!-- 2. Operations Logs (Activity Log) -->
            <div class="glass-card rounded-2xl p-6 space-y-4">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800/80 pb-3">
                    📋 Operations Log Feed
                </h3>

                <div class="space-y-4 max-h-[350px] overflow-y-auto pr-2">
                    @forelse($jobCard->activities as $act)
                        <div class="relative pl-4 border-l-2 border-slate-800 last:border-l-0 pb-4 last:pb-0">
                            <span class="absolute -left-1.5 top-1 flex h-3 w-3 items-center justify-center rounded-full bg-slate-850 text-[5px]">
                                ●
                            </span>
                            <div class="text-[11px] text-slate-300 leading-relaxed">
                                <span class="font-bold text-slate-200 capitalize">{{ $act->user->name }}</span> 
                                {{ $act->details }}
                            </div>
                            <div class="text-[9px] text-slate-500 font-medium mt-0.5">
                                {{ $act->created_at->diffForHumans() }}
                            </div>
                        </div>
                    @empty
                        <div class="text-slate-650 text-xs text-center py-4">
                            No logs captured.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>
@endsection

@php
// Helper helper for capitalizing or using title checks safely in Blade template environment
if (!function_exists('capitalize')) {
    function capitalize($str) {
        return ucfirst($str);
    }
}
@endphp
