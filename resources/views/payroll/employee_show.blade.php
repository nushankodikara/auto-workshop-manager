@extends('layouts.app')

@section('title', 'Employee Profile & Utilization')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    <!-- Back Button & Title -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('payroll.index') }}" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                <span>Back to Directory</span>
            </a>
            <span class="text-slate-400">|</span>
            <span class="text-slate-650 dark:text-slate-350 font-semibold text-sm">Employee Profile</span>
        </div>
    </div>

    <!-- Employee Card & Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Profile details -->
        <div class="md:col-span-1 app-card rounded-2xl p-6 space-y-4 shadow-xs">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5 border-b border-slate-200 dark:border-slate-800 pb-3">
                <i data-lucide="user" class="w-4 h-4 text-primary"></i>
                <span>Employee Details</span>
            </h3>
            
            <div class="space-y-3 text-sm">
                <div>
                    <span class="text-slate-500 block text-xs font-semibold">Full Name</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200 mt-0.5 block capitalize">{{ $user->name }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block text-xs font-semibold">Email Address</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300 font-mono mt-0.5 block text-xs break-all">{{ $user->email }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block text-xs font-semibold">System Role</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-650 dark:text-slate-400 border border-slate-200 dark:border-slate-700 capitalize mt-1 inline-block">
                        {{ $user->role }}
                    </span>
                </div>
                <div>
                    <span class="text-slate-500 block text-xs font-semibold">Basic Salary</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200 mt-0.5 block font-mono">
                        {{ config('app.currency', 'Rs.') }}{{ number_format($user->basic_salary, 2) }}
                    </span>
                </div>
                <div>
                    <span class="text-slate-500 block text-xs font-semibold">Overtime Rate</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300 mt-0.5 block font-mono">
                        {{ config('app.currency', 'Rs.') }}{{ number_format($user->overtime_rate, 2) }}/hr
                    </span>
                </div>
            </div>
        </div>

        <!-- Total Active Working Hours Card -->
        <div class="app-card rounded-2xl p-6 flex flex-col justify-between items-center text-center shadow-xs border border-primary/10 bg-primary/[0.01]">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Regular Active Hours</h3>
            <div class="py-4">
                <span class="text-4xl font-extrabold text-primary font-mono tracking-tight">{{ number_format($totalActiveHours, 2) }}</span>
                <span class="text-xs text-slate-500 block mt-1 font-semibold">Hours Spent (08:30 - 18:00)</span>
            </div>
            <p class="text-[10px] text-slate-400 leading-normal">
                Hours spent on tickets during regular company working hours.
            </p>
        </div>

        <!-- Total Overtime Hours Card -->
        <div class="app-card rounded-2xl p-6 flex flex-col justify-between items-center text-center shadow-xs border border-amber-500/10 bg-amber-550/[0.01]">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Overtime Hours</h3>
            <div class="py-4">
                <span class="text-4xl font-extrabold text-amber-550 font-mono tracking-tight">{{ number_format($totalOvertimeHours, 2) }}</span>
                <span class="text-xs text-slate-500 block mt-1 font-semibold">Hours Spent (18:00 - 19:00)</span>
            </div>
            <p class="text-[10px] text-slate-400 leading-normal">
                Overtime tracked daily after 06:00 PM (maximum up to 07:00 PM).
            </p>
        </div>
    </div>

    <!-- Ticket Breakdown List -->
    <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-1.5">
            <i data-lucide="receipt" class="w-4 h-4 text-primary"></i>
            <span>Ticket Allocation & Hours Breakdown</span>
        </h3>

        @if(empty($ticketBreakdown))
            <div class="text-slate-500 text-sm py-8 text-center bg-slate-50 dark:bg-slate-955/20 rounded-xl border border-slate-200 dark:border-slate-800 border-dashed">
                No tickets or active working hours recorded for this employee yet.
            </div>
        @else
            <div class="overflow-hidden rounded-xl border border-slate-205 dark:border-slate-800 bg-white dark:bg-slate-950/20">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-100/60 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                            <th class="py-3 px-5">Job Card</th>
                            <th class="py-3 px-5">Vehicle</th>
                            <th class="py-3 px-5">Status</th>
                            <th class="py-3 px-5 text-right">Regular Hours</th>
                            <th class="py-3 px-5 text-right">Overtime Hours</th>
                            <th class="py-3 px-5 text-right">Total Hours</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-850/60">
                        @foreach($ticketBreakdown as $jcId => $data)
                            @php 
                                $jc = $data['job_card'];
                            @endphp
                            <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-900/20 transition">
                                <td class="py-3.5 px-5">
                                    <a href="{{ route('job-cards.show', $jc->id) }}" class="font-semibold text-primary hover:underline">
                                        {{ $jc->card_number ?? 'Job Card #' . str_pad($jc->id, 4, '0', STR_PAD_LEFT) }}
                                    </a>
                                </td>
                                <td class="py-3.5 px-5 text-slate-700 dark:text-slate-350 capitalize">
                                    {{ $jc->vehicle->make }} {{ $jc->vehicle->model }} ({{ $jc->vehicle->plate_number }})
                                </td>
                                <td class="py-3.5 px-5 capitalize">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-650 dark:text-slate-400">
                                        {{ str_replace('-', ' ', $jc->status) }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 text-right font-mono text-slate-700 dark:text-slate-300">
                                    {{ number_format($data['regular_hours'], 2) }} hrs
                                </td>
                                <td class="py-3.5 px-5 text-right font-mono text-amber-650 dark:text-amber-400">
                                    {{ number_format($data['overtime_hours'], 2) }} hrs
                                </td>
                                <td class="py-3.5 px-5 text-right font-mono font-bold text-slate-800 dark:text-slate-200">
                                    {{ number_format($data['total_hours'], 2) }} hrs
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
