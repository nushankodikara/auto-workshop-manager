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

    <!-- Yearly Attendance Calendar -->
    <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-550 flex items-center gap-1.5">
                <i data-lucide="calendar" class="w-4 h-4 text-primary"></i>
                <span>Yearly Attendance Calendar</span>
            </h3>

            <!-- Year Selector Form -->
            <form action="{{ route('employees.show', $user->id) }}" method="GET" class="flex items-center gap-2">
                <label for="year" class="text-xs font-semibold text-slate-500">Select Year:</label>
                <select name="year" id="year" onchange="this.form.submit()" class="px-2 py-1 app-input rounded-lg text-xs font-semibold focus:outline-none focus:border-primary">
                    @for($y = (int)date('Y') + 1; $y >= (int)date('Y') - 4; $y--)
                        <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </form>
        </div>

        <!-- 12 Months Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 pt-2">
            @for($m = 1; $m <= 12; $m++)
                @php
                    $monthStart = \Carbon\Carbon::create($selectedYear, $m, 1);
                    $monthName = $monthStart->format('F');
                    $daysInMonth = $monthStart->daysInMonth;
                    $firstDayOfWeek = $monthStart->dayOfWeekIso - 1; // 0=Mon, 6=Sun
                @endphp
                <div class="p-3 bg-slate-50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/80 rounded-2xl flex flex-col items-center">
                    <h4 class="text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-2.5 uppercase tracking-wider">{{ $monthName }}</h4>
                    
                    <!-- Weekday Headers -->
                    <div class="grid grid-cols-7 gap-1 text-[8px] font-bold text-center text-slate-400 dark:text-slate-500 mb-1.5 w-full">
                        <span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span><span>S</span>
                    </div>
                    
                    <div class="grid grid-cols-7 gap-1 text-[9px] text-center font-mono w-full">
                        <!-- Empty slots before the first day -->
                        @for($i = 0; $i < $firstDayOfWeek; $i++)
                            <span class="w-5.5 h-5.5 flex items-center justify-center"></span>
                        @endfor
                        
                        <!-- Days of the month -->
                        @for($d = 1; $d <= $daysInMonth; $d++)
                            @php
                                $dateStr = sprintf('%04d-%02d-%02d', $selectedYear, $m, $d);
                                $record = $yearlyAttendance->get($dateStr);
                                
                                $bgClass = 'bg-slate-100/50 dark:bg-slate-850/30 text-slate-400 dark:text-slate-600 hover:bg-slate-200/55';
                                $title = 'Unmarked';
                                
                                if ($record) {
                                    if ($record->status === 'present') {
                                        $bgClass = 'bg-green-600 text-white font-bold shadow-sm shadow-green-500/20';
                                        $title = 'Present';
                                    } elseif ($record->status === 'half_day') {
                                        $bgClass = 'bg-amber-500 text-white font-bold shadow-sm shadow-amber-500/20';
                                        $title = 'Half Day';
                                    } elseif ($record->status === 'absent') {
                                        $bgClass = 'bg-red-500 text-white font-bold shadow-sm shadow-red-500/20';
                                        $title = 'Absent';
                                    } elseif ($record->status === 'leave') {
                                        $bgClass = 'bg-blue-500 text-white font-bold shadow-sm shadow-blue-500/20';
                                        $title = 'Leave';
                                    }
                                }
                            @endphp
                            <span class="w-5.5 h-5.5 flex items-center justify-center rounded-md text-[9px] {{ $bgClass }} cursor-default transition-all duration-150" title="{{ $d }} {{ $monthName }}: {{ $title }}">
                                {{ $d }}
                            </span>
                        @endfor
                    </div>
                </div>
            @endfor
        </div>

        <!-- Legend -->
        <div class="flex flex-wrap items-center gap-4 text-[10px] mt-4 pt-4 border-t border-slate-200 dark:border-slate-800">
            <span class="font-bold uppercase tracking-wider text-slate-450">Legend:</span>
            <span class="flex items-center gap-1.5">
                <span class="w-3.5 h-3.5 rounded bg-green-600 shadow-sm shadow-green-500/20"></span>
                <span class="text-slate-650 dark:text-slate-350">Present</span>
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3.5 h-3.5 rounded bg-amber-500 shadow-sm shadow-amber-500/20"></span>
                <span class="text-slate-650 dark:text-slate-350">Half Day</span>
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3.5 h-3.5 rounded bg-red-500 shadow-sm shadow-red-500/20"></span>
                <span class="text-slate-650 dark:text-slate-350">Absent</span>
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-3.5 h-3.5 rounded bg-blue-500 shadow-sm shadow-blue-500/20"></span>
                <span class="text-slate-650 dark:text-slate-350">Approved Leave</span>
            </span>
        </div>
    </div>

    <!-- Ticket Breakdown List -->
    <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-505 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-1.5">
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
