@extends('layouts.app')

@section('title', 'Payroll & HR Workspace')

@section('content')
<div class="space-y-6">

    <!-- Filters & Tab Navigation Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-4">
        <!-- Date selectors -->
        <form action="{{ route('payroll.index') }}" method="GET" class="flex items-center gap-3">
            <div>
                <select name="year" onchange="this.form.submit()" 
                        class="px-3 py-1.5 app-input rounded-lg text-slate-900 dark:text-slate-250 text-xs focus:outline-none focus:border-primary cursor-pointer">
                    @for($y = date('Y') - 2; $y <= date('Y') + 2; $y++)
                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <select name="month" onchange="this.form.submit()"
                        class="px-3 py-1.5 app-input rounded-lg text-slate-900 dark:text-slate-250 text-xs focus:outline-none focus:border-primary cursor-pointer">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                        </option>
                    @endfor
                </select>
            </div>
        </form>
        
        <!-- Tab buttons -->
        <div class="flex border-b border-transparent">
            <button onclick="switchTab('tab-slips')" id="btn-tab-slips" class="px-4 py-2 font-semibold text-sm border-b-2 border-primary text-primary transition">
                Payslips
            </button>
            <button onclick="switchTab('tab-attendance')" id="btn-tab-attendance" class="px-4 py-2 font-semibold text-sm border-b-2 border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300 transition">
                Attendance Tracker
            </button>
            <button onclick="switchTab('tab-employees')" id="btn-tab-employees" class="px-4 py-2 font-semibold text-sm border-b-2 border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300 transition">
                Employee Directory
            </button>
            <button onclick="switchTab('tab-advances')" id="btn-tab-advances" class="px-4 py-2 font-semibold text-sm border-b-2 border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300 transition">
                Salary Advances
            </button>
        </div>
    </div>

    <!-- 1. TAB: Payslips -->
    <div id="tab-slips" class="grid grid-cols-1 lg:grid-cols-3 gap-8 tab-content">
        <!-- Left: Salary Slips Table -->
        <div class="lg:col-span-2 space-y-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Generated Payslips</h3>
            <div class="app-card rounded-2xl overflow-x-auto shadow-xs">
                <table class="datatable w-full text-left border-collapse text-sm min-w-[700px]">
                    <thead>
                        <tr class="bg-slate-100/60 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                            <th class="py-4 px-6">Employee</th>
                            <th class="py-4 px-6">Prorated Base</th>
                            <th class="py-4 px-6">OT Pay</th>
                            <th class="py-4 px-6">Allowances</th>
                            <th class="py-4 px-6">Deductions</th>
                            <th class="py-4 px-6">Net Payout</th>
                            <th class="py-4 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-850/60">
                        @forelse($slips as $slip)
                            <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-900/30 transition">
                                <td class="py-4 px-6 font-semibold text-slate-800 dark:text-slate-200 capitalize">
                                    <a href="{{ route('payroll.show', $slip->id) }}" class="hover:text-primary">
                                        {{ $slip->user->name }}
                                    </a>
                                    <span class="block text-[10px] text-slate-500 capitalize mt-0.5">{{ $slip->user->role }}</span>
                                </td>
                                <td class="py-4 px-6 font-mono text-slate-500 dark:text-slate-400">
                                    {{ config('app.currency', '$') }}{{ number_format($slip->prorated_salary, 2) }}
                                    <span class="block text-[9px] text-slate-450 font-sans mt-0.5">({{ $slip->attended_days }}/{{ $slip->required_days }} days)</span>
                                </td>
                                <td class="py-4 px-6 font-mono text-slate-500 dark:text-slate-400">
                                    {{ config('app.currency', '$') }}{{ number_format($slip->overtime_amount, 2) }}
                                    <span class="block text-[9px] text-slate-450 font-sans mt-0.5">({{ $slip->overtime_hours }} hrs)</span>
                                </td>
                                <td class="py-4 px-6 font-mono text-green-600 dark:text-green-400">+{{ config('app.currency', '$') }}{{ number_format($slip->allowance, 2) }}</td>
                                <td class="py-4 px-6 font-mono text-red-650 dark:text-red-400">-{{ config('app.currency', '$') }}{{ number_format($slip->deductions, 2) }}</td>
                                <td class="py-4 px-6 font-mono text-slate-800 dark:text-slate-200 font-bold">{{ config('app.currency', '$') }}{{ number_format($slip->net_salary, 2) }}</td>
                                <td class="py-4 px-6 text-right">
                                    <a href="{{ route('payroll.show', $slip->id) }}" 
                                       class="text-xs font-semibold text-primary bg-primary/10 border border-primary/20 px-2.5 py-1 rounded transition hover:bg-primary hover:text-white">
                                        View Slip
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-500">
                                    No payslips generated for this period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right: Staff Members without payslips -->
        <div class="app-card rounded-2xl p-6 h-fit shadow-xs">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 mb-4 flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-4 h-4 text-primary"></i>
                <span>Outstanding Payslips</span>
            </h3>

            <div class="space-y-4">
                @php $hasOutstanding = false; @endphp
                @foreach($users as $user)
                    @php
                        $hasSlip = $slips->contains('user_id', $user->id);
                    @endphp

                    @if(!$hasSlip)
                        @php $hasOutstanding = true; @endphp
                        <div class="flex items-center justify-between p-3.5 bg-slate-50 dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-800/80 text-xs capitalize">
                            <div>
                                <span class="font-bold text-slate-800 dark:text-slate-200 block">{{ $user->name }}</span>
                                <span class="text-[10px] text-slate-500 mt-0.5">Role: {{ $user->role }} • Basic: {{ config('app.currency', 'Rs.') }}{{ number_format($user->basic_salary, 2) }} • Total: {{ config('app.currency', 'Rs.') }}{{ number_format($user->total_salary, 2) }}</span>
                            </div>
                            <div>
                                <a href="{{ route('payroll.create', ['user' => $user->id, 'year' => $year, 'month' => $month]) }}"
                                   class="px-2.5 py-1.5 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg transition text-[11px] shadow-sm flex items-center gap-1">
                                    <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                    <span>Generate</span>
                                </a>
                            </div>
                        </div>
                    @endif
                @endforeach

                @if(!$hasOutstanding)
                    <div class="text-slate-500 text-xs text-center py-4">
                        All staff members have payslips generated for this period.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- 2. TAB: Attendance Tracker -->
    <div id="tab-attendance" class="space-y-8 tab-content hidden">
        
        <!-- Subsection: Log Daily Attendance -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-4">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Log Daily Attendance</h3>
                <div class="app-card rounded-2xl p-6 shadow-xs">
                    <form action="{{ route('payroll.attendance.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="flex items-center gap-4 border-b border-slate-200 dark:border-slate-855 pb-4">
                            <div>
                                <label for="attendance_date" class="block text-xs text-slate-500 mb-1 font-semibold uppercase tracking-wider">Date to Log</label>
                                <input type="date" name="date" id="attendance_date" value="{{ date('Y-m-d') }}" required
                                       class="px-3 py-1.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs">
                            </div>
                            <div class="text-xs text-slate-500 mt-5">
                                Select statuses and overtime hours for staff.
                            </div>
                        </div>

                        <div class="divide-y divide-slate-200 dark:divide-slate-800">
                            @foreach($users as $user)
                                <div class="py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-sm">
                                    <div class="font-semibold text-slate-800 dark:text-slate-200 capitalize">
                                        {{ $user->name }}
                                        <span class="block text-[10px] text-slate-500 font-normal">Role: {{ $user->role }}</span>
                                    </div>
                                    <div class="flex items-center gap-4 flex-wrap sm:flex-nowrap">
                                        <!-- Status select -->
                                        <div>
                                            <select name="attendance[{{ $user->id }}]" onchange="toggleTimeInputs(this, '{{ $user->id }}')"
                                                    class="px-2.5 py-1.5 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary cursor-pointer">
                                                <option value="present" selected>Clock In/Out</option>
                                                <option value="absent">Absent</option>
                                                <option value="leave">Leave</option>
                                                <option value="n/a">N/A</option>
                                            </select>
                                        </div>
                                        <!-- Time inputs -->
                                        <div id="time-inputs-{{ $user->id }}" class="flex items-center gap-2">
                                            <div class="flex items-center gap-1">
                                                <span class="text-[10px] text-slate-500">In:</span>
                                                <input type="time" name="in_time[{{ $user->id }}]" value="08:30"
                                                       class="px-2 py-1 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs font-mono focus:outline-none focus:border-primary">
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <span class="text-[10px] text-slate-500">Out:</span>
                                                <input type="time" name="out_time[{{ $user->id }}]" value="18:00"
                                                       class="px-2 py-1 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs font-mono focus:outline-none focus:border-primary">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-hover text-white text-xs font-semibold rounded-lg transition shadow-sm flex items-center gap-1.5">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                <span>Save Daily Attendance</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="app-card rounded-2xl p-6 h-fit shadow-xs">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 mb-4 flex items-center gap-2">
                    <i data-lucide="calendar" class="w-4 h-4 text-primary"></i>
                    <span>Bulk Edit Attendance</span>
                </h3>
                <p class="text-xs text-slate-500 mb-4 leading-relaxed">
                    Select an employee below to bulk edit or override their attendance dates and overtime hours for the entire selected month of <strong>{{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}</strong>.
                </p>
                <div class="space-y-3">
                    @foreach($users as $u)
                        <a href="{{ route('payroll.attendance.employee', ['user' => $u->id, 'year' => $year, 'month' => $month]) }}" 
                           class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-850 border border-slate-200 dark:border-slate-800 rounded-xl transition text-xs font-semibold text-slate-750 dark:text-slate-200">
                            <span class="capitalize">{{ $u->name }}</span>
                            <span class="text-primary hover:underline flex items-center gap-1">
                                <span>Bulk Edit</span>
                                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Subsection: Monthly Attendance Summary Grid -->
        <div class="space-y-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Monthly Attendance Grid</h3>
            <div class="app-card rounded-2xl overflow-x-auto shadow-xs">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-100/60 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
                            <th class="py-3.5 px-4 sticky left-0 bg-slate-100/60 dark:bg-slate-900/60 min-w-[120px]">Employee</th>
                            @for($d = 1; $d <= $daysInMonth; $d++)
                                <th class="py-3.5 px-1.5 text-center min-w-[24px]">{{ $d }}</th>
                            @endfor
                            <th class="py-3.5 px-4 text-center">Present</th>
                            <th class="py-3.5 px-4 text-center">OT Hrs</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
                        @foreach($users as $user)
                            @php
                                $uRecords = $attendanceData->get($user->id, collect());
                                $presentCount = 0;
                                $totalOt = 0;
                            @endphp
                            <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-900/30 transition">
                                <td class="py-3 px-4 font-semibold text-slate-800 dark:text-slate-200 capitalize sticky left-0 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                    {{ $user->name }}
                                </td>
                                @for($d = 1; $d <= $daysInMonth; $d++)
                                    @php
                                        $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
                                        $record = $uRecords->firstWhere(function($item) use ($dateStr) {
                                            return $item->date->format('Y-m-d') === $dateStr;
                                        });
                                    @endphp
                                    <td class="py-3 px-1.5 text-center border-r border-slate-150 dark:border-slate-850/60 last:border-r-0">
                                        @if($record)
                                            @if($record->status === 'present')
                                                @php $presentCount++; $totalOt += $record->overtime_hours; @endphp
                                                <span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block" title="Present ({{ $record->overtime_hours > 0 ? 'OT: ' . $record->overtime_hours . 'h' : 'No OT' }})"></span>
                                            @elseif($record->status === 'half_day')
                                                @php $presentCount += 0.5; $totalOt += $record->overtime_hours; @endphp
                                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block" title="Half Day ({{ $record->overtime_hours > 0 ? 'OT: ' . $record->overtime_hours . 'h' : 'No OT' }})"></span>
                                            @elseif($record->status === 'absent')
                                                <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block" title="Absent"></span>
                                            @else
                                                <span class="w-2.5 h-2.5 rounded-full bg-blue-500 inline-block" title="Leave"></span>
                                            @endif
                                        @else
                                            <span class="text-slate-300 dark:text-slate-700 font-mono">-</span>
                                        @endif
                                    </td>
                                @endfor
                                <td class="py-3 px-4 font-bold text-center text-slate-700 dark:text-slate-300">{{ $presentCount }}</td>
                                <td class="py-3 px-4 font-bold text-center font-mono text-slate-600 dark:text-slate-400">{{ $totalOt }}h</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex items-center gap-4 text-xxs text-slate-500 font-medium pt-1">
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span> Present</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span> Half Day</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span> Absent</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-500 inline-block"></span> Leave</span>
                <span class="flex items-center gap-1"><span class="text-slate-300 dark:text-slate-700 font-mono font-bold">-</span> N/A (Not Logged)</span>
            </div>
        </div>

    </div>

    <!-- 3. TAB: Employee Directory -->
    <div id="tab-employees" class="space-y-6 tab-content hidden">
        <div class="flex justify-between items-center">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Employee List</h3>
            <button onclick="document.getElementById('create-employee-drawer').classList.remove('hidden')"
                    class="px-4 py-2 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg text-xs transition flex items-center gap-1.5 shadow-sm">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                <span>Add Employee</span>
            </button>
        </div>

        <div class="app-card rounded-2xl overflow-hidden shadow-xs">
            <table class="datatable w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-100/60 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                        <th class="py-4 px-6">Name</th>
                        <th class="py-4 px-6">Email</th>
                        <th class="py-4 px-6">Contact Number</th>
                        <th class="py-4 px-6">Role</th>
                        <th class="py-4 px-6">Base Salary</th>
                        <th class="py-4 px-6">Required Days</th>
                        <th class="py-4 px-6">Overtime Rate</th>
                        <th class="py-4 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-850/60">
                    @foreach($users as $emp)
                        <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-900/30 transition">
                            <td class="py-4 px-6 font-semibold text-slate-800 dark:text-slate-200 capitalize">
                                <a href="{{ route('employees.show', $emp->id) }}" class="text-primary hover:underline">
                                    {{ $emp->name }}
                                </a>
                            </td>
                            <td class="py-4 px-6 text-slate-650 dark:text-slate-400 font-mono text-xs">{{ $emp->email }}</td>
                            <td class="py-4 px-6 text-slate-650 dark:text-slate-400 font-mono text-xs">{{ $emp->contact_number ?? '-' }}</td>
                            <td class="py-4 px-6 capitalize">
                                <span class="px-2 py-0.5 rounded text-xs font-semibold border {{ $emp->isSuperManager() ? 'bg-red-500/10 text-red-550 border-red-500/20' : ($emp->isManager() ? 'bg-blue-500/10 text-blue-550 border-blue-500/20' : 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-550/20') }}">
                                    {{ $emp->role }}
                                </span>
                            </td>
                            <td class="py-4 px-6 font-mono font-bold text-slate-700 dark:text-slate-300">
                                <span class="text-xs font-sans font-normal">Basic:</span> {{ config('app.currency', 'Rs.') }}{{ number_format($emp->basic_salary, 2) }}
                                <span class="block text-[10px] text-slate-450 font-sans font-normal mt-0.5">Total: {{ config('app.currency', 'Rs.') }}{{ number_format($emp->total_salary, 2) }}</span>
                            </td>
                            <td class="py-4 px-6 font-mono text-slate-550 dark:text-slate-450">{{ $emp->required_days }} days</td>
                            <td class="py-4 px-6 font-mono text-slate-550 dark:text-slate-450">
                                {{ config('app.currency', '$') }}{{ number_format($emp->overtime_rate, 2) }}/hr
                            </td>
                            <td class="py-4 px-6 text-right space-x-2">
                                <!-- Edit trigger -->
                                <button onclick="openEditEmployeeDrawer({{ json_encode($emp) }})" 
                                        class="text-xs font-semibold text-primary hover:underline">
                                    Edit
                                </button>
                                
                                @if($emp->id !== auth()->id())
                                    <form action="{{ route('employees.archive', $emp->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" onclick="return confirm('Archive this employee? They will no longer appear for active work, attendance, or payroll.')"
                                                class="text-xs font-semibold text-amber-600 dark:text-amber-450 hover:text-amber-500">
                                            Archive
                                        </button>
                                    </form>
                                    <form action="{{ route('employees.destroy', $emp->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('Permanently delete this employee?')"
                                                class="text-xs font-semibold text-red-550 dark:text-red-400 hover:text-red-500">
                                            Delete
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if(count($archivedUsers) > 0)
            <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-800 space-y-4">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-550">Archived Employees (Left Company)</h4>
                <div class="app-card rounded-2xl overflow-hidden shadow-xs border border-slate-200 dark:border-slate-800">
                    <table class="datatable w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-slate-100/40 dark:bg-slate-900/40 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                                <th class="py-4 px-6">Name</th>
                                <th class="py-4 px-6">Email</th>
                                <th class="py-4 px-6">Contact Number</th>
                                <th class="py-4 px-6">Role</th>
                                <th class="py-4 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-850/60">
                            @foreach($archivedUsers as $emp)
                                <tr class="hover:bg-slate-100/10 dark:hover:bg-slate-900/10 transition opacity-70">
                                    <td class="py-4 px-6 font-semibold text-slate-700 dark:text-slate-350 capitalize">
                                        <a href="{{ route('employees.show', $emp->id) }}" class="text-primary hover:underline">
                                            {{ $emp->name }}
                                        </a>
                                    </td>
                                    <td class="py-4 px-6 text-slate-505 font-mono text-xs">{{ $emp->email }}</td>
                                    <td class="py-4 px-6 text-slate-505 font-mono text-xs">{{ $emp->contact_number ?? '-' }}</td>
                                    <td class="py-4 px-6 capitalize">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold border border-slate-200 dark:border-slate-800 bg-slate-100/50 dark:bg-slate-800/50 text-slate-500">
                                            {{ $emp->role }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-right space-x-2">
                                        <form action="{{ route('employees.unarchive', $emp->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" onclick="return confirm('Restore this employee to active status?')"
                                                    class="text-xs font-semibold text-green-600 dark:text-green-400 hover:text-green-500">
                                                Restore
                                            </button>
                                        </form>
                                        @if($emp->id !== auth()->id())
                                            <form action="{{ route('employees.destroy', $emp->id) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm('Permanently delete this employee record?')"
                                                        class="text-xs font-semibold text-red-550 dark:text-red-400 hover:text-red-500">
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

</div>

<!-- Sidebar Drawer Modal: Create Employee -->
<div id="create-employee-drawer" class="fixed inset-0 z-50 overflow-hidden hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute inset-0 bg-slate-950/75 transition-opacity" onclick="document.getElementById('create-employee-drawer').classList.add('hidden')"></div>

        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div class="pointer-events-auto w-screen max-w-md">
                <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-xl">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950/40">
                        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <i data-lucide="user-plus" class="w-5 h-5 text-primary"></i>
                            <span>Register Employee</span>
                        </h2>
                        <button onclick="document.getElementById('create-employee-drawer').classList.add('hidden')" class="text-slate-500 hover:text-slate-400 font-bold p-2">✕</button>
                    </div>

                    <form action="{{ route('employees.store') }}" method="POST" class="flex-1 p-6 space-y-5">
                        @csrf

                        <div>
                            <label for="emp_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Full Name</label>
                            <input type="text" name="name" id="emp_name" required placeholder="Alice Smith"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <div>
                            <label for="emp_email" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Email Address</label>
                            <input type="email" name="email" id="emp_email" required placeholder="alice@workshop.com"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <div>
                            <label for="emp_contact_number" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Mobile / Contact Number</label>
                            <input type="text" name="contact_number" id="emp_contact_number" placeholder="e.g. 0771234567"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <div>
                            <label for="emp_password" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Password</label>
                            <input type="password" name="password" id="emp_password" required placeholder="••••••••"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <div>
                            <label for="emp_role" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">System Role</label>
                            <select name="role" id="emp_role" required
                                    class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm cursor-pointer">
                                @foreach(\App\Models\Role::all() as $roleOption)
                                    @if($roleOption->name !== 'super-manager' || auth()->user()->isSuperManager())
                                        <option value="{{ $roleOption->name }}">{{ $roleOption->label }}</option>
                                    @endif
                                @endforeach
                            </select>

                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="emp_salary" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Basic Salary (Monthly)</label>
                                <input type="number" step="0.01" name="basic_salary" id="emp_salary" required value="20000"
                                       class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                            </div>
                            <div>
                                <label for="emp_total_salary" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Total Salary (Monthly)</label>
                                <input type="number" step="0.01" name="total_salary" id="emp_total_salary" required value="25000"
                                       class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="emp_req_days" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Required Days</label>
                                <input type="number" name="required_days" id="emp_req_days" required value="26" min="1" max="31"
                                       class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                            </div>
                            <div>
                                <label for="emp_ot" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Overtime Rate/hr</label>
                                <input type="number" step="0.01" name="overtime_rate" id="emp_ot" required value="15"
                                       class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex gap-3">
                            <button type="submit"
                                    class="flex-1 py-2.5 px-4 bg-primary hover:bg-primary-hover text-white font-medium rounded-lg transition text-sm">
                                Register Employee
                            </button>
                            <button type="button" onclick="document.getElementById('create-employee-drawer').classList.add('hidden')"
                                    class="py-2.5 px-4 bg-slate-250 dark:bg-slate-850 hover:bg-slate-350 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium rounded-lg transition text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sidebar Drawer Modal: Edit Employee -->
<div id="edit-employee-drawer" class="fixed inset-0 z-50 overflow-hidden hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute inset-0 bg-slate-950/75 transition-opacity" onclick="document.getElementById('edit-employee-drawer').classList.add('hidden')"></div>

        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div class="pointer-events-auto w-screen max-w-md">
                <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-xl">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-950/40">
                        <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                            <i data-lucide="edit" class="w-5 h-5 text-primary"></i>
                            <span>Edit Employee Profile</span>
                        </h2>
                        <button onclick="document.getElementById('edit-employee-drawer').classList.add('hidden')" class="text-slate-500 hover:text-slate-400 font-bold p-2">✕</button>
                    </div>

                    <form id="edit-employee-form" method="POST" class="flex-1 p-6 space-y-5">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="edit_emp_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Full Name</label>
                            <input type="text" name="name" id="edit_emp_name" required
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <div>
                            <label for="edit_emp_email" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Email Address</label>
                            <input type="email" name="email" id="edit_emp_email" required
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        <div>
                            <label for="edit_emp_contact_number" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Mobile / Contact Number</label>
                            <input type="text" name="contact_number" id="edit_emp_contact_number" placeholder="e.g. 0771234567"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>

                        @if(auth()->user()->isSuperManager())
                        <div>
                            <label for="edit_emp_password" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Password (Leave blank to keep current)</label>
                            <input type="password" name="password" id="edit_emp_password" placeholder="••••••••"
                                   class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        </div>
                        @endif


                        <div>
                            <label for="edit_emp_role" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">System Role</label>
                            <select name="role" id="edit_emp_role" required
                                    class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm cursor-pointer">
                                @foreach(\App\Models\Role::all() as $roleOption)
                                    @if($roleOption->name !== 'super-manager' || auth()->user()->isSuperManager())
                                        <option value="{{ $roleOption->name }}">{{ $roleOption->label }}</option>
                                    @endif
                                @endforeach
                            </select>

                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="edit_emp_salary" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Basic Salary</label>
                                <input type="number" step="0.01" name="basic_salary" id="edit_emp_salary" required
                                       class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                            </div>
                            <div>
                                <label for="edit_emp_total_salary" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Total Salary</label>
                                <input type="number" step="0.01" name="total_salary" id="edit_emp_total_salary" required
                                       class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="edit_emp_req_days" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Required Days</label>
                                <input type="number" name="required_days" id="edit_emp_req_days" required min="1" max="31"
                                       class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                            </div>
                            <div>
                                <label for="edit_emp_ot" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Overtime Rate/hr</label>
                                <input type="number" step="0.01" name="overtime_rate" id="edit_emp_ot" required
                                       class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex gap-3">
                            <button type="submit"
                                    class="flex-1 py-2.5 px-4 bg-primary hover:bg-primary-hover text-white font-medium rounded-lg transition text-sm">
                                Save Employee Changes
                            </button>
                            <button type="button" onclick="document.getElementById('edit-employee-drawer').classList.add('hidden')"
                                    class="py-2.5 px-4 bg-slate-250 dark:bg-slate-850 hover:bg-slate-350 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium rounded-lg transition text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- 4. TAB: Salary Advances -->
    <div id="tab-advances" class="space-y-6 tab-content hidden">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Employee Salary Advances</h3>
                <p class="text-xs text-slate-500 mt-1">Advances are interest-free cash payments made to employees, automatically deducted from their next payslip.</p>
            </div>
            <button onclick="document.getElementById('create-advance-drawer').classList.remove('hidden')"
                    class="px-4 py-2 bg-primary hover:bg-primary-hover text-white font-medium rounded-lg text-sm transition flex items-center gap-1.5 shadow-sm">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Record Advance Payment</span>
            </button>
        </div>

        <div class="app-card rounded-2xl overflow-x-auto shadow-xs">
            <table class="datatable w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-100/60 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                        <th class="py-4 px-6">Employee</th>
                        <th class="py-4 px-6">Date</th>
                        <th class="py-4 px-6">Reason / Emergency Notes</th>
                        <th class="py-4 px-6 text-right">Amount</th>
                        <th class="py-4 px-6">Status</th>
                        <th class="py-4 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-850/60">
                    @forelse($advances as $adv)
                        <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-900/30 transition text-slate-750 dark:text-slate-300">
                            <td class="py-4 px-6 font-semibold text-slate-855 dark:text-slate-200 capitalize">
                                {{ $adv->user->name }}
                                <span class="block text-[10px] text-slate-500 mt-0.5 capitalize">{{ $adv->user->role }}</span>
                            </td>
                            <td class="py-4 px-6 font-mono text-xs">
                                {{ $adv->advance_date->format('Y-m-d') }}
                            </td>
                            <td class="py-4 px-6 text-xs max-w-xs truncate" title="{{ $adv->reason }}">
                                {{ $adv->reason ?: 'Emergency advance payment' }}
                            </td>
                            <td class="py-4 px-6 text-right font-mono font-semibold text-slate-800 dark:text-slate-205">
                                {{ config('app.currency', 'Rs.') }}{{ number_format($adv->amount, 2) }}
                            </td>
                            <td class="py-4 px-6 text-xs">
                                @if($adv->status === 'pending')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-600 border border-amber-500/20">Pending Recovery</span>
                                @elseif($adv->status === 'deducted')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-500/10 text-green-600 border border-green-500/20">Recovered (Deducted)</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-500/10 text-red-650 dark:text-red-400 border border-red-500/20">Cancelled</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right">
                                @if($adv->status === 'pending')
                                    <form action="{{ route('payroll.advances.destroy', $adv->id) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Are you sure you want to cancel and delete this advance payment? This will clear the ledger entry.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-bold text-red-650 bg-red-500/10 border border-red-500/20 px-2.5 py-1 rounded transition hover:bg-red-650 hover:text-white cursor-pointer flex items-center gap-1 justify-end ml-auto">
                                            <i data-lucide="trash-2" class="w-3 h-3"></i>
                                            <span>Cancel</span>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-slate-400 font-italic text-xs">No actions</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-500">
                                No employee advance payments recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Sidebar Drawer Modal: Record New Advance -->
        <div id="create-advance-drawer" class="fixed inset-0 z-50 overflow-hidden hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute inset-0 bg-slate-955/75 transition-opacity" onclick="document.getElementById('create-advance-drawer').classList.add('hidden')"></div>

                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <div class="pointer-events-auto w-screen max-w-md">
                        <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-xl">
                            <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-955/40">
                                <h2 class="text-lg font-bold text-slate-850 dark:text-slate-200 flex items-center gap-2">
                                    <i data-lucide="plus-circle" class="w-5 h-5 text-primary"></i>
                                    <span>Record Advance Payment</span>
                                </h2>
                                <button onclick="document.getElementById('create-advance-drawer').classList.add('hidden')" class="text-slate-500 hover:text-slate-400 font-bold p-2 cursor-pointer">✕</button>
                            </div>

                            <form action="{{ route('payroll.advances.store') }}" method="POST" class="flex-1 p-6 space-y-5">
                                @csrf

                                <!-- Select Employee -->
                                <div>
                                    <label for="adv_user_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Select Employee</label>
                                    <select name="user_id" id="adv_user_id" required
                                            class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm cursor-pointer">
                                        <option value="">-- Choose employee --</option>
                                        @foreach($users as $emp)
                                            <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->role }})</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Date -->
                                <div>
                                    <label for="adv_date" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Payment Date</label>
                                    <input type="date" name="advance_date" id="adv_date" required value="{{ date('Y-m-d') }}"
                                           class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                                </div>

                                <!-- Amount -->
                                <div>
                                    <label for="adv_amount" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Advance Amount (Rs.)</label>
                                    <input type="number" step="0.01" min="1" name="amount" id="adv_amount" required placeholder="0.00"
                                           class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono font-semibold">
                                </div>

                                <!-- Reason -->
                                <div>
                                    <label for="adv_reason" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Reason / Emergency Details</label>
                                    <textarea name="reason" id="adv_reason" rows="4" placeholder="Emergency hospital expenses, personal advance..."
                                              class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm"></textarea>
                                </div>

                                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex gap-3">
                                    <button type="submit"
                                            class="flex-1 py-2.5 px-4 bg-primary hover:bg-primary-hover text-white font-medium rounded-lg transition text-sm">
                                        Confirm & Post Ledger
                                    </button>
                                    <button type="button" onclick="document.getElementById('create-advance-drawer').classList.add('hidden')"
                                            class="py-2.5 px-4 bg-slate-250 dark:bg-slate-850 hover:bg-slate-350 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium rounded-lg transition text-sm">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
    // Tab switcher function
    function switchTab(tabId) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        // Remove active colors from all tabs
        document.getElementById('btn-tab-slips').className = "px-4 py-2 font-semibold text-sm border-b-2 border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300 transition";
        document.getElementById('btn-tab-attendance').className = "px-4 py-2 font-semibold text-sm border-b-2 border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300 transition";
        document.getElementById('btn-tab-employees').className = "px-4 py-2 font-semibold text-sm border-b-2 border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300 transition";
        document.getElementById('btn-tab-advances').className = "px-4 py-2 font-semibold text-sm border-b-2 border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300 transition";
        
        // Show target tab
        document.getElementById(tabId).classList.remove('hidden');
        // Set active style
        document.getElementById('btn-' + tabId).className = "px-4 py-2 font-semibold text-sm border-b-2 border-primary text-primary transition";

        // Save selected tab in localStorage to keep tab active on submit redirects
        localStorage.setItem('payroll_active_tab', tabId);
    }

    // Restore active tab on load
    document.addEventListener('DOMContentLoaded', () => {
        const activeTab = localStorage.getItem('payroll_active_tab') || 'tab-slips';
        switchTab(activeTab);
    });

    // Helper to toggle Time inputs based on attendance status in bulk form
    function toggleTimeInputs(selectElement, userId) {
        const container = document.getElementById(`time-inputs-${userId}`);
        if (selectElement.value === 'present') {
            container.classList.remove('opacity-40', 'pointer-events-none');
            container.querySelectorAll('input').forEach(i => i.removeAttribute('disabled'));
        } else {
            container.classList.add('opacity-40', 'pointer-events-none');
            container.querySelectorAll('input').forEach(i => i.setAttribute('disabled', 'true'));
        }
    }

    // Populate and open Edit Employee drawer
    function openEditEmployeeDrawer(emp) {
        document.getElementById('edit-employee-form').action = `/employees/${emp.id}`;
        document.getElementById('edit_emp_name').value = emp.name;
        document.getElementById('edit_emp_email').value = emp.email;
        document.getElementById('edit_emp_contact_number').value = emp.contact_number || '';
        document.getElementById('edit_emp_role').value = emp.role;
        document.getElementById('edit_emp_salary').value = emp.basic_salary;
        document.getElementById('edit_emp_total_salary').value = emp.total_salary;
        document.getElementById('edit_emp_req_days').value = emp.required_days;
        document.getElementById('edit_emp_ot').value = emp.overtime_rate;
        document.getElementById('edit-employee-drawer').classList.remove('hidden');
    }
</script>
@endsection
