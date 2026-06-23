@extends('layouts.app')

@section('title', 'Bulk Attendance Editor')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">

    <!-- Header navigation -->
    <div class="flex items-center gap-3 border-b border-slate-200 dark:border-slate-800 pb-4">
        <a href="{{ route('payroll.index', ['year' => $year, 'month' => $month]) }}" class="text-sm font-semibold text-primary hover:underline">
            &larr; Back to Payroll
        </a>
        <span class="text-slate-400">|</span>
        <span class="text-slate-600 dark:text-slate-350 font-semibold text-sm capitalize">Monthly Attendance for {{ $user->name }}</span>
    </div>

    <!-- Bulk Monthly Attendance Editor Card -->
    <div class="app-card rounded-2xl p-6 shadow-xs">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">Bulk Month Log</h3>
                <p class="text-xs text-slate-500 mt-1">Period: {{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}</p>
            </div>
            <div class="text-xs text-slate-500 font-semibold uppercase tracking-wider">
                Base Salary: {{ config('app.currency', '$') }}{{ number_format($user->basic_salary, 2) }}
            </div>
        </div>

        <form action="{{ route('payroll.attendance.employee.store', $user->id) }}" method="POST" class="space-y-6">
            @csrf
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="month" value="{{ $month }}">

            <div class="max-h-[500px] overflow-y-auto pr-2 border border-slate-200 dark:border-slate-800 rounded-xl divide-y divide-slate-200 dark:divide-slate-800 bg-slate-50/50 dark:bg-slate-950/20">
                @for($d = 1; $d <= $daysInMonth; $d++)
                    @php
                        $dayOfWeek = date('l', mktime(0, 0, 0, $month, $d, $year));
                        $record = $records->get($d);
                        $currentStatus = $record ? $record->status : 'n/a';
                        $currentOt = $record ? $record->overtime_hours : 0.00;
                    @endphp
                    <div class="p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-slate-100/40 dark:hover:bg-slate-900/30 transition">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-slate-200 dark:bg-slate-800 font-mono font-bold text-slate-700 dark:text-slate-300 flex items-center justify-center text-sm">
                                {{ str_pad($d, 2, '0', STR_PAD_LEFT) }}
                            </span>
                            <div>
                                <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ $dayOfWeek }}</span>
                                <span class="block text-[9px] text-slate-450">{{ sprintf('%04d-%02d-%02d', $year, $month, $d) }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <!-- Status Dropdown -->
                            <div>
                                <select name="status[{{ $d }}]"
                                        class="px-2.5 py-1.5 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary cursor-pointer">
                                    <option value="present" {{ $currentStatus === 'present' ? 'selected' : '' }}>Present</option>
                                    <option value="half_day" {{ $currentStatus === 'half_day' ? 'selected' : '' }}>Half Day</option>
                                    <option value="absent" {{ $currentStatus === 'absent' ? 'selected' : '' }}>Absent</option>
                                    <option value="leave" {{ $currentStatus === 'leave' ? 'selected' : '' }}>Leave</option>
                                    <option value="n/a" {{ $currentStatus === 'n/a' ? 'selected' : '' }}>N/A (Remove/Not Logged)</option>
                                </select>
                            </div>

                            <!-- Overtime Input -->
                            <div class="flex items-center gap-1">
                                <label class="text-[10px] text-slate-450">OT Hours:</label>
                                <input type="number" step="0.5" name="overtime[{{ $d }}]" value="{{ $currentOt > 0 ? $currentOt : '' }}" placeholder="0" min="0" max="24"
                                       class="w-16 px-2 py-1.5 text-center app-input rounded-lg text-slate-900 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:border-primary text-xs">
                            </div>
                        </div>
                    </div>
                @endfor
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                <a href="{{ route('payroll.index', ['year' => $year, 'month' => $month]) }}"
                   class="py-2 px-4 bg-slate-200 dark:bg-slate-800 hover:bg-slate-350 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold rounded-lg text-xs transition">
                    Cancel
                </a>
                <button type="submit"
                        class="py-2 px-5 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg text-xs transition shadow-sm flex items-center gap-1">
                    <i data-lucide="save" class="w-3.5 h-3.5"></i>
                    <span>Save All Changes</span>
                </button>
            </div>

        </form>
    </div>

</div>
@endsection
