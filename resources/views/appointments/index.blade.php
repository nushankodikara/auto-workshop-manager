@extends('layouts.app')

@section('title', 'Appointments')

@section('content')
<div class="space-y-6">

    {{-- ── Page Header ──────────────────────────────────────────── --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                <i data-lucide="calendar" class="w-5 h-5 text-primary"></i>
                Appointments
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">Manage upcoming service bookings and send customer reminders.</p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            {{-- Morning Notification Button --}}
            <form action="{{ route('appointments.notify-morning') }}" method="POST"
                  onsubmit="return confirm('Send SMS and Email reminders to all clients with appointments TODAY and TOMORROW that have not yet been notified?\n\nThis is safe to press multiple times — each client will only receive one notification per day.')">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition border
                               {{ $pendingNotifications > 0
                                  ? 'bg-amber-500 hover:bg-amber-600 text-white border-amber-600 shadow-sm shadow-amber-400/30'
                                  : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-700 cursor-default' }}"
                        {{ $pendingNotifications === 0 ? 'title=All appointments already notified today' : '' }}>
                    <i data-lucide="bell-ring" class="w-4 h-4"></i>
                    <span>Send Morning Notifications</span>
                    @if($pendingNotifications > 0)
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-white/25 text-xs font-bold">{{ $pendingNotifications }}</span>
                    @endif
                </button>
            </form>

            {{-- New Appointment Button --}}
            <button onclick="document.getElementById('create-appointment-drawer').classList.remove('hidden')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg text-sm transition shadow-sm shadow-primary/20">
                <i data-lucide="calendar-plus" class="w-4 h-4"></i>
                <span>New Appointment</span>
            </button>
        </div>
    </div>

    {{-- ── Flash Messages ──────────────────────────────────────── --}}
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/25 text-emerald-600 dark:text-emerald-400 text-sm font-medium flex items-start gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 shrink-0 mt-0.5"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="p-4 rounded-xl bg-red-500/10 border border-red-500/25 text-red-600 dark:text-red-400 text-sm font-medium">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ── Filters ─────────────────────────────────────────────── --}}
    <form action="{{ route('appointments.index') }}" method="GET"
          class="app-card rounded-xl px-5 py-4 shadow-xs flex flex-wrap items-end gap-4">
        <div>
            <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 mb-1">From</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}"
                   class="px-3 py-2 app-input rounded-lg text-sm text-slate-800 dark:text-slate-200 focus:outline-none focus:border-primary">
        </div>
        <div>
            <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 mb-1">To</label>
            <input type="date" name="date_to" value="{{ $dateTo }}"
                   class="px-3 py-2 app-input rounded-lg text-sm text-slate-800 dark:text-slate-200 focus:outline-none focus:border-primary">
        </div>
        <div>
            <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Status</label>
            <select name="status" class="px-3 py-2 app-input rounded-lg text-sm text-slate-800 dark:text-slate-200 focus:outline-none focus:border-primary">
                <option value="">All Statuses</option>
                @foreach(['pending','confirmed','completed','no-show','cancelled'] as $s)
                    <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit"
                    class="px-4 py-2 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg text-sm transition flex items-center gap-1.5">
                <i data-lucide="filter" class="w-3.5 h-3.5"></i> Filter
            </button>
            <a href="{{ route('appointments.index') }}"
               class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-400 font-semibold rounded-lg text-sm transition">
                Reset
            </a>
        </div>
    </form>

    {{-- ── Appointments Table ───────────────────────────────────── --}}
    <div class="app-card rounded-2xl overflow-hidden shadow-xs">
        <table class="datatable w-full text-sm text-left border-collapse">
            <thead>
                <tr class="bg-slate-100/60 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                    <th class="py-4 px-5">Date &amp; Time</th>
                    <th class="py-4 px-5">Client</th>
                    <th class="py-4 px-5">Vehicle</th>
                    <th class="py-4 px-5">Service</th>
                    <th class="py-4 px-5 text-center">Duration</th>
                    <th class="py-4 px-5 text-center">Status</th>
                    <th class="py-4 px-5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                @forelse($appointments as $appt)
                    @php
                        $isToday    = \Carbon\Carbon::parse($appt->appointment_date)->isToday();
                        $isPast     = \Carbon\Carbon::parse($appt->appointment_date)->isPast() && !$isToday;
                        $rowOpacity = ($appt->status === 'cancelled' || $appt->status === 'no-show') ? 'opacity-50' : '';
                    @endphp
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-900/30 transition {{ $rowOpacity }}">
                        {{-- Date / Time --}}
                        <td class="py-4 px-5">
                            <div class="flex items-center gap-2">
                                @if($isToday)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-primary/10 text-primary border border-primary/20 uppercase">Today</span>
                                @endif
                                <div>
                                    <div class="font-semibold text-slate-800 dark:text-slate-200">
                                        {{ \Carbon\Carbon::parse($appt->appointment_date)->format('D, d M Y') }}
                                    </div>
                                    <div class="text-xs text-slate-500 font-mono mt-0.5">{{ \Carbon\Carbon::createFromFormat('H:i:s', $appt->appointment_time)->format('g:i A') }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- Client --}}
                        <td class="py-4 px-5">
                            @if($appt->client)
                                <a href="{{ route('clients.show', $appt->client_id) }}"
                                   class="font-semibold text-slate-800 dark:text-slate-200 hover:text-primary">
                                    {{ $appt->client->name }}
                                </a>
                                <div class="text-xs text-slate-500 font-mono mt-0.5">{{ $appt->client->phone }}</div>
                            @else
                                <span class="text-slate-400 text-xs">Walk-in</span>
                            @endif
                        </td>

                        {{-- Vehicle --}}
                        <td class="py-4 px-5">
                            @if($appt->vehicle)
                                <div class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $appt->vehicle->make }} {{ $appt->vehicle->model }}</div>
                                <div class="text-xs text-slate-500 font-mono">{{ $appt->vehicle->plate_number }}</div>
                            @else
                                <span class="text-slate-400 text-xs">Not assigned</span>
                            @endif
                        </td>

                        {{-- Service --}}
                        <td class="py-4 px-5">
                            <div class="text-sm text-slate-700 dark:text-slate-300 max-w-[200px] truncate" title="{{ $appt->service_type }}">
                                {{ $appt->service_type }}
                            </div>
                            @if($appt->notes)
                                <div class="text-[10px] text-slate-400 mt-0.5 max-w-[200px] truncate" title="{{ $appt->notes }}">
                                    {{ $appt->notes }}
                                </div>
                            @endif
                        </td>

                        {{-- Duration --}}
                        <td class="py-4 px-5 text-center">
                            <span class="text-xs text-slate-500 font-mono">
                                {{ $appt->estimated_duration >= 60
                                    ? floor($appt->estimated_duration / 60) . 'h ' . ($appt->estimated_duration % 60 > 0 ? ($appt->estimated_duration % 60) . 'm' : '')
                                    : $appt->estimated_duration . 'm' }}
                            </span>
                        </td>

                        {{-- Status --}}
                        <td class="py-4 px-5 text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full border text-xs font-semibold {{ $appt->statusBadgeClass() }}">
                                {{ $appt->statusLabel() }}
                            </span>
                            {{-- Notification flags --}}
                            <div class="flex items-center justify-center gap-1 mt-1.5">
                                @if($appt->notified_on_create)
                                    <span title="Creation notice sent" class="text-emerald-500"><i data-lucide="mail-check" class="w-3 h-3"></i></span>
                                @endif
                                @if($appt->notified_day_prior)
                                    <span title="Day-prior reminder sent" class="text-blue-500"><i data-lucide="bell-dot" class="w-3 h-3"></i></span>
                                @endif
                                @if($appt->notified_morning)
                                    <span title="Morning reminder sent" class="text-primary"><i data-lucide="bell-ring" class="w-3 h-3"></i></span>
                                @endif
                            </div>
                        </td>

                        {{-- Actions --}}
                        <td class="py-4 px-5 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                {{-- Edit --}}
                                <button onclick='openEditDrawer(@json($appt))'
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-transparent hover:border-slate-200 dark:hover:border-slate-700 transition"
                                        title="Edit appointment">
                                    <i data-lucide="pencil" class="w-3 h-3"></i>
                                    Edit
                                </button>

                                {{-- Convert to Job Card --}}
                                @if(! $appt->job_card_id && in_array($appt->status, ['pending','confirmed']))
                                    <button onclick='openConvertDrawer(@json($appt))'
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-semibold text-primary bg-primary/10 hover:bg-primary/20 border border-primary/20 transition"
                                            title="Convert to Job Card">
                                        <i data-lucide="clipboard-check" class="w-3 h-3"></i>
                                        Job Card
                                    </button>
                                @elseif($appt->job_card_id)
                                    <a href="{{ route('job-cards.show', $appt->job_card_id) }}"
                                       class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-semibold text-emerald-600 bg-emerald-500/10 border border-emerald-500/20 transition hover:bg-emerald-500/20">
                                        <i data-lucide="external-link" class="w-3 h-3"></i>
                                        #{{ str_pad($appt->job_card_id, 4, '0', STR_PAD_LEFT) }}
                                    </a>
                                @endif

                                {{-- Delete --}}
                                <form action="{{ route('appointments.destroy', $appt->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            onclick="return confirm('Delete this appointment for {{ addslashes($appt->client->name ?? 'this client') }}?')"
                                            class="inline-flex items-center justify-center w-7 h-7 rounded text-red-500 hover:bg-red-500/10 border border-transparent hover:border-red-500/20 transition">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-16 text-center text-slate-400">
                            <i data-lucide="calendar-x" class="w-10 h-10 mx-auto mb-3 opacity-30"></i>
                            <p class="text-sm">No appointments found for this period.</p>
                            <button onclick="document.getElementById('create-appointment-drawer').classList.remove('hidden')"
                                    class="mt-4 inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:underline">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                Book the first appointment
                            </button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- Drawer: Create Appointment                                     --}}
{{-- ══════════════════════════════════════════════════════════════ --}}
<div id="create-appointment-drawer" class="fixed inset-0 z-50 overflow-hidden hidden" role="dialog" aria-modal="true">
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute inset-0 bg-slate-950/75 transition-opacity"
             onclick="document.getElementById('create-appointment-drawer').classList.add('hidden')"></div>

        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div class="pointer-events-auto w-screen max-w-md">
                <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-xl">

                    {{-- Header --}}
                    <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950/40">
                        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <i data-lucide="calendar-plus" class="w-5 h-5 text-primary"></i>
                            New Appointment
                        </h2>
                        <button onclick="document.getElementById('create-appointment-drawer').classList.add('hidden')"
                                class="text-slate-500 hover:text-slate-400 font-bold p-2 text-lg leading-none">&times;</button>
                    </div>

                    <form action="{{ route('appointments.store') }}" method="POST" class="flex-1 p-6 space-y-5">
                        @csrf

                        {{-- Client select --}}
                        <div>
                            <label for="client_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Client</label>
                            <select name="client_id" id="client_id" required
                                    onchange="filterVehiclesByClient(this.value, 'vehicle_id')"
                                    class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                                <option value="">— Select client —</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->phone }})</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Vehicle select (dynamically filtered) --}}
                        <div>
                            <label for="vehicle_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Vehicle <span class="font-normal normal-case">(Optional)</span></label>
                            <select name="vehicle_id" id="vehicle_id"
                                    class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                                <option value="">— Select client first —</option>
                            </select>
                        </div>

                        {{-- Date & Time --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="appointment_date" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Date</label>
                                <input type="date" name="appointment_date" id="appointment_date" required
                                       min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}"
                                       class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                            </div>
                            <div>
                                <label for="appointment_time" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Time</label>
                                <input type="time" name="appointment_time" id="appointment_time" required
                                       value="09:00"
                                       class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                            </div>
                        </div>

                        {{-- Service Type --}}
                        <div>
                            <label for="service_type" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Service Type</label>
                            <input type="text" name="service_type" id="service_type" required
                                   placeholder="e.g. Full Service, Oil Change, Brake Inspection"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        {{-- Estimated Duration --}}
                        <div>
                            <label for="estimated_duration" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">
                                Estimated Duration (minutes)
                            </label>
                            <select name="estimated_duration" id="estimated_duration"
                                    class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                                <option value="30">30 min</option>
                                <option value="60" selected>1 hour</option>
                                <option value="90">1.5 hours</option>
                                <option value="120">2 hours</option>
                                <option value="180">3 hours</option>
                                <option value="240">4 hours</option>
                                <option value="480">Full day (8 hours)</option>
                            </select>
                        </div>

                        {{-- Status --}}
                        <div>
                            <label for="status" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Status</label>
                            <select name="status" id="status"
                                    class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                            </select>
                        </div>

                        {{-- Notes --}}
                        <div>
                            <label for="notes" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Notes <span class="font-normal normal-case">(Optional)</span></label>
                            <textarea name="notes" id="notes" rows="3"
                                      placeholder="Any special instructions or customer requests..."
                                      class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm"></textarea>
                        </div>

                        <div class="text-[10px] text-slate-400 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-lg p-3 flex items-start gap-1.5">
                            <i data-lucide="info" class="w-3 h-3 shrink-0 mt-0.5 text-primary"></i>
                            A confirmation SMS and email will be sent to the client immediately upon booking.
                        </div>

                        {{-- Submit --}}
                        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex gap-3">
                            <button type="submit"
                                    class="flex-1 py-2.5 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg text-sm transition">
                                Book Appointment
                            </button>
                            <button type="button"
                                    onclick="document.getElementById('create-appointment-drawer').classList.add('hidden')"
                                    class="py-2.5 px-4 bg-slate-100 dark:bg-slate-850 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 font-medium rounded-lg text-sm transition">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- Drawer: Edit Appointment                                       --}}
{{-- ══════════════════════════════════════════════════════════════ --}}
<div id="edit-appointment-drawer" class="fixed inset-0 z-50 overflow-hidden hidden" role="dialog" aria-modal="true">
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute inset-0 bg-slate-950/75"
             onclick="document.getElementById('edit-appointment-drawer').classList.add('hidden')"></div>

        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div class="pointer-events-auto w-screen max-w-md">
                <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-xl">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950/40">
                        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <i data-lucide="pencil" class="w-5 h-5 text-primary"></i>
                            Edit Appointment
                        </h2>
                        <button onclick="document.getElementById('edit-appointment-drawer').classList.add('hidden')"
                                class="text-slate-500 hover:text-slate-400 font-bold p-2 text-lg leading-none">&times;</button>
                    </div>

                    <form id="edit-appointment-form" action="" method="POST" class="flex-1 p-6 space-y-5">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Client</label>
                            <select name="client_id" id="edit_client_id" required
                                    onchange="filterVehiclesByClient(this.value, 'edit_vehicle_id')"
                                    class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->phone }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Vehicle <span class="font-normal normal-case">(Optional)</span></label>
                            <select name="vehicle_id" id="edit_vehicle_id"
                                    class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                                <option value="">— None —</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Date</label>
                                <input type="date" name="appointment_date" id="edit_appointment_date" required
                                       class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Time</label>
                                <input type="time" name="appointment_time" id="edit_appointment_time" required
                                       class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Service Type</label>
                            <input type="text" name="service_type" id="edit_service_type" required
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Estimated Duration (minutes)</label>
                            <select name="estimated_duration" id="edit_estimated_duration"
                                    class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                                <option value="30">30 min</option>
                                <option value="60">1 hour</option>
                                <option value="90">1.5 hours</option>
                                <option value="120">2 hours</option>
                                <option value="180">3 hours</option>
                                <option value="240">4 hours</option>
                                <option value="480">Full day (8 hours)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Status</label>
                            <select name="status" id="edit_status"
                                    class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="completed">Completed</option>
                                <option value="no-show">No-Show</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Notes</label>
                            <textarea name="notes" id="edit_notes" rows="3"
                                      class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm"></textarea>
                        </div>

                        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex gap-3">
                            <button type="submit"
                                    class="flex-1 py-2.5 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg text-sm transition">
                                Save Changes
                            </button>
                            <button type="button"
                                    onclick="document.getElementById('edit-appointment-drawer').classList.add('hidden')"
                                    class="py-2.5 px-4 bg-slate-100 dark:bg-slate-850 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 font-medium rounded-lg text-sm transition">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- Drawer: Convert to Job Card                                    --}}
{{-- ══════════════════════════════════════════════════════════════ --}}
<div id="convert-drawer" class="fixed inset-0 z-50 overflow-hidden hidden" role="dialog" aria-modal="true">
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute inset-0 bg-slate-950/75"
             onclick="document.getElementById('convert-drawer').classList.add('hidden')"></div>

        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div class="pointer-events-auto w-screen max-w-md">
                <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-xl">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950/40">
                        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <i data-lucide="clipboard-check" class="w-5 h-5 text-primary"></i>
                            Convert to Job Card
                        </h2>
                        <button onclick="document.getElementById('convert-drawer').classList.add('hidden')"
                                class="text-slate-500 hover:text-slate-400 font-bold p-2 text-lg leading-none">&times;</button>
                    </div>

                    <form id="convert-form" action="" method="POST" class="flex-1 p-6 space-y-5">
                        @csrf

                        <div class="p-4 bg-amber-500/5 border border-amber-500/20 rounded-xl text-sm text-slate-700 dark:text-slate-300">
                            <p class="font-semibold text-amber-600 dark:text-amber-400 mb-1 flex items-center gap-1.5">
                                <i data-lucide="info" class="w-3.5 h-3.5"></i>
                                Converting this appointment will:
                            </p>
                            <ul class="text-xs text-slate-500 list-disc list-inside space-y-0.5 mt-1">
                                <li>Create a new Job Card with this vehicle and notes</li>
                                <li>Set appointment status to Confirmed</li>
                                <li>Open the Job Card detail page</li>
                            </ul>
                        </div>

                        <div>
                            <label for="convert_shop_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Assign Workshop Bay / Shop</label>
                            <select name="shop_id" id="convert_shop_id" required
                                    class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                                <option value="">— Select workshop bay —</option>
                                @foreach(\App\Models\Shop::orderBy('name')->get() as $shop)
                                    <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex gap-3">
                            <button type="submit"
                                    class="flex-1 py-2.5 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg text-sm transition flex items-center justify-center gap-2">
                                <i data-lucide="clipboard-check" class="w-4 h-4"></i>
                                Create Job Card
                            </button>
                            <button type="button"
                                    onclick="document.getElementById('convert-drawer').classList.add('hidden')"
                                    class="py-2.5 px-4 bg-slate-100 dark:bg-slate-850 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 font-medium rounded-lg text-sm transition">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════ --}}
{{-- Vehicle data for dynamic filtering                            --}}
{{-- ══════════════════════════════════════════════════════════════ --}}
<script>
    // All vehicles keyed by client_id for instant filtering without an API call
    const vehiclesByClient = @json(
        $clients->mapWithKeys(fn($c) => [
            $c->id => $c->vehicles->map(fn($v) => [
                'id'    => $v->id,
                'label' => "{$v->make} {$v->model} ({$v->plate_number})",
            ])
        ])
    );

    /**
     * Populate a vehicle <select> based on selected client.
     */
    function filterVehiclesByClient(clientId, selectId) {
        const sel = document.getElementById(selectId);
        sel.innerHTML = '<option value="">— No vehicle —</option>';

        const vehicles = vehiclesByClient[clientId] || [];
        vehicles.forEach(function(v) {
            const opt = document.createElement('option');
            opt.value       = v.id;
            opt.textContent = v.label;
            sel.appendChild(opt);
        });
    }

    /**
     * Open the Edit drawer and populate all fields.
     */
    function openEditDrawer(appt) {
        // Set form action
        document.getElementById('edit-appointment-form').action = '/appointments/' + appt.id;

        // Populate client, then trigger vehicle filter
        const clientSel = document.getElementById('edit_client_id');
        clientSel.value = appt.client_id;
        filterVehiclesByClient(appt.client_id, 'edit_vehicle_id');

        // Brief delay so vehicle options render before we set the value
        setTimeout(function() {
            document.getElementById('edit_vehicle_id').value = appt.vehicle_id || '';
        }, 10);

        document.getElementById('edit_appointment_date').value    = appt.appointment_date ? appt.appointment_date.substring(0, 10) : '';
        document.getElementById('edit_appointment_time').value    = appt.appointment_time ? appt.appointment_time.substring(0, 5) : '';
        document.getElementById('edit_service_type').value        = appt.service_type;
        document.getElementById('edit_estimated_duration').value  = appt.estimated_duration || 60;
        document.getElementById('edit_status').value              = appt.status;
        document.getElementById('edit_notes').value               = appt.notes || '';

        document.getElementById('edit-appointment-drawer').classList.remove('hidden');
    }

    /**
     * Open the Convert-to-Job-Card drawer.
     */
    function openConvertDrawer(appt) {
        document.getElementById('convert-form').action = '/appointments/' + appt.id + '/convert';
        document.getElementById('convert-drawer').classList.remove('hidden');
    }
</script>
@endsection
