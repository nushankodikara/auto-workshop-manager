@extends('layouts.app')

@section('title', 'Draft Service Quotation')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header navigation -->
    <div class="flex items-center gap-3 border-b border-slate-200 dark:border-slate-800 pb-4">
        <a href="{{ route('quotations.index') }}" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            <span>Back to Workspace</span>
        </a>
        <span class="text-slate-400">|</span>
        <span class="text-slate-650 dark:text-slate-350 font-semibold text-sm">New Quotation</span>
    </div>

    <form action="{{ route('quotations.store') }}" method="POST" id="quotation-form" class="space-y-6">
        @csrf

        <!-- 1. Customer Information Card -->
        <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-2">
                <i data-lucide="user" class="w-4 h-4 text-primary"></i>
                <span>Customer Information</span>
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="customer_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Customer Name <span class="text-red-500">*</span></label>
                    <input type="text" name="customer_name" id="customer_name" required placeholder="e.g. Shehan Silva"
                           value="{{ old('customer_name') }}"
                           class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                </div>

                <div>
                    <label for="customer_phone" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Phone Number</label>
                    <input type="text" name="customer_phone" id="customer_phone" placeholder="e.g. 0711144444"
                           value="{{ old('customer_phone') }}"
                           class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                </div>

                <div>
                    <label for="customer_email" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Email Address</label>
                    <input type="email" name="customer_email" id="customer_email" placeholder="e.g. customer@example.com"
                           value="{{ old('customer_email') }}"
                           class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                </div>

                <div>
                    <label for="customer_address" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Billing Address</label>
                    <input type="text" name="customer_address" id="customer_address" placeholder="e.g. 123 Galle Road, Colombo"
                           value="{{ old('customer_address') }}"
                           class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                </div>
            </div>
        </div>

        <!-- 2. Line Items Table -->
        <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-2">
                    <i data-lucide="list" class="w-4 h-4 text-primary"></i>
                    <span>Service & Parts Line Items</span>
                </h3>
                
                <div class="flex gap-2">
                    <button type="button" onclick="addCustomRow('part')"
                            class="py-1 px-2.5 bg-sky-500/10 hover:bg-sky-500/20 text-sky-600 dark:text-sky-400 font-semibold rounded-lg text-xs transition flex items-center gap-1 cursor-pointer">
                        <i data-lucide="plus" class="w-3 h-3"></i>
                        <span>+ Add Part</span>
                    </button>
                    <button type="button" onclick="addCustomRow('labor')"
                            class="py-1 px-2.5 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-650 dark:text-emerald-400 font-semibold rounded-lg text-xs transition flex items-center gap-1 cursor-pointer">
                        <i data-lucide="plus" class="w-3 h-3"></i>
                        <span>+ Add Labor</span>
                    </button>
                    <button type="button" onclick="addCustomRow('outsourcing')"
                            class="py-1 px-2.5 bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 dark:text-amber-400 font-semibold rounded-lg text-xs transition flex items-center gap-1 cursor-pointer">
                        <i data-lucide="plus" class="w-3 h-3"></i>
                        <span>+ Add Outsourced</span>
                    </button>
                </div>
            </div>

            <!-- Items container -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs" id="items-table">
                    <thead>
                        <tr class="text-slate-550 dark:text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800 pb-2">
                            <th class="py-2 pr-4 w-1/2">Description / Part or Service Name</th>
                            <th class="py-2 px-2 w-1/6">Category</th>
                            <th class="py-2 px-2 text-right w-1/12">Qty</th>
                            <th class="py-2 px-2 text-right w-1/8">Unit Price</th>
                            <th class="py-2 px-2 text-right w-1/8">Total</th>
                            <th class="py-2 pl-4 text-right w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="items-container" class="divide-y divide-slate-100 dark:divide-slate-850">
                        <!-- Rows dynamically appended here -->
                    </tbody>
                </table>
            </div>

            <div id="no-items-warning" class="text-center py-6 text-slate-400 text-xs">
                No items added yet. Click one of the buttons above to append a row.
            </div>
        </div>

        <!-- 3. Calculations Summary -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Left Side: Notes / Parameters -->
            <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-2">
                    <i data-lucide="percent" class="w-4 h-4 text-primary"></i>
                    <span>Taxes & Discounts</span>
                </h3>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="discount_percent" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Discount (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="discount_percent" id="discount_percent" value="0.00" oninput="calculateTotals()"
                               class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                    </div>
                    <div>
                        <label for="tax" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Tax (%)</label>
                        <input type="number" step="0.01" min="0" name="tax" id="tax" value="0.00" oninput="calculateTotals()"
                               class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                    </div>
                </div>
            </div>

            <!-- Right Side: Calculations -->
            <div class="app-card rounded-2xl p-6 space-y-3 shadow-xs">
                <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400">
                    <span>Subtotal:</span>
                    <span class="font-mono font-semibold" id="label-subtotal">{{ config('app.currency', 'Rs.') }}0.00</span>
                </div>
                <div class="flex justify-between text-xs text-red-500">
                    <span>Discount Amount:</span>
                    <span class="font-mono font-semibold" id="label-discount">-{{ config('app.currency', 'Rs.') }}0.00</span>
                </div>
                <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400">
                    <span>Tax Amount:</span>
                    <span class="font-mono font-semibold" id="label-tax">{{ config('app.currency', 'Rs.') }}0.00</span>
                </div>
                <div class="flex justify-between text-sm font-bold border-t border-slate-200 dark:border-slate-800 pt-2 text-slate-800 dark:text-slate-100">
                    <span>Grand Total:</span>
                    <span class="font-mono text-primary" id="label-total">{{ config('app.currency', 'Rs.') }}0.00</span>
                </div>
            </div>
        </div>

        <!-- Submission -->
        <div class="flex justify-end gap-3">
            <a href="{{ route('quotations.index') }}" class="px-5 py-2.5 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-lg text-xs font-bold transition flex items-center justify-center">
                Cancel
            </a>
            <button type="submit" class="px-5 py-2.5 bg-primary hover:bg-primary-hover text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm border-0 cursor-pointer">
                <i data-lucide="check" class="w-4 h-4"></i>
                <span>Save & View Quotation</span>
            </button>
        </div>

    </form>
</div>

<!-- Simple Vanilla Javascript for Dynamic Rows & Totals -->
<script>
    let rowIndex = 0;

    // Helper to format currency
    function formatCurrency(amount) {
        return '{{ config('app.currency', 'Rs.') }}' + amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Function to add a row dynamically
    function addCustomRow(type = 'part', description = '', quantity = 1.00, price = 0.00) {
        document.getElementById('no-items-warning').classList.add('hidden');
        
        const container = document.getElementById('items-container');
        const row = document.createElement('tr');
        row.className = 'item-row text-slate-800 dark:text-slate-250';
        row.id = `row-${rowIndex}`;
        
        const typeOptions = `
            <option value="part" ${type === 'part' ? 'selected' : ''}>Part</option>
            <option value="labor" ${type === 'labor' ? 'selected' : ''}>Labor/Service</option>
            <option value="outsourcing" ${type === 'outsourcing' ? 'selected' : ''}>Outsourced</option>
            <option value="other" ${type === 'other' ? 'selected' : ''}>Other</option>
        `;

        row.innerHTML = `
            <td class="py-3 pr-4">
                <input type="text" name="items[${rowIndex}][description]" required value="${description}" placeholder="e.g. Brake Caliper Lubricant, Oil Filter Exchange"
                       class="w-full px-3 py-1.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs">
            </td>
            <td class="py-3 px-2">
                <select name="items[${rowIndex}][type]" required class="w-full px-2 py-1.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs cursor-pointer">
                    ${typeOptions}
                </select>
            </td>
            <td class="py-3 px-2 text-right">
                <input type="number" step="0.01" min="0.01" name="items[${rowIndex}][quantity]" required value="${quantity.toFixed(2)}" oninput="updateRowTotal(${rowIndex}); calculateTotals();"
                       class="w-full px-2 py-1.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono text-right">
            </td>
            <td class="py-3 px-2 text-right">
                <input type="number" step="0.01" min="0" name="items[${rowIndex}][unit_price]" required value="${price.toFixed(2)}" oninput="updateRowTotal(${rowIndex}); calculateTotals();"
                       class="w-full px-2 py-1.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono text-right">
            </td>
            <td class="py-3 px-2 text-right font-mono font-semibold" id="row-total-${rowIndex}">
                ${formatCurrency(quantity * price)}
            </td>
            <td class="py-3 pl-4 text-right">
                <button type="button" onclick="removeRow(${rowIndex})" class="text-red-500 hover:text-red-400 p-1.5">
                    ✕
                </button>
            </td>
        `;

        container.appendChild(row);
        rowIndex++;
        calculateTotals();
    }

    // Function to remove a row
    function removeRow(index) {
        const row = document.getElementById(`row-${index}`);
        if (row) {
            row.remove();
        }

        const container = document.getElementById('items-container');
        if (container.children.length === 0) {
            document.getElementById('no-items-warning').classList.remove('hidden');
        }

        calculateTotals();
    }

    // Update individual row total
    function updateRowTotal(index) {
        const row = document.getElementById(`row-${index}`);
        if (!row) return;
        
        const qty = parseFloat(row.querySelector(`input[name="items[${index}][quantity]"]`).value) || 0;
        const price = parseFloat(row.querySelector(`input[name="items[${index}][unit_price]"]`).value) || 0;
        const total = qty * price;
        
        document.getElementById(`row-total-${index}`).innerText = formatCurrency(total);
    }

    // Calculate totals across all rows
    function calculateTotals() {
        let subtotal = 0;
        
        const container = document.getElementById('items-container');
        const rows = container.getElementsByClassName('item-row');
        
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const match = row.id.match(/row-(\d+)/);
            if (!match) continue;
            
            const index = match[1];
            const qty = parseFloat(row.querySelector(`input[name="items[${index}][quantity]"]`).value) || 0;
            const price = parseFloat(row.querySelector(`input[name="items[${index}][unit_price]"]`).value) || 0;
            subtotal += qty * price;
        }

        const discountPercent = parseFloat(document.getElementById('discount_percent').value) || 0;
        const taxPercent = parseFloat(document.getElementById('tax').value) || 0;

        let discountAmount = 0;
        if (discountPercent > 0) {
            discountAmount = subtotal * (discountPercent / 100);
        }

        const subtotalAfterDiscount = subtotal - discountAmount;

        let taxAmount = 0;
        if (taxPercent > 0) {
            taxAmount = subtotalAfterDiscount * (taxPercent / 100);
        }

        const grandTotal = subtotalAfterDiscount + taxAmount;

        // Update labels
        document.getElementById('label-subtotal').innerText = formatCurrency(subtotal);
        document.getElementById('label-discount').innerText = '-' + formatCurrency(discountAmount);
        document.getElementById('label-tax').innerText = formatCurrency(taxAmount);
        document.getElementById('label-total').innerText = formatCurrency(grandTotal);
    }

    // Append 1 default row at launch
    window.addEventListener('DOMContentLoaded', () => {
        addCustomRow('part', '', 1, 0);
    });
</script>
@endsection
