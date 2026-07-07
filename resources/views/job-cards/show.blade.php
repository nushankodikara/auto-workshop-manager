@extends('layouts.app')

@section('title', 'Job Card Details')

@section('content')
<div class="space-y-6">

    <!-- Top Navigation & Summary -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('job-cards.board') }}" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                <span>Back to Board</span>
            </a>
            <span class="text-slate-400">|</span>
            <span class="text-slate-650 dark:text-slate-350 font-semibold text-sm">Job Card #{{ $jobCard->card_number ?? str_pad($jobCard->id, 4, '0', STR_PAD_LEFT) }}</span>
        </div>

        <!-- Invoice / Billing Link -->
        <div>
            @if($jobCard->bill)
                <a href="{{ route('billing.show', $jobCard->id) }}" 
                   class="px-4 py-2 bg-green-500/10 text-green-700 dark:text-green-400 border border-green-500/20 rounded-lg text-xs font-bold hover:bg-green-500/20 transition flex items-center gap-1.5 shadow-sm">
                    <i data-lucide="file-check" class="w-3.5 h-3.5"></i>
                    <span>View Invoice ({{ ucfirst($jobCard->bill->status) }})</span>
                </a>
            @else
                <a href="{{ route('billing.workspace', $jobCard->id) }}" 
                   class="px-4 py-2 bg-primary hover:bg-primary-hover text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                    <i data-lucide="receipt" class="w-3.5 h-3.5"></i>
                    <span>Billing & Invoice</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Main Grid Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left 2-Columns: Details, Parts, Comments -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- 1. Diagnostic Card -->
            <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                        <i data-lucide="info" class="w-4 h-4 text-primary"></i>
                        <span>Diagnostic Info</span>
                    </h3>
                    <div class="flex items-center gap-2">
                        @if(!$jobCard->bill || auth()->user()->isSuperManager())
                            <button onclick="document.getElementById('edit-job-details-drawer').classList.remove('hidden')"
                                    class="text-xs text-primary hover:bg-primary/10 font-semibold px-2 py-1 rounded transition border border-transparent hover:border-primary/20 flex items-center gap-1">
                                <i data-lucide="edit" class="w-3.5 h-3.5"></i>
                                <span>Edit Details</span>
                            </button>
                        @endif
                        <span class="px-2.5 py-0.5 rounded text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 capitalize">
                            Status: {{ str_replace('-', ' ', $jobCard->status) }}
                        </span>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-6 gap-4 text-sm mt-3">
                    <div>
                        <span class="text-slate-500 block text-xs font-semibold">Vehicle</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200 mt-0.5 block">{{ $jobCard->vehicle->make }} {{ $jobCard->vehicle->model }} ({{ $jobCard->vehicle->year }})</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-xs font-semibold">Plate Number</span>
                        <span class="font-semibold text-slate-700 dark:text-slate-300 font-mono mt-0.5 block">{{ $jobCard->vehicle->plate_number }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-xs font-semibold">Client</span>
                        <a href="{{ route('clients.show', $jobCard->vehicle->client_id) }}" class="font-semibold text-primary hover:underline mt-0.5 block">
                            {{ $jobCard->vehicle->client->name }}
                        </a>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-xs font-semibold">Assigned Shop</span>
                        <span class="font-semibold text-slate-700 dark:text-slate-300 mt-0.5 block">{{ $jobCard->shop->name }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-xs font-semibold">Odometer / Mileage</span>
                        <span class="font-bold text-slate-850 dark:text-slate-200 mt-0.5 block">{{ $jobCard->mileage ? number_format($jobCard->mileage) . ' km' : 'Not recorded' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-xs font-semibold">Open Duration</span>
                        <span class="font-bold text-slate-850 dark:text-slate-200 mt-0.5 block">{{ $jobCard->open_duration }}</span>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-200 dark:border-slate-800/50">
                    <span class="text-slate-500 block text-xs font-semibold">Job Notes & Diagnostic Logs</span>
                    <p class="text-slate-700 dark:text-slate-300 mt-1.5 text-sm leading-relaxed bg-slate-50 dark:bg-slate-950/40 p-4 rounded-xl border border-slate-200 dark:border-slate-800">
                        {{ $jobCard->notes ?? 'No diagnostic notes added.' }}
                    </p>
                </div>
            </div>

            <!-- Services performed Card -->
            <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-1.5">
                    <i data-lucide="wrench" class="w-4 h-4 text-primary"></i>
                    <span>Services & Operations (Tasks)</span>
                </h3>

                <!-- Services list -->
                <div class="space-y-3">
                    @forelse($jobCard->services as $serv)
                        <div class="flex items-center justify-between p-3.5 bg-slate-50 dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-800 text-sm">
                            <div>
                                <span class="font-semibold text-slate-850 dark:text-slate-200">{{ $serv->name }}</span>
                                @if($serv->description)
                                    <span class="text-xs text-slate-500 block mt-0.5">{{ $serv->description }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="font-bold text-slate-800 dark:text-slate-200 font-mono">{{ config('app.currency', '$') }}{{ number_format($serv->price, 2) }}</span>
                                @if(!$jobCard->bill || auth()->user()->isSuperManager())
                                    <form action="{{ route('job-cards.delete-service', $serv->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('Remove service operation?')"
                                                class="text-xs text-red-650 dark:text-red-400 hover:bg-red-500/10 hover:text-red-500 font-bold p-1.5 rounded transition">
                                            ✕
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-slate-500 text-sm py-6 text-center bg-slate-50 dark:bg-slate-955/20 rounded-xl border border-slate-200 dark:border-slate-800 border-dashed">
                            No service tasks recorded yet. Use the form below to add services.
                        </div>
                    @endforelse
                </div>

                <!-- Add service form -->
                @if(!$jobCard->bill || auth()->user()->isSuperManager())
                    @if($jobCard->bill && auth()->user()->isSuperManager())
                        <div class="text-xs text-amber-600 bg-amber-500/10 p-3 rounded-lg border border-amber-500/20 mb-3 flex items-center gap-1.5 font-semibold">
                            <i data-lucide="alert-triangle" class="w-3.5 h-3.5 shrink-0"></i>
                            <span>Note: This ticket has already been billed. Modifying services will require updating the billing calculations.</span>
                        </div>
                    @endif
                    <form action="{{ route('job-cards.add-service', $jobCard->id) }}" method="POST" class="pt-4 border-t border-slate-200 dark:border-slate-800/50 grid grid-cols-1 md:grid-cols-4 gap-4">
                        @csrf
                        <div class="md:col-span-2">
                            <label for="service_name" class="block text-xs text-slate-500 mb-1 font-semibold">Service Operation Name</label>
                            <input type="text" name="name" id="service_name" required placeholder="e.g., Engine Oil & Filter Change"
                                   class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label for="service_price" class="block text-xs text-slate-500 mb-1 font-semibold">Price ({{ config('app.currency', '$') }})</label>
                            <input type="number" step="0.01" name="price" id="service_price" required placeholder="0.00"
                                   class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary font-mono">
                        </div>
                        <div class="flex items-end">
                            <button type="submit"
                                    class="w-full py-2 px-4 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-primary dark:text-slate-200 font-bold rounded-lg text-xs transition border border-transparent hover:border-slate-300 dark:hover:border-slate-700 flex items-center justify-center gap-1">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                <span>Add Task</span>
                            </button>
                        </div>
                    </form>
                @else
                    <div class="text-xs text-slate-500 bg-slate-50 dark:bg-slate-955/35 p-3 rounded-lg border border-slate-200 dark:border-slate-800 mt-2 flex items-center gap-1.5">
                        <i data-lucide="lock" class="w-3.5 h-3.5 text-primary shrink-0"></i>
                        <span>Service logs are locked because an invoice has already been generated.</span>
                    </div>
                @endif
            </div>

            <!-- 2. Parts Allocated Grid -->
            <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-1.5">
                    <i data-lucide="package" class="w-4 h-4 text-primary"></i>
                    <span>Parts Allocated to this Repair</span>
                </h3>

                <!-- Allocated list -->
                <div class="space-y-3">
                    @php $hasParts = false; @endphp
                    @foreach($jobCard->stockMovements as $mov)
                        @if($mov->type === 'out')
                            @php $hasParts = true; @endphp
                            <div class="flex items-center justify-between p-3.5 bg-slate-50 dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-800 text-sm">
                                <div>
                                    <span class="font-semibold text-slate-850 dark:text-slate-200">{{ $mov->inventory->name }}</span>
                                    <span class="text-xs text-slate-500 font-mono block mt-0.5">SKU: {{ $mov->inventory->sku }} • Batch: {{ $mov->purchaseBatch->batch_code ?? 'None' }}</span>
                                    @if(!empty($mov->notes))
                                        <span class="text-xs text-slate-450 dark:text-slate-450 block mt-1">Notes: {{ $mov->notes }}</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="text-right">
                                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ abs($mov->quantity) }} {{ $mov->inventory->unit }}</span>
                                        <span class="text-xs text-slate-500 font-mono block mt-0.5">Price: {{ config('app.currency', 'Rs.') }}{{ number_format(abs($mov->quantity) * ($mov->purchaseBatch ? $mov->purchaseBatch->selling_price : $mov->inventory->selling_price), 2) }}</span>
                                    </div>
                                    @if(!$jobCard->bill || auth()->user()->isSuperManager())
                                        <div class="flex items-center gap-1">
                                            <!-- Edit Button -->
                                            <button onclick="openEditAllocationModal({{ $mov->id }}, {{ abs($mov->quantity) }}, '{{ addslashes($mov->inventory->name) }}', '{{ addslashes($mov->notes) }}')"
                                                    class="text-blue-600 hover:text-blue-500 p-1 cursor-pointer" title="Edit Quantity">
                                                <i data-lucide="edit-2" class="w-4 h-4"></i>
                                            </button>
                                            
                                            <!-- Remove Form -->
                                            <form action="{{ route('job-cards.deallocate-parts', $mov->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to remove this allocated part and return it to stock?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-650 hover:text-red-500 p-1 cursor-pointer" title="Remove & Return to Stock">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach

                    @if(!$hasParts)
                        <div class="text-slate-500 text-sm py-6 text-center bg-slate-50 dark:bg-slate-955/20 rounded-xl border border-slate-200 dark:border-slate-800 border-dashed">
                            No parts allocated to this job card yet. Use the form below to allocate parts from stock.
                        </div>
                    @endif
                </div>

                <!-- Part allocation form (Only show if invoice doesn't exist or user is super admin) -->
                @if(!$jobCard->bill || auth()->user()->isSuperManager())
                    @if($jobCard->bill && auth()->user()->isSuperManager())
                        <div class="text-xs text-amber-600 bg-amber-500/10 p-3 rounded-lg border border-amber-500/20 mb-3 flex items-center gap-1.5 font-semibold">
                            <i data-lucide="alert-triangle" class="w-3.5 h-3.5 shrink-0"></i>
                            <span>Note: This ticket has already been billed. Modifying parts will require updating the billing calculations.</span>
                        </div>
                    @endif
                    <form action="{{ route('job-cards.allocate-parts', $jobCard->id) }}" method="POST" class="pt-4 border-t border-slate-200 dark:border-slate-800/50 grid grid-cols-1 md:grid-cols-4 gap-4">
                        @csrf
                        <div>
                            <label for="inventory_id" class="block text-xs text-slate-500 mb-1 font-semibold">Select Part</label>
                            <select name="inventory_id" id="inventory_id" required onchange="updateBatchDropdown()"
                                    class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary cursor-pointer">
                                <option value="">-- Choose part --</option>
                                @foreach($inventoryItems as $item)
                                    <option value="{{ $item->id }}">
                                        {{ $item->name }} (SKU: {{ $item->sku }} - Total: {{ $item->quantity }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="purchase_batch_id" class="block text-xs text-slate-500 mb-1 font-semibold">Select Batch</label>
                            <select name="purchase_batch_id" id="purchase_batch_id" required
                                    class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary cursor-pointer">
                                <option value="">-- Choose batch --</option>
                            </select>
                        </div>
                        <div>
                            <label for="quantity" class="block text-xs text-slate-500 mb-1 font-semibold">Quantity Needed</label>
                            <input type="number" name="quantity" id="quantity" required min="1" value="1"
                                   class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary font-mono">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" 
                                    class="w-full py-2 px-4 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-primary dark:text-slate-200 font-bold rounded-lg text-xs transition border border-transparent hover:border-slate-300 dark:hover:border-slate-700 flex items-center justify-center gap-1">
                                <i data-lucide="link" class="w-3.5 h-3.5"></i>
                                <span>Allocate Part</span>
                            </button>
                        </div>
                    </form>
                @else
                    <div class="text-xs text-slate-500 bg-slate-50 dark:bg-slate-955/35 p-3 rounded-lg border border-slate-200 dark:border-slate-800 mt-2 flex items-center gap-1.5">
                        <i data-lucide="lock" class="w-3.5 h-3.5 text-primary shrink-0"></i>
                        <span>Parts allocation is locked because an invoice has already been generated.</span>
                    </div>
                @endif
            </div>

            <!-- 3. Discussion Feed (Comments) -->
            <div class="app-card rounded-2xl p-6 space-y-6 shadow-xs">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-1.5">
                    <i data-lucide="message-square" class="w-4 h-4 text-primary"></i>
                    <span>Discussion & Staff Comments</span>
                </h3>

                <!-- Form -->
                <form action="{{ route('job-cards.comment', $jobCard->id) }}" method="POST" class="space-y-3">
                    @csrf
                    <textarea name="content" required rows="3" placeholder="Post progress updates or diagnostics remarks..."
                              class="w-full px-4 py-3 app-input rounded-lg text-slate-900 dark:text-slate-200 text-sm focus:outline-none focus:border-primary placeholder-slate-400"></textarea>
                    <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg text-xs transition shadow-sm flex items-center gap-1">
                        <i data-lucide="send" class="w-3 h-3"></i>
                        <span>Post Comment</span>
                    </button>
                </form>

                <!-- Comments list -->
                <div class="space-y-4 pt-4 border-t border-slate-200 dark:border-slate-800/60">
                    @forelse($jobCard->comments as $com)
                        <div class="bg-slate-50 dark:bg-slate-900/35 p-4 rounded-xl border border-slate-200 dark:border-slate-800/80 flex items-start gap-3 text-sm">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-200 dark:bg-slate-800 text-xs shrink-0 font-bold text-slate-700 dark:text-slate-300 uppercase">
                                {{ substr($com->user->name, 0, 2) }}
                            </span>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-800 dark:text-slate-200 capitalize text-xs">{{ $com->user->name }}</span>
                                    <span class="text-[10px] text-slate-500 font-medium">{{ $com->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-slate-700 dark:text-slate-300 text-xs leading-relaxed">
                                    {{ $com->content }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="text-slate-500 text-xs text-center py-4">
                            No comments posted on this card yet.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Right 1-Column: Assignees & Status Timeline -->
        <div class="space-y-8">
            
            <!-- 1. Assigned Technicians Panel -->
            <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-1.5">
                    <i data-lucide="users" class="w-4 h-4 text-primary"></i>
                    <span>Staff Assignments</span>
                </h3>
                <!-- Active & Historical Workers list -->
                <div class="space-y-2">
                    @php
                        $historicalWorkerIds = \App\Models\JobCardAssignment::where('job_card_id', $jobCard->id)->pluck('user_id')->unique();
                        $historicalWorkers = \App\Models\User::whereIn('id', $historicalWorkerIds)->get();
                    @endphp
                    @forelse($historicalWorkers as $worker)
                        @php
                            $isCurrentlyAssigned = $jobCard->workers->contains($worker->id);
                        @endphp
                        <div class="flex items-center gap-2.5 p-2 bg-slate-50 dark:bg-slate-900/50 rounded-lg border border-slate-200 dark:border-slate-800 text-xs capitalize font-medium">
                            <span class="h-2 w-2 rounded-full {{ $isCurrentlyAssigned ? 'bg-green-500' : 'bg-slate-400' }} text-xs inline-block shrink-0"
                                  title="{{ $isCurrentlyAssigned ? 'Currently Assigned' : 'Previously Assigned (Inactive)' }}"></span>
                            <span class="text-slate-750 dark:text-slate-350 font-bold">
                                <a href="{{ route('employees.show', $worker->id) }}" class="text-primary hover:underline">
                                    {{ $worker->name }}
                                </a>
                                @if(!$isCurrentlyAssigned)
                                    <span class="text-[9px] text-slate-500 dark:text-slate-450 normal-case ml-1 font-semibold bg-slate-200 dark:bg-slate-800 px-1 py-0.5 rounded">(inactive)</span>
                                @endif
                            </span>
                            <span class="text-slate-550 dark:text-slate-400 ml-auto flex flex-col items-end text-right">
                                <span class="font-semibold">{{ $worker->role }}</span>
                                <span class="text-[10px] text-slate-400 font-semibold font-mono mt-0.5">
                                    Active: {{ number_format($jobCard->getWorkerActiveHours($worker), 2) }}h (OT: {{ number_format($jobCard->getWorkerOvertimeHours($worker), 2) }}h)
                                </span>
                            </span>
                        </div>
                    @empty
                        <div class="text-yellow-600 dark:text-yellow-400 text-xs p-3 bg-yellow-500/10 rounded-lg border border-yellow-500/20 flex items-center gap-1.5">
                            <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
                            <span>No mechanics assigned to this job card.</span>
                        </div>
                    @endforelse
                </div>
                <!-- Update Assignees form -->
                @if(!$jobCard->bill || auth()->user()->isSuperManager())
                    @if($jobCard->bill && auth()->user()->isSuperManager())
                        <div class="text-xs text-amber-600 bg-amber-500/10 p-3 rounded-lg border border-amber-500/20 mb-3 flex items-center gap-1.5 font-semibold font-sans">
                            <i data-lucide="alert-triangle" class="w-3.5 h-3.5 shrink-0"></i>
                            <span>Note: This ticket has already been billed. Modifying technicians won't automatically update existing bill items.</span>
                        </div>
                    @endif
                    <form action="{{ route('job-cards.workers', $jobCard->id) }}" method="POST" class="pt-4 border-t border-slate-200 dark:border-slate-800/50 space-y-3">
                        @csrf
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider">Manage Assignments</label>
                        <div class="space-y-1.5 max-h-40 overflow-y-auto p-3 bg-white dark:bg-slate-955 border border-slate-250 dark:border-slate-800 rounded-lg">
                            @foreach($allWorkers as $worker)
                                <label class="flex items-center text-xs text-slate-700 dark:text-slate-350 cursor-pointer">
                                    <input type="checkbox" name="workers[]" value="{{ $worker->id }}" 
                                           {{ $jobCard->workers->contains($worker->id) ? 'checked' : '' }}
                                           class="h-3.5 w-3.5 text-primary focus:ring-primary rounded border border-slate-355 dark:border-slate-800">
                                    <span class="ml-2 capitalize">{{ $worker->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <button type="submit" class="w-full py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-lg text-xs transition border border-transparent hover:border-slate-300 dark:hover:border-slate-700 flex items-center justify-center gap-1">
                            <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                            <span>Update Technicians</span>
                        </button>
                    </form>
                @else
                    <div class="text-xs text-slate-500 bg-slate-50 dark:bg-slate-955/35 p-3 rounded-lg border border-slate-200 dark:border-slate-800 mt-2 flex items-center gap-1.5">
                        <i data-lucide="lock" class="w-3.5 h-3.5 text-primary shrink-0"></i>
                        <span>Technician assignments are locked because an invoice has already been generated.</span>
                    </div>
                @endif
            </div>

            <!-- 2. Operations Logs (Activity Log) -->
            <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-1.5">
                    <i data-lucide="history" class="w-4 h-4 text-primary"></i>
                    <span>Operations Log Feed</span>
                </h3>

                <div class="space-y-4 max-h-[350px] overflow-y-auto pr-2">
                    @forelse($jobCard->activities as $act)
                        <div class="relative pl-4 border-l-2 border-slate-200 dark:border-slate-800 last:border-l-0 pb-4 last:pb-0">
                            <span class="absolute -left-1.5 top-1 flex h-3 w-3 items-center justify-center rounded-full bg-slate-200 dark:bg-slate-850 text-slate-500 dark:text-slate-400 text-[6px]">
                                ●
                            </span>
                            <div class="text-[11px] text-slate-650 dark:text-slate-300 leading-relaxed">
                                <span class="font-bold text-slate-800 dark:text-slate-200 capitalize">{{ $act->user->name }}</span> 
                                {{ $act->details }}
                            </div>
                            <div class="text-[9px] text-slate-500 font-semibold mt-0.5">
                                {{ $act->created_at->diffForHumans() }}
                            </div>
                        </div>
                    @empty
                        <div class="text-slate-500 text-xs text-center py-4">
                            No logs captured.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>

<!-- Sidebar Drawer Modal: Edit Job Card details -->
<div id="edit-job-details-drawer" class="fixed inset-0 z-50 overflow-hidden hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute inset-0 bg-slate-955/75 transition-opacity" onclick="document.getElementById('edit-job-details-drawer').classList.add('hidden')"></div>

        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div class="pointer-events-auto w-screen max-w-md">
                <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-xl">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-955/40">
                        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <i data-lucide="edit" class="w-5 h-5 text-primary"></i>
                            <span>Edit Job Details</span>
                        </h2>
                        <button onclick="document.getElementById('edit-job-details-drawer').classList.add('hidden')" class="text-slate-500 hover:text-slate-400 font-bold p-2">✕</button>
                    </div>

                    <form action="{{ route('job-cards.update', $jobCard->id) }}" method="POST" class="flex-1 p-6 space-y-5">
                        @csrf
                        @method('PUT')

                        <!-- Mileage -->
                        <div>
                            <label for="edit_mileage" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Odometer / Mileage (Optional)</label>
                            <input type="number" name="mileage" id="edit_mileage" min="0" value="{{ $jobCard->mileage }}" placeholder="e.g., 75000"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>



                        <!-- Notes -->
                        <div>
                            <label for="edit_notes" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Job Notes & Instructions</label>
                            <textarea name="notes" id="edit_notes" rows="6" placeholder="Brake diagnostic remarks..."
                                      class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">{{ $jobCard->notes }}</textarea>
                        </div>

                        <!-- Buttons -->
                        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex gap-3">
                            <button type="submit"
                                    class="flex-1 py-2.5 px-4 bg-primary hover:bg-primary-hover text-white font-medium rounded-lg transition text-sm">
                                Save Details
                            </button>
                            <button type="button" onclick="document.getElementById('edit-job-details-drawer').classList.add('hidden')"
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

<!-- Edit Allocation Modal -->
<div id="edit-allocation-modal" class="fixed inset-0 z-50 flex items-center justify-center p-6 hidden">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-slate-950/75" onclick="closeEditAllocationModal()"></div>
    
    <!-- Modal Card -->
    <div class="app-card w-full max-w-md rounded-2xl relative z-10 overflow-hidden shadow-2xl">
        <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-955/40">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                <i data-lucide="package" class="w-4 h-4 text-primary"></i>
                <span id="edit-alloc-title">Edit Allocated Quantity</span>
            </h2>
            <button onclick="closeEditAllocationModal()" class="text-slate-500 hover:text-slate-400 font-bold p-2">✕</button>
        </div>

        <form id="edit-allocation-form" method="POST" class="p-6 space-y-4 text-xs">
            @csrf
            @method('PATCH')

            <div>
                <label for="edit_alloc_quantity" class="block text-slate-500 mb-1 font-semibold">Allocated Quantity</label>
                <input type="number" name="quantity" id="edit_alloc_quantity" required min="1"
                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary font-mono">
            </div>

            <div>
                <label for="edit_alloc_notes" class="block text-slate-500 mb-1 font-semibold">Allocation Notes</label>
                <input type="text" name="notes" id="edit_alloc_notes"
                       class="w-full px-3 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary">
            </div>

            <!-- Buttons -->
            <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex gap-2 justify-end">
                <button type="button" onclick="closeEditAllocationModal()"
                        class="py-2 px-3 bg-slate-200 dark:bg-slate-850 hover:bg-slate-300 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold rounded-lg">
                    Cancel
                </button>
                <button type="submit" class="py-2 px-4 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg">
                    Update Quantity
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const inventoryData = @json($inventoryItems);

    function openEditAllocationModal(movementId, currentQty, partName, notes) {
        const modal = document.getElementById('edit-allocation-modal');
        const form = document.getElementById('edit-allocation-form');
        const title = document.getElementById('edit-alloc-title');
        
        form.action = `/job-cards/allocated-parts/${movementId}`;
        title.innerText = `Edit Allocation - ${partName}`;
        document.getElementById('edit_alloc_quantity').value = currentQty;
        document.getElementById('edit_alloc_notes').value = notes;
        
        modal.classList.remove('hidden');
    }

    function closeEditAllocationModal() {
        document.getElementById('edit-allocation-modal').classList.add('hidden');
    }
    
    function updateBatchDropdown() {
        const partSelect = document.getElementById('inventory_id');
        const batchSelect = document.getElementById('purchase_batch_id');
        const selectedPartId = partSelect.value;
        
        // Clear previous options
        batchSelect.innerHTML = '<option value="">-- Choose batch --</option>';
        
        if (!selectedPartId) {
            return;
        }
        
        // Find selected part in inventoryData
        const part = inventoryData.find(item => item.id == selectedPartId);
        if (part && part.purchase_batches) {
            part.purchase_batches.forEach((batch, index) => {
                const option = document.createElement('option');
                option.value = batch.id;
                // Oldest batch is recommended by FIFO
                const fifoLabel = index === 0 ? ' (FIFO Recommended)' : '';
                option.text = `${batch.batch_code} (Avail: ${batch.quantity_remaining} | Price: Rs.${parseFloat(batch.selling_price).toFixed(2)})${fifoLabel}`;
                if (index === 0) {
                    option.selected = true; // Auto-select the oldest batch (FIFO)
                }
                batchSelect.appendChild(option);
            });
        }
    }

    // Call updateBatchDropdown on page load to populate if there's a pre-selected value
    document.addEventListener('DOMContentLoaded', function() {
        updateBatchDropdown();
    });
</script>
@endsection
