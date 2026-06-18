@extends('layouts.app')

@section('title', 'Generate Salary Slip')

@section('content')
<div class="space-y-6 max-w-3xl mx-auto">

    <!-- Header navigation -->
    <div class="flex items-center gap-3 border-b border-slate-800 pb-4">
        <a href="{{ route('payroll.index', ['year' => $year, 'month' => $month]) }}" class="text-sm font-semibold text-indigo-400 hover:text-indigo-300">
            &larr; Back to Payroll
        </a>
        <span class="text-slate-600">|</span>
        <span class="text-slate-350 font-semibold text-sm capitalize">New Payslip for {{ $user->name }}</span>
    </div>

    <!-- Generator Form -->
    <form action="{{ route('payroll.store') }}" method="POST" class="space-y-8">
        @csrf
        <input type="hidden" name="user_id" value="{{ $user->id }}">
        <input type="hidden" name="month" value="{{ $month }}">
        <input type="hidden" name="year" value="{{ $year }}">

        <!-- 1. Base Salary Details -->
        <div class="glass-card rounded-2xl p-6 grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
            <div>
                <label class="block text-xs text-slate-500 mb-1">Employee Name</label>
                <span class="font-bold text-slate-200 text-sm capitalize block">{{ $user->name }}</span>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Pay Period</label>
                <span class="font-semibold text-slate-200 text-sm block">
                    {{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}
                </span>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Basic Base Salary ($)</label>
                <input type="text" readonly value="${{ number_format($user->basic_salary, 2) }}"
                       class="w-full px-4 py-2 bg-slate-950 border border-slate-850 rounded-lg text-slate-400 font-mono text-sm focus:outline-none select-none">
            </div>
        </div>

        <!-- 2. Allowances & Additions -->
        <div class="glass-card rounded-2xl p-6 space-y-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800/80 pb-3">
                ➕ Additions / Allowances / Bonuses
            </h3>

            <div id="additions-container" class="space-y-3">
                @php $hasAdditions = false; @endphp
                @foreach($categories->where('type', 'addition') as $cat)
                    @php $hasAdditions = true; @endphp
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center addition-row">
                        <div class="md:col-span-3">
                            <input type="text" name="item_name[]" value="{{ $cat->name }}" readonly
                                   class="w-full px-4 py-2 bg-slate-950/45 border border-slate-850 rounded-lg text-slate-400 text-xs focus:outline-none">
                            <input type="hidden" name="item_type[]" value="addition">
                        </div>
                        <div>
                            <input type="number" step="0.01" name="item_amount[]" placeholder="0.00" value="{{ $cat->default_amount ?? '' }}"
                                   class="w-full px-4 py-2 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 font-mono text-xs focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>
                @endforeach
            </div>

            <button type="button" onclick="addRow('addition', 'additions-container')"
                    class="py-1.5 px-3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold rounded-lg text-xs transition border border-slate-750">
                + Add Custom Addition
            </button>
        </div>

        <!-- 3. Deductions Section -->
        <div class="glass-card rounded-2xl p-6 space-y-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800/80 pb-3">
                ➖ Deductions / EPF / Advances
            </h3>

            <div id="deductions-container" class="space-y-3">
                @php $hasDeductions = false; @endphp
                @foreach($categories->where('type', 'deduction') as $cat)
                    @php $hasDeductions = true; @endphp
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center deduction-row">
                        <div class="md:col-span-3">
                            <input type="text" name="item_name[]" value="{{ $cat->name }}" readonly
                                   class="w-full px-4 py-2 bg-slate-950/45 border border-slate-850 rounded-lg text-slate-400 text-xs focus:outline-none">
                            <input type="hidden" name="item_type[]" value="deduction">
                        </div>
                        <div>
                            <input type="number" step="0.01" name="item_amount[]" placeholder="0.00" value="{{ $cat->default_amount ?? '' }}"
                                   class="w-full px-4 py-2 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 font-mono text-xs focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>
                @endforeach
            </div>

            <button type="button" onclick="addRow('deduction', 'deductions-container')"
                    class="py-1.5 px-3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold rounded-lg text-xs transition border border-slate-750">
                + Add Custom Deduction
            </button>
        </div>

        <!-- Submit actions -->
        <div class="flex justify-end gap-3 pt-4 border-t border-slate-900">
            <a href="{{ route('payroll.index', ['year' => $year, 'month' => $month]) }}"
               class="py-2.5 px-4 bg-slate-850 hover:bg-slate-800 text-slate-300 font-semibold rounded-lg text-xs transition">
                Cancel
            </a>
            <button type="submit"
                    class="py-2.5 px-5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-lg text-xs transition">
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
                <input type="text" name="item_name[]" placeholder="e.g., Overtime Allowance, EPF Deduction" required
                       class="w-full px-4 py-2 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 text-xs focus:outline-none focus:border-indigo-500">
                <input type="hidden" name="item_type[]" value="${type}">
            </div>
            <div class="flex gap-2">
                <input type="number" step="0.01" name="item_amount[]" placeholder="0.00" required
                       class="flex-1 px-4 py-2 bg-slate-950 border border-slate-800 rounded-lg text-slate-200 font-mono text-xs focus:outline-none focus:border-indigo-500">
                <button type="button" onclick="this.closest('.${type}-row').remove()" class="text-red-500 hover:text-red-400 font-bold px-2 text-sm">✕</button>
            </div>
        `;
        container.appendChild(newRow);
    }
</script>
@endsection
