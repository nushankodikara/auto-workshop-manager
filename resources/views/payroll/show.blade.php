@extends('layouts.app')

@section('title', 'Salary Slip')

@section('content')
<div class="space-y-6 max-w-3xl mx-auto">

    <!-- Actions bar (hidden during print) -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4 print:hidden">
        <div class="flex items-center gap-3">
            <a href="{{ route('payroll.index', ['year' => $payrollSlip->year, 'month' => $payrollSlip->month]) }}" class="text-sm font-semibold text-primary hover:underline">
                &larr; Back to Payroll
            </a>
            <span class="text-slate-400">|</span>
            <span class="text-slate-650 dark:text-slate-350 font-semibold text-sm capitalize">Payslip Detail</span>
        </div>

        <div class="flex items-center gap-3">
            <!-- Mark as Paid form (Only show if draft) -->
            @if($payrollSlip->status === 'draft')
                <form action="{{ route('payroll.update-status', $payrollSlip->id) }}" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="paid">
                    <button type="submit" 
                            class="px-4 py-2 bg-green-650 hover:bg-green-650 text-white rounded-lg text-xs font-bold transition shadow-sm flex items-center gap-1.5">
                        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                        <span>Mark as Paid</span>
                    </button>
                </form>
            @endif

            <!-- Print Button -->
            <button onclick="window.print()" 
                    class="px-4 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-bold transition flex items-center gap-1.5">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Print Payslip</span>
            </button>
        </div>
    </div>

    <!-- Printable Payslip Card -->
    <div class="app-card rounded-2xl p-8 space-y-6 shadow-xs print:bg-white print:text-black print:border-0 print:p-0">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-5 print:border-black/10">
            <div>
                <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100 print:text-black">{{ config('app.name', 'Auto Workshop Manager') }}</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 print:text-black/60 font-medium">Salary Payslip & Disbursal Receipt</p>
            </div>
            <div class="text-left md:text-right text-xs">
                <div class="font-bold text-slate-700 dark:text-slate-200 print:text-black">Period: {{ date('F Y', mktime(0, 0, 0, $payrollSlip->month, 1, $payrollSlip->year)) }}</div>
                <div class="text-slate-500 font-mono mt-0.5 print:text-black/60">Slip ID: PSL-{{ str_pad($payrollSlip->id, 5, '0', STR_PAD_LEFT) }}</div>
                <div class="mt-2">
                    @if($payrollSlip->status === 'paid')
                        <span class="px-2.5 py-0.5 rounded bg-green-500/10 text-green-600 dark:text-green-400 border border-green-500/20 font-bold text-[10px] uppercase print:border-black/20 print:text-black">Paid</span>
                    @else
                        <span class="px-2.5 py-0.5 rounded bg-yellow-500/10 text-yellow-600 dark:text-yellow-400 border border-yellow-500/20 font-bold text-[10px] uppercase print:border-black/20 print:text-black">Draft / Pending</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Employee Info -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs border-b border-slate-200 dark:border-slate-800 pb-5 print:border-black/10">
            <div>
                <span class="text-slate-500 print:text-black/40 font-semibold uppercase tracking-wider block mb-1">Employee</span>
                <span class="font-bold text-slate-800 dark:text-slate-200 block capitalize print:text-black text-sm">{{ $payrollSlip->user->name }}</span>
            </div>
            <div>
                <span class="text-slate-500 print:text-black/40 font-semibold uppercase tracking-wider block mb-1">Email</span>
                <span class="font-semibold text-slate-650 dark:text-slate-350 block print:text-black">{{ $payrollSlip->user->email }}</span>
            </div>
            <div>
                <span class="text-slate-500 print:text-black/40 font-semibold uppercase tracking-wider block mb-1">Role</span>
                <span class="font-semibold text-slate-650 dark:text-slate-350 block capitalize print:text-black">{{ $payrollSlip->user->role }}</span>
            </div>
            <div>
                <span class="text-slate-500 print:text-black/40 font-semibold uppercase tracking-wider block mb-1">Base Salary</span>
                <span class="font-bold text-slate-850 dark:text-slate-200 font-mono block print:text-black text-sm">{{ config('app.currency', '$') }}{{ number_format($payrollSlip->basic_salary, 2) }}</span>
            </div>
        </div>

        <!-- Attendance & Overtime Breakdown -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-2">
            <div class="p-4 bg-slate-50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-xl space-y-2 text-xs">
                <div class="font-bold text-slate-700 dark:text-slate-300 border-b border-slate-200 dark:border-slate-800 pb-1 flex items-center gap-1.5">
                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-primary"></i>
                    <span>Attendance Summary</span>
                </div>
                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                    <span>Required Days:</span>
                    <span class="font-mono font-semibold">{{ $payrollSlip->required_days }} days</span>
                </div>
                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                    <span>Attended Days:</span>
                    <span class="font-mono font-semibold">{{ $payrollSlip->attended_days }} days</span>
                </div>
                <div class="flex justify-between font-bold text-slate-800 dark:text-slate-200 border-t border-slate-200 dark:border-slate-800 pt-1">
                    <span>Prorated Base Salary:</span>
                    <span class="font-mono">{{ config('app.currency', '$') }}{{ number_format($payrollSlip->prorated_salary, 2) }}</span>
                </div>
            </div>

            <div class="p-4 bg-slate-50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 rounded-xl space-y-2 text-xs">
                <div class="font-bold text-slate-700 dark:text-slate-300 border-b border-slate-200 dark:border-slate-800 pb-1 flex items-center gap-1.5">
                    <i data-lucide="clock" class="w-3.5 h-3.5 text-primary"></i>
                    <span>Overtime Summary</span>
                </div>
                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                    <span>Overtime Hours:</span>
                    <span class="font-mono font-semibold">{{ $payrollSlip->overtime_hours }} hrs</span>
                </div>
                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                    <span>Hourly OT Rate:</span>
                    <span class="font-mono font-semibold">{{ config('app.currency', '$') }}{{ number_format($payrollSlip->overtime_rate, 2) }}/hr</span>
                </div>
                <div class="flex justify-between font-bold text-slate-800 dark:text-slate-200 border-t border-slate-200 dark:border-slate-800 pt-1">
                    <span>Overtime Payout:</span>
                    <span class="font-mono">{{ config('app.currency', '$') }}{{ number_format($payrollSlip->overtime_amount, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Additions / Deductions Lists -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-200 dark:border-slate-800 print:border-black/10">
            <!-- Allowances / Additions Column -->
            <div class="space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-2 print:text-black/50 print:border-black/10">Additions / Allowances</h4>
                <div class="space-y-2">
                    <div class="flex justify-between text-xs text-slate-600 dark:text-slate-455 print:text-black/70">
                        <span>Prorated Salary:</span>
                        <span class="font-mono font-semibold">{{ config('app.currency', '$') }}{{ number_format($payrollSlip->prorated_salary, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-xs text-slate-600 dark:text-slate-455 print:text-black/70">
                        <span>Overtime Payout:</span>
                        <span class="font-mono font-semibold">+{{ config('app.currency', '$') }}{{ number_format($payrollSlip->overtime_amount, 2) }}</span>
                    </div>
                    @php $hasAdditions = false; @endphp
                    @foreach($payrollSlip->items->where('type', 'addition') as $item)
                        @php $hasAdditions = true; @endphp
                        <div class="flex justify-between text-xs text-slate-700 dark:text-slate-300 print:text-black">
                            <span>{{ $item->category_name }}:</span>
                            <span class="font-mono text-green-600 dark:text-green-400 print:text-black">+{{ config('app.currency', '$') }}{{ number_format($item->amount, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Deductions Column -->
            <div class="space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-2 print:text-black/50 print:border-black/10">Deductions</h4>
                <div class="space-y-2">
                    @php $hasDeductions = false; @endphp
                    @foreach($payrollSlip->items->where('type', 'deduction') as $item)
                        @php $hasDeductions = true; @endphp
                        <div class="flex justify-between text-xs text-slate-700 dark:text-slate-300 print:text-black">
                            <span>{{ $item->category_name }}:</span>
                            <span class="font-mono text-red-650 dark:text-red-400 print:text-black">-{{ config('app.currency', '$') }}{{ number_format($item->amount, 2) }}</span>
                        </div>
                    @endforeach
                    @if(!$hasDeductions)
                        <div class="text-[10px] text-slate-400 italic">No deductions applied.</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Totals summary block -->
        <div class="flex justify-end pt-6 border-t border-slate-200 dark:border-slate-800 print:border-black/10">
            <div class="w-full md:w-80 space-y-2 text-xs">
                <div class="flex justify-between text-slate-550 dark:text-slate-450">
                    <span>Base Basic (Contractual):</span>
                    <span class="font-mono">{{ config('app.currency', '$') }}{{ number_format($payrollSlip->basic_salary, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-550 dark:text-slate-450">
                    <span>Prorated Salary:</span>
                    <span class="font-mono">{{ config('app.currency', '$') }}{{ number_format($payrollSlip->prorated_salary, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-550 dark:text-slate-450">
                    <span>Overtime Amount:</span>
                    <span class="font-mono text-green-600 dark:text-green-400">+{{ config('app.currency', '$') }}{{ number_format($payrollSlip->overtime_amount, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-550 dark:text-slate-450">
                    <span>Total Allowances:</span>
                    <span class="font-mono text-green-600 dark:text-green-400">+{{ config('app.currency', '$') }}{{ number_format($payrollSlip->allowance, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-550 dark:text-slate-450">
                    <span>Total Deductions:</span>
                    <span class="font-mono text-red-655 dark:text-red-400">-{{ config('app.currency', '$') }}{{ number_format($payrollSlip->deductions, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm font-bold border-t border-slate-200 dark:border-slate-800 pt-2 text-slate-800 dark:text-slate-100 print:text-black print:border-black/10">
                    <span>Net Disbursed Amount:</span>
                    <div class="font-mono text-base text-primary inline-flex items-center gap-0.5">
                        <span>{{ config('app.currency', '$') }}</span>
                        <span>{{ number_format($payrollSlip->net_salary, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Signatures (visible during print) -->
        <div class="hidden print:flex justify-between mt-20 text-xs">
            <div class="w-48 text-center border-t border-black pt-2">
                Employee Signature
            </div>
            <div class="w-48 text-center border-t border-black pt-2">
                Authorized Signatory
            </div>
        </div>

    </div>

</div>

<!-- Styles override for clean printing layout -->
<style>
    @media print {
        aside {
            display: none !important;
        }
        header {
            display: none !important;
        }
        main {
            padding: 0 !important;
            background: white !important;
        }
        body {
            background: white !important;
            color: black !important;
        }
    }
</style>
@endsection
