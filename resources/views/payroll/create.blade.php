@extends('layouts.app')

@section('title', 'Generate Salary Slip')

@section('content')
<div class="space-y-6 max-w-3xl mx-auto">

    <!-- Header navigation -->
    <div class="flex items-center gap-3 border-b border-slate-200 dark:border-slate-800 pb-4">
        <a href="{{ route('payroll.index', ['year' => $year, 'month' => $month]) }}" class="text-sm font-semibold text-primary hover:underline">
            &larr; Back to Payroll
        </a>
        <span class="text-slate-400">|</span>
        <span class="text-slate-650 dark:text-slate-350 font-semibold text-sm capitalize">New Payslip for {{ $user->name }}</span>
    </div>

    <!-- Generator Form -->
    <form action="{{ route('payroll.store') }}" method="POST" class="space-y-8">
        @csrf
        <input type="hidden" name="user_id" value="{{ $user->id }}">
        <input type="hidden" name="month" value="{{ $month }}">
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="total_salary" value="{{ $user->total_salary }}">

        <!-- 1. Employee Context -->
        <div class="app-card rounded-2xl p-6 grid grid-cols-1 md:grid-cols-4 gap-6 items-center shadow-xs">
            <div>
                <span class="text-xs text-slate-500 uppercase tracking-wider block font-semibold mb-1">Employee Name</span>
                <span class="font-bold text-slate-800 dark:text-slate-200 text-sm capitalize block">{{ $user->name }}</span>
            </div>
            <div>
                <span class="text-xs text-slate-500 uppercase tracking-wider block font-semibold mb-1">Pay Period</span>
                <span class="font-semibold text-slate-800 dark:text-slate-200 text-sm block">
                    {{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}
                </span>
            </div>
            <div>
                <span class="text-xs text-slate-500 uppercase tracking-wider block font-semibold mb-1">Contract Basic Salary</span>
                <span class="font-mono font-bold text-slate-850 dark:text-slate-200 text-sm block">
                    {{ config('app.currency', 'Rs.') }}{{ number_format($user->basic_salary, 2) }}
                </span>
                <input type="hidden" id="contract_salary" value="{{ $user->basic_salary }}">
            </div>
            <div>
                <span class="text-xs text-slate-500 uppercase tracking-wider block font-semibold mb-1">Contract Total Salary</span>
                <span class="font-mono font-bold text-slate-850 dark:text-slate-200 text-sm block">
                    {{ config('app.currency', 'Rs.') }}{{ number_format($user->total_salary, 2) }}
                </span>
            </div>
        </div>

        <!-- 2. Attendance & Pro-Rata Base Salary Calculations -->
        <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-550 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-2">
                <i data-lucide="calendar" class="w-4 h-4 text-primary"></i>
                <span>Attendance & Prorated Base Salary</span>
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label for="required_days" class="block text-xs text-slate-500 mb-1 font-semibold">Required Work Days</label>
                    <input type="number" name="required_days" id="required_days" value="{{ $requiredDays }}" min="1" max="31" oninput="recalculate()"
                           class="w-full px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 font-mono text-sm focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label for="attended_days" class="block text-xs text-slate-500 mb-1 font-semibold">Days Attended (Present)</label>
                    <input type="number" step="any" name="attended_days" id="attended_days" value="{{ $attendedDays }}" min="0" max="31" oninput="recalculate()"
                           class="w-full px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 font-mono text-sm focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label for="prorated_salary" class="block text-xs text-slate-500 mb-1 font-semibold">Prorated Basic Salary ({{ config('app.currency', 'Rs.') }})</label>
                    <input type="number" step="0.01" name="prorated_salary" id="prorated_salary" value="{{ $proratedSalary }}" oninput="recalculateTotal()"
                           class="w-full px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 font-mono text-sm focus:outline-none focus:border-primary">
                </div>
            </div>
            <p class="text-[10px] text-slate-500 leading-normal">
                Formula: (Attended Days / Required Days) * Contract Basic Salary. Calculated automatically, but can be overridden manually if needed.
            </p>
        </div>

        <!-- 3. Overtime Calculations -->
        <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-550 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-2">
                <i data-lucide="clock" class="w-4 h-4 text-primary"></i>
                <span>Overtime (OT) Log</span>
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label for="overtime_hours" class="block text-xs text-slate-500 mb-1 font-semibold">Overtime Hours Worked</label>
                    <input type="number" step="0.01" name="overtime_hours" id="overtime_hours" value="{{ $overtimeHours }}" oninput="recalculate()"
                           class="w-full px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 font-mono text-sm focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label for="overtime_rate" class="block text-xs text-slate-500 mb-1 font-semibold">Hourly OT Rate ({{ config('app.currency', 'Rs.') }})</label>
                    <input type="number" step="0.01" name="overtime_rate" id="overtime_rate" value="{{ $overtimeRate }}" oninput="recalculate()"
                           class="w-full px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 font-mono text-sm focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label for="overtime_amount" class="block text-xs text-slate-500 mb-1 font-semibold">OT Payout Amount ({{ config('app.currency', 'Rs.') }})</label>
                    <input type="number" step="0.01" name="overtime_amount" id="overtime_amount" value="{{ $overtimeAmount }}" oninput="recalculateTotal()"
                           class="w-full px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 font-mono text-sm focus:outline-none focus:border-primary">
                </div>
            </div>
            <p class="text-[10px] text-slate-500 leading-normal">
                Formula: Overtime Hours * Hourly OT Rate. Calculated automatically, but can be overridden manually if needed.
            </p>
        </div>

        <!-- 4. Allowances & Additions -->
        <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-550 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-4 h-4 text-green-600 dark:text-green-400"></i>
                <span>Allowances & Dynamic Additions</span>
            </h3>

            <div id="additions-container" class="space-y-3">
                @if($baseAllowance > 0)
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center addition-row">
                        <div class="md:col-span-3">
                            <input type="text" name="item_name[]" value="Base Allowance" readonly
                                   class="w-full px-4 py-2 bg-slate-100/60 dark:bg-slate-950/40 border border-slate-250 dark:border-slate-850 rounded-lg text-slate-500 dark:text-slate-400 text-xs focus:outline-none">
                            <input type="hidden" name="item_type[]" value="addition">
                        </div>
                        <div>
                            <input type="number" step="0.01" name="item_amount[]" placeholder="0.00" value="{{ $baseAllowance }}" oninput="recalculateTotal()"
                                   class="w-full px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 font-mono text-xs focus:outline-none focus:border-primary addition-amount">
                        </div>
                    </div>
                @endif

                @foreach($categories->where('type', 'addition') as $cat)
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center addition-row">
                        <div class="md:col-span-3">
                            <input type="text" name="item_name[]" value="{{ $cat->name }}" readonly
                                   class="w-full px-4 py-2 bg-slate-100/60 dark:bg-slate-950/40 border border-slate-250 dark:border-slate-850 rounded-lg text-slate-500 dark:text-slate-400 text-xs focus:outline-none">
                            <input type="hidden" name="item_type[]" value="addition">
                        </div>
                        <div>
                            <input type="number" step="0.01" name="item_amount[]" placeholder="0.00" value="{{ $cat->default_amount ?? '' }}" oninput="recalculateTotal()"
                                   class="w-full px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 font-mono text-xs focus:outline-none focus:border-primary addition-amount">
                        </div>
                    </div>
                @endforeach
            </div>

            <button type="button" onclick="addRow('addition', 'additions-container')"
                    class="py-1.5 px-3 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold rounded-lg text-xs transition border border-transparent hover:border-slate-300 dark:hover:border-slate-700/50 flex items-center gap-1">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>Add Custom Allowance</span>
            </button>
        </div>

        <!-- 5. Deductions Section -->
        <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-550 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-2">
                <i data-lucide="minus-circle" class="w-4 h-4 text-red-650 dark:text-red-400"></i>
                <span>Deductions / EPF / Advances</span>
            </h3>

            <div id="deductions-container" class="space-y-3">
                @foreach($categories->where('type', 'deduction') as $cat)
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center deduction-row">
                        <div class="md:col-span-3">
                            <input type="text" name="item_name[]" value="{{ $cat->name }}" readonly
                                   class="w-full px-4 py-2 bg-slate-100/60 dark:bg-slate-955/40 border border-slate-250 dark:border-slate-850 rounded-lg text-slate-500 dark:text-slate-400 text-xs focus:outline-none">
                            <input type="hidden" name="item_type[]" value="deduction">
                        </div>
                        <div>
                            @php
                                $val = $cat->default_amount ?? '';
                                if ($cat->name === 'Advance Payment' && isset($pendingAdvancesSum) && $pendingAdvancesSum > 0) {
                                    $val = $pendingAdvancesSum;
                                }
                            @endphp
                            <input type="number" step="0.01" name="item_amount[]" placeholder="0.00" value="{{ $val }}" oninput="recalculateTotal()"
                                   class="w-full px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 font-mono text-xs focus:outline-none focus:border-primary deduction-amount">
                        </div>
                    </div>
                @endforeach
            </div>

            <button type="button" onclick="addRow('deduction', 'deductions-container')"
                    class="py-1.5 px-3 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold rounded-lg text-xs transition border border-transparent hover:border-slate-300 dark:hover:border-slate-700/50 flex items-center gap-1">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                <span>Add Custom Deduction</span>
            </button>
        </div>

        <!-- 6. Total Summary Details -->
        <div class="app-card rounded-2xl p-6 bg-slate-50 dark:bg-slate-900/50 shadow-xs border border-slate-250 dark:border-slate-850 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <span class="text-xs text-slate-500 font-bold uppercase tracking-wider block">Estimated Net Payout</span>
                <span class="text-[10px] text-slate-450 mt-1 block">Recalculates in real time.</span>
            </div>
            <div class="text-2xl font-mono font-bold text-slate-800 dark:text-slate-100 flex items-center gap-1">
                <span>{{ config('app.currency', '$') }}</span>
                <span id="net_salary_display">0.00</span>
            </div>
        </div>

        <!-- Submit actions -->
        <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-850">
            <a href="{{ route('payroll.index', ['year' => $year, 'month' => $month]) }}"
               class="py-2.5 px-4 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold rounded-lg text-xs transition">
                Cancel
            </a>
            <button type="submit"
                    class="py-2.5 px-5 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg text-xs transition shadow-sm">
                Compile & Save Salary Slip
            </button>
        </div>

    </form>

</div>

<script>
    function addRow(type, containerId) {
        const container = document.getElementById(containerId);
        const newRow = document.createElement('div');
        newRow.className = `grid grid-cols-1 md:grid-cols-4 gap-4 items-center ${type}-row`;
        newRow.innerHTML = `
            <div class="md:col-span-3">
                <input type="text" name="item_name[]" placeholder="e.g., Target Allowance, advance deductions" required
                       class="w-full px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary">
                <input type="hidden" name="item_type[]" value="${type}">
            </div>
            <div class="flex gap-2">
                <input type="number" step="0.01" name="item_amount[]" placeholder="0.00" required oninput="recalculateTotal()"
                       class="flex-1 px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 font-mono text-xs focus:outline-none focus:border-primary ${type}-amount">
                <button type="button" onclick="this.closest('.${type}-row').remove(); recalculateTotal();" class="text-red-500 hover:text-red-400 font-bold px-2 text-sm">✕</button>
            </div>
        `;
        container.appendChild(newRow);
        lucide.createIcons();
    }

    function recalculate() {
        const contractSalary = parseFloat(document.getElementById('contract_salary').value) || 0;
        const requiredDays = parseInt(document.getElementById('required_days').value) || 0;
        const attendedDays = parseFloat(document.getElementById('attended_days').value) || 0;
        const otHours = parseFloat(document.getElementById('overtime_hours').value) || 0;
        const otRate = parseFloat(document.getElementById('overtime_rate').value) || 0;

        // Prorated Salary
        let proratedSalary = contractSalary;
        if (requiredDays > 0) {
            proratedSalary = (attendedDays / requiredDays) * contractSalary;
        } else {
            proratedSalary = 0;
        }
        document.getElementById('prorated_salary').value = proratedSalary.toFixed(2);

        // OT Amount
        const otAmount = otHours * otRate;
        document.getElementById('overtime_amount').value = otAmount.toFixed(2);

        recalculateTotal();
    }

    function recalculateTotal() {
        const proratedSalary = parseFloat(document.getElementById('prorated_salary').value) || 0;
        const overtimeAmount = parseFloat(document.getElementById('overtime_amount').value) || 0;

        // Sum additions
        let allowanceSum = 0;
        document.querySelectorAll('.addition-amount').forEach(el => {
            allowanceSum += parseFloat(el.value) || 0;
        });

        // Sum deductions
        let deductionSum = 0;
        document.querySelectorAll('.deduction-amount').forEach(el => {
            deductionSum += parseFloat(el.value) || 0;
        });

        // Calculate Net
        const netSalary = proratedSalary + overtimeAmount + allowanceSum - deductionSum;
        document.getElementById('net_salary_display').innerText = netSalary.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Run calculation once on load to populate net salary
    document.addEventListener('DOMContentLoaded', () => {
        recalculateTotal();
    });
</script>
@endsection
