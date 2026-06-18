@extends('layouts.app')

@section('title', 'Payroll Workspace')

@section('content')
<div class="space-y-6">

    <!-- Filters & Actions Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-800 pb-4">
        <!-- Date selectors -->
        <form action="{{ route('payroll.index') }}" method="GET" class="flex items-center gap-3">
            <div>
                <select name="year" onchange="this.form.submit()" 
                        class="px-3 py-1.5 bg-slate-900 border border-slate-800 rounded-lg text-slate-200 text-xs focus:outline-none focus:border-indigo-500 cursor-pointer">
                    @for($y = date('Y') - 2; $y <= date('Y') + 2; $y++)
                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <select name="month" onchange="this.form.submit()"
                        class="px-3 py-1.5 bg-slate-900 border border-slate-800 rounded-lg text-slate-200 text-xs focus:outline-none focus:border-indigo-500 cursor-pointer">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                        </option>
                    @endfor
                </select>
            </div>
        </form>
        
        <div>
            <span class="text-xs text-slate-500">
                Period: {{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}
            </span>
        </div>
    </div>

    <!-- Main Workspace Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left: Salary Slips Table -->
        <div class="lg:col-span-2 space-y-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Generated Payslips</h3>
            <div class="glass-card rounded-2xl overflow-hidden border border-slate-900">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-900/60 border-b border-slate-850/80 text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                            <th class="py-4 px-6">Employee</th>
                            <th class="py-4 px-6">Basic</th>
                            <th class="py-4 px-6">Allowances</th>
                            <th class="py-4 px-6">Deductions</th>
                            <th class="py-4 px-6">Net Payout</th>
                            <th class="py-4 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-850/60">
                        @forelse($slips as $slip)
                            <tr class="hover:bg-slate-900/40 transition">
                                <td class="py-4 px-6 font-semibold text-slate-200 capitalize">
                                    <a href="{{ route('payroll.show', $slip->id) }}" class="hover:text-indigo-400">
                                        {{ $slip->user->name }}
                                    </a>
                                    <span class="block text-[10px] text-slate-500 capitalize mt-0.5">{{ $slip->user->role }}</span>
                                </td>
                                <td class="py-4 px-6 font-mono text-slate-400">${{ number_format($slip->basic_salary, 2) }}</td>
                                <td class="py-4 px-6 font-mono text-green-400">+${{ number_format($slip->allowance, 2) }}</td>
                                <td class="py-4 px-6 font-mono text-red-400">-${{ number_format($slip->deductions, 2) }}</td>
                                <td class="py-4 px-6 font-mono text-slate-200 font-bold">${{ number_format($slip->net_salary, 2) }}</td>
                                <td class="py-4 px-6 text-right">
                                    <a href="{{ route('payroll.show', $slip->id) }}" 
                                       class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 bg-indigo-500/10 border border-indigo-500/20 px-2 py-1 rounded transition">
                                        View Slip
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-500">
                                    No payslips generated for this period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right: Staff Members without payslips -->
        <div class="glass-card rounded-2xl p-6">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800/80 pb-3 mb-4">
                ➕ Outstanding Payslips
            </h3>

            <div class="space-y-4">
                @php $hasOutstanding = false; @endphp
                @foreach($users as $user)
                    <!-- Check if user already has a slip for this month -->
                    @php
                        $hasSlip = $slips->contains('user_id', $user->id);
                    @endphp

                    @if(!$hasSlip)
                        @php $hasOutstanding = true; @endphp
                        <div class="flex items-center justify-between p-3.5 bg-slate-900/40 rounded-xl border border-slate-850/80 text-xs capitalize">
                            <div>
                                <span class="font-bold text-slate-250 block">{{ $user->name }}</span>
                                <span class="text-[10px] text-slate-500 mt-0.5">Role: {{ $user->role }} • Basic: ${{ number_format($user->basic_salary, 2) }}</span>
                            </div>
                            <div>
                                <a href="{{ route('payroll.create', ['user' => $user->id, 'year' => $year, 'month' => $month]) }}"
                                   class="px-2.5 py-1.5 bg-indigo-650 hover:bg-indigo-650 text-slate-200 font-semibold rounded-lg transition border border-indigo-600/30">
                                    Generate
                                </a>
                            </div>
                        </div>
                    @endif
                @endforeach

                @if(!$hasOutstanding)
                    <div class="text-slate-650 text-xs text-center py-4">
                        All active staff members have payslips generated for this month.
                    </div>
                @endif
            </div>
        </div>

    </div>

</div>
@endsection
