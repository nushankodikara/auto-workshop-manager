@extends('layouts.app')

@section('title', 'Salary Slip')

@section('content')
<div class="space-y-6 max-w-3xl mx-auto">

    <!-- Actions bar (hidden during print) -->
    <div class="flex items-center justify-between border-b border-slate-800 pb-4 print:hidden">
        <div class="flex items-center gap-3">
            <a href="{{ route('payroll.index', ['year' => $payrollSlip->year, 'month' => $payrollSlip->month]) }}" class="text-sm font-semibold text-indigo-400 hover:text-indigo-300">
                &larr; Back to Payroll
            </a>
            <span class="text-slate-600">|</span>
            <span class="text-slate-350 font-semibold text-sm capitalize">Payslip Detail</span>
        </div>

        <div class="flex items-center gap-3">
            <!-- Mark as Paid form (Only show if draft) -->
            @if($payrollSlip->status === 'draft')
                <form action="{{ route('payroll.update-status', $payrollSlip->id) }}" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="paid">
                    <button type="submit" 
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition">
                        Mark as Paid / Disbursed
                    </button>
                </form>
            @endif

            <!-- Print Button -->
            <button onclick="window.print()" 
                    class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-lg text-xs font-bold transition">
                🖨️ Print Payslip
            </button>
        </div>
    </div>

    <!-- Printable Payslip Card -->
    <div class="glass-card rounded-2xl p-8 space-y-6 bg-slate-900/40 border border-slate-850 print:bg-white print:text-black print:border-0 print:p-0">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between gap-4 border-b border-slate-800/85 pb-5 print:border-black/10">
            <div>
                <h2 class="text-xl font-bold text-slate-100 print:text-black">{{ config('app.name', 'Auto Workshop Manager') }}</h2>
                <p class="text-xs text-slate-400 print:text-black/60">Salary Payslip & Disbursal Receipt</p>
            </div>
            <div class="text-left md:text-right text-xs">
                <div class="font-bold text-slate-250 print:text-black">Period: {{ date('F Y', mktime(0, 0, 0, $payrollSlip->month, 1, $payrollSlip->year)) }}</div>
                <div class="text-slate-500 font-mono mt-0.5 print:text-black/60">Slip ID: PSL-{{ str_pad($payrollSlip->id, 5, '0', STR_PAD_LEFT) }}</div>
                <div class="mt-2">
                    @if($payrollSlip->status === 'paid')
                        <span class="px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-450 border border-emerald-500/20 font-bold uppercase print:border-black/20 print:text-black">Paid</span>
                    @else
                        <span class="px-2 py-0.5 rounded bg-red-500/10 text-red-400 border border-red-500/20 font-bold uppercase print:border-black/20 print:text-black">Draft / Pending</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Employee Info -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="text-slate-500 block print:text-black/40">Employee Name</span>
                <span class="font-bold text-slate-200 mt-1 block capitalize print:text-black">{{ $payrollSlip->user->name }}</span>
            </div>
            <div>
                <span class="text-slate-500 block print:text-black/40">Employee Email</span>
                <span class="font-semibold text-slate-350 mt-1 block print:text-black">{{ $payrollSlip->user->email }}</span>
            </div>
            <div>
                <span class="text-slate-500 block print:text-black/40">Role</span>
                <span class="font-semibold text-slate-350 mt-1 block capitalize print:text-black">{{ $payrollSlip->user->role }}</span>
            </div>
            <div>
                <span class="text-slate-500 block print:text-black/40">Base Basic Salary</span>
                <span class="font-semibold text-slate-350 font-mono mt-1 block print:text-black">${{ number_format($payrollSlip->basic_salary, 2) }}</span>
            </div>
        </div>

        <!-- Additions / Deductions Lists -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-850 print:border-black/10">
            <!-- Allowances / Additions Column -->
            <div class="space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-850 pb-2 print:text-black/50 print:border-black/10">Additions / Allowances</h4>
                <div class="space-y-2">
                    <div class="flex justify-between text-xs text-slate-400 print:text-black/70">
                        <span>Basic Salary:</span>
                        <span class="font-mono">${{ number_format($payrollSlip->basic_salary, 2) }}</span>
                    </div>
                    @php $hasAdditions = false; @endphp
                    @foreach($payrollSlip->items->where('type', 'addition') as $item)
                        @php $hasAdditions = true; @endphp
                        <div class="flex justify-between text-xs text-slate-300 print:text-black">
                            <span>{{ $item->category_name }}:</span>
                            <span class="font-mono text-green-400 print:text-black">+${{ number_format($item->amount, 2) }}</span>
                        </div>
                    @endforeach
                    @if(!$hasAdditions)
                        <div class="text-[10px] text-slate-600 italic">No additional allowances.</div>
                    @endif
                </div>
            </div>

            <!-- Deductions Column -->
            <div class="space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-850 pb-2 print:text-black/50 print:border-black/10">Deductions</h4>
                <div class="space-y-2">
                    @php $hasDeductions = false; @endphp
                    @foreach($payrollSlip->items->where('type', 'deduction') as $item)
                        @php $hasDeductions = true; @endphp
                        <div class="flex justify-between text-xs text-slate-300 print:text-black">
                            <span>{{ $item->category_name }}:</span>
                            <span class="font-mono text-red-400 print:text-black">-${{ number_format($item->amount, 2) }}</span>
                        </div>
                    @endforeach
                    @if(!$hasDeductions)
                        <div class="text-[10px] text-slate-650 italic">No deductions applied.</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Totals summary block -->
        <div class="flex justify-end pt-6 border-t border-slate-850 print:border-black/10">
            <div class="w-full md:w-72 space-y-2 text-xs">
                <div class="flex justify-between text-slate-500 print:text-black/60">
                    <span>Base Basic:</span>
                    <span class="font-mono">${{ number_format($payrollSlip->basic_salary, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-500 print:text-black/60">
                    <span>Total Additions:</span>
                    <span class="font-mono text-green-400 print:text-black">+${{ number_format($payrollSlip->allowance, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-500 print:text-black/60">
                    <span>Total Deductions:</span>
                    <span class="font-mono text-red-400 print:text-black">-${{ number_format($payrollSlip->deductions, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm font-bold border-t border-slate-800 pt-2 text-slate-100 print:text-black print:border-black/10">
                    <span>Net Disbursed Amount:</span>
                    <span class="font-mono text-base">${{ number_format($payrollSlip->net_salary, 2) }}</span>
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
