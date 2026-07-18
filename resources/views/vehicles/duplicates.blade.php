@extends('layouts.app')

@section('title', 'Duplicate Vehicle Records')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <a href="{{ route('vehicles.index') }}" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>Vehicles Directory</span>
                </a>
                <span class="text-slate-400">|</span>
                <span class="text-slate-600 dark:text-slate-350 font-semibold text-sm">Duplicate Records</span>
            </div>
            <p class="text-xs text-slate-505 dark:text-slate-450 max-w-xl">
                These vehicle records share the same license plate number. Select the primary record to keep, check off the duplicates to merge away, then submit. All job cards, appointments, and telemetry links will be reassigned to the primary.
            </p>
        </div>

        @if($totalDuplicates > 0)
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-500/10 border border-amber-500/25 text-amber-600 dark:text-amber-400 font-semibold text-xs animate-pulse">
                <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>
                {{ $totalDuplicates }} duplicate {{ Str::plural('record', $totalDuplicates) }} found
            </span>
        @endif
    </div>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/25 text-emerald-600 dark:text-emerald-400 text-sm font-medium flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
            {{ session('success') }}
        </div>
    @endif

    @if($groups->isEmpty())
        {{-- All Clear --}}
        <div class="app-card rounded-2xl p-16 text-center shadow-xs">
            <i data-lucide="check-circle-2" class="w-12 h-12 text-emerald-500 mx-auto mb-4"></i>
            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 mb-1">No Duplicate Records Found</h2>
            <p class="text-sm text-slate-500">Every vehicle profile has a unique plate number. Your directory is clean.</p>
            <a href="{{ route('vehicles.index') }}" class="inline-flex items-center gap-1.5 mt-6 px-4 py-2 bg-primary hover:bg-primary-hover text-white font-medium rounded-lg text-sm transition">
                <i data-lucide="car" class="w-4 h-4"></i>
                Back to Directory
            </a>
        </div>
    @else
        {{-- Duplicate Groups --}}
        <div class="space-y-6">
            @foreach($groups as $groupIndex => $group)
                <div class="app-card rounded-2xl overflow-hidden shadow-xs border border-slate-200 dark:border-slate-800">
                    {{-- Group Header --}}
                    <div class="px-6 py-4 bg-amber-500/5 border-b border-amber-500/15 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i data-lucide="copy" class="w-4 h-4 text-amber-500"></i>
                            <span class="text-sm font-bold text-slate-800 dark:text-slate-200">Plate: 
                                <span class="font-mono text-amber-600 dark:text-amber-400">{{ strtoupper($group->first()->plate_number) }}</span>
                            </span>
                        </div>
                        <span class="text-xs text-slate-500 font-semibold">{{ $group->count() }} records</span>
                    </div>

                    {{-- Merge Form --}}
                    <form action="{{ route('vehicles.merge') }}" method="POST" class="p-6"
                          onsubmit="return confirmMerge(this)">
                        @csrf

                        {{-- Records Table --}}
                        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 mb-5">
                            <table class="w-full text-sm text-left">
                                <thead>
                                    <tr class="bg-slate-100/60 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                                        <th class="py-3 px-4">Primary<br><span class="font-normal normal-case text-[9px]">Select one to keep</span></th>
                                        <th class="py-3 px-4">Merge Away<br><span class="font-normal normal-case text-[9px]">Check to delete after merge</span></th>
                                        <th class="py-3 px-4">ID</th>
                                        <th class="py-3 px-4">Details</th>
                                        <th class="py-3 px-4">Owner Client</th>
                                        <th class="py-3 px-4">Job Cards</th>
                                        <th class="py-3 px-4">Created</th>
                                        <th class="py-3 px-4"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                    @foreach($group as $i => $vehicle)
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/30 transition" id="row-{{ $groupIndex }}-{{ $vehicle->id }}">
                                            {{-- Primary radio --}}
                                            <td class="py-3 px-4">
                                                <input type="radio"
                                                       name="primary_id"
                                                       value="{{ $vehicle->id }}"
                                                       id="primary-{{ $groupIndex }}-{{ $vehicle->id }}"
                                                       {{ $i === 0 ? 'checked' : '' }}
                                                       class="w-4 h-4 accent-primary cursor-pointer"
                                                       onchange="syncRadioCheckbox({{ $groupIndex }}, {{ $group->pluck('id')->toJson() }}, {{ $vehicle->id }})">
                                            </td>
                                            {{-- Merge-away checkbox --}}
                                            <td class="py-3 px-4">
                                                <input type="checkbox"
                                                       name="duplicate_ids[]"
                                                       value="{{ $vehicle->id }}"
                                                       id="dup-{{ $groupIndex }}-{{ $vehicle->id }}"
                                                       {{ $i !== 0 ? 'checked' : '' }}
                                                       {{ $i === 0 ? 'disabled' : '' }}
                                                       class="w-4 h-4 accent-red-500 cursor-pointer">
                                            </td>
                                            {{-- Data cells --}}
                                            <td class="py-3 px-4 font-mono text-slate-400 text-xs">#{{ $vehicle->id }}</td>
                                            <td class="py-3 px-4">
                                                <div class="font-bold text-slate-800 dark:text-slate-200">
                                                    {{ $vehicle->make }} {{ $vehicle->model }} ({{ $vehicle->year ?: '—' }})
                                                </div>
                                                <div class="text-[10px] text-slate-450 dark:text-slate-500 font-mono uppercase mt-0.5">
                                                    VIN: {{ $vehicle->vin ?: '—' }} | Mileage: {{ $vehicle->mileage ?: '0' }} km
                                                </div>
                                            </td>
                                            <td class="py-3 px-4">
                                                @if($vehicle->client)
                                                    <a href="{{ route('clients.show', $vehicle->client_id) }}" target="_blank"
                                                       class="hover:text-primary font-semibold inline-flex items-center gap-1">
                                                        {{ $vehicle->client->name }}
                                                        <i data-lucide="external-link" class="w-3 h-3 opacity-40"></i>
                                                    </a>
                                                    <div class="text-[9px] text-slate-400 font-mono mt-0.5">{{ $vehicle->client->phone }}</div>
                                                @else
                                                    <span class="text-slate-400 text-xs">Unassigned</span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4">
                                                @if($vehicle->job_cards_count > 0)
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 text-xs font-semibold">
                                                        <i data-lucide="wrench" class="w-3 h-3"></i>
                                                        {{ $vehicle->job_cards_count }}
                                                    </span>
                                                @else
                                                    <span class="text-slate-400 text-xs">None</span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4 text-slate-400 text-xs">{{ $vehicle->created_at->format('Y-m-d') }}</td>
                                            <td class="py-3 px-4">
                                                <a href="{{ route('vehicles.history', $vehicle->id) }}"
                                                   class="text-xs text-primary font-semibold hover:underline" target="_blank">History</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Action Footer --}}
                        <div class="flex items-center justify-between flex-wrap gap-3">
                            <p class="text-xs text-slate-500">
                                <i data-lucide="info" class="w-3 h-3 inline-block mr-1 opacity-60"></i>
                                Job cards, appointments, and telemetry records from merged vehicles will be moved to the primary. This cannot be undone.
                            </p>
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white font-semibold rounded-lg text-sm transition shadow-sm shadow-amber-500/20 cursor-pointer border-0">
                                <i data-lucide="merge" class="w-4 h-4"></i>
                                Merge Duplicates into Primary
                            </button>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>
    @endif

</div>

<script>
/**
 * When a Primary radio is selected, automatically:
 *  - disable & uncheck the checkbox for that row (can't delete the primary)
 *  - enable & check all other checkboxes in the group
 */
function syncRadioCheckbox(groupIndex, allIds, selectedPrimaryId) {
    allIds.forEach(function(id) {
        var checkbox = document.getElementById('dup-' + groupIndex + '-' + id);
        if (!checkbox) return;
        if (id === selectedPrimaryId) {
            checkbox.checked  = false;
            checkbox.disabled = true;
        } else {
            checkbox.disabled = false;
            checkbox.checked  = true;
        }
    });
}

function confirmMerge(form) {
    var checkedBoxes = form.querySelectorAll('input[name="duplicate_ids[]"]:checked');
    if (checkedBoxes.length === 0) {
        alert('Please check at least one duplicate vehicle to merge away.');
        return false;
    }
    return confirm(
        checkedBoxes.length + ' duplicate vehicle record(s) will be permanently deleted after their job cards and records are reassigned. This cannot be undone.\n\nProceed?'
    );
}
</script>
@endsection
