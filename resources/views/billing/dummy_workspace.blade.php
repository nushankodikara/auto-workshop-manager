@extends('layouts.app')

@section('title', 'Dummy Invoice Generator')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto pb-12">

    <!-- Header navigation -->
    <div class="flex items-center gap-3 border-b border-slate-200 dark:border-slate-800 pb-4">
        <a href="{{ route('tests.index') }}" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
            <span>Back to Tests</span>
        </a>
        <span class="text-slate-400">|</span>
        <span class="text-slate-650 dark:text-slate-350 font-semibold text-sm">Dummy Invoice Generator</span>
    </div>

    <!-- Main Workspace Form -->
    <form action="{{ route('billing.dummy.generate') }}" method="POST" class="space-y-8" target="_blank">
        @csrf

        <!-- 1. Shop Details -->
        <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-2">
                <i data-lucide="map-pin" class="w-4 h-4 text-primary"></i>
                <span>Shop / Location Details</span>
            </h3>
            
            @php
                $defaultShop = $shops->first();
                $defaultShopName = $defaultShop ? $defaultShop->name : config('app.name', 'Total Drive Care');
                $defaultShopAddress = $defaultShop ? $defaultShop->address : 'Colombo, Sri Lanka';
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="shop_name" class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Shop Name</label>
                    <input type="text" name="shop_name" id="shop_name" value="{{ $defaultShopName }}" required
                           class="w-full px-3.5 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label for="shop_address" class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Shop Address</label>
                    <input type="text" name="shop_address" id="shop_address" value="{{ $defaultShopAddress }}" required
                           class="w-full px-3.5 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary">
                </div>
            </div>
        </div>

        <!-- 2. Customer & Vehicle Details -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Billed To -->
            <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-2">
                    <i data-lucide="user" class="w-4 h-4 text-primary"></i>
                    <span>Customer Details (Billed To)</span>
                </h3>
                
                <div class="space-y-3">
                    <div>
                        <label for="customer_name" class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Client Name</label>
                        <input type="text" name="customer_name" id="customer_name" placeholder="e.g., John Doe" required
                               class="w-full px-3.5 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary">
                    </div>
                    <div>
                        <label for="customer_phone" class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Phone Number</label>
                        <input type="text" name="customer_phone" id="customer_phone" placeholder="e.g., 0771234567" required
                               class="w-full px-3.5 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary">
                    </div>
                    <div>
                        <label for="customer_email" class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Email Address (Optional)</label>
                        <input type="email" name="customer_email" id="customer_email" placeholder="e.g., john@example.com"
                               class="w-full px-3.5 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary">
                    </div>
                    <div>
                        <label for="customer_address" class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Address (Optional)</label>
                        <input type="text" name="customer_address" id="customer_address" placeholder="e.g., Colombo, Sri Lanka"
                               class="w-full px-3.5 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary">
                    </div>
                </div>
            </div>

            <!-- Vehicle Details -->
            <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-2">
                    <i data-lucide="car" class="w-4 h-4 text-primary"></i>
                    <span>Vehicle Details</span>
                </h3>
                
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="vehicle_make" class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Make</label>
                            <input type="text" name="vehicle_make" id="vehicle_make" placeholder="e.g., Toyota" required
                                   class="w-full px-3.5 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label for="vehicle_model" class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Model</label>
                            <input type="text" name="vehicle_model" id="vehicle_model" placeholder="e.g., Corolla" required
                                   class="w-full px-3.5 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="vehicle_year" class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Year</label>
                            <input type="number" name="vehicle_year" id="vehicle_year" value="2020" min="1900" max="2100" required
                                   class="w-full px-3.5 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label for="vehicle_plate" class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Plate Number</label>
                            <input type="text" name="vehicle_plate" id="vehicle_plate" placeholder="e.g., WP CAB-1234" required
                                   class="w-full px-3.5 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary">
                        </div>
                    </div>
                    <div>
                        <label for="vehicle_vin" class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">VIN / Chassis Number (Optional)</label>
                        <input type="text" name="vehicle_vin" id="vehicle_vin" placeholder="e.g., 17-digit VIN"
                               class="w-full px-3.5 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary">
                    </div>
                    <div>
                        <label for="vehicle_mileage" class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Odometer Mileage (Optional)</label>
                        <input type="number" name="vehicle_mileage" id="vehicle_mileage" placeholder="e.g., 65000" min="0"
                               class="w-full px-3.5 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary">
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Invoice Metadata -->
        <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-2">
                <i data-lucide="file-text" class="w-4 h-4 text-primary"></i>
                <span>Invoice Information</span>
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="invoice_number" class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Invoice Number</label>
                    <input type="text" name="invoice_number" id="invoice_number" value="INV-{{ date('Ymd') }}-DUMMY" required
                           class="w-full px-3.5 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary font-mono">
                </div>
                <div>
                    <label for="invoice_date" class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Invoice Date</label>
                    <input type="date" name="invoice_date" id="invoice_date" value="{{ date('Y-m-d') }}" required
                           class="w-full px-3.5 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary">
                </div>
                <div>
                    <label for="invoice_status" class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Invoice Status</label>
                    <select name="invoice_status" id="invoice_status" required
                            class="w-full px-3.5 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary cursor-pointer">
                        <option value="paid" selected>Paid</option>
                        <option value="draft">Draft / Unpaid</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- 4. Line Items Table -->
        <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-2">
                    <i data-lucide="list" class="w-4 h-4 text-primary"></i>
                    <span>Invoice Line Items</span>
                </h3>
                <button type="button" onclick="addLineItem()"
                        class="py-1 px-3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg text-xs transition flex items-center gap-1 cursor-pointer">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Add Item</span>
                </button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="text-slate-500 dark:text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-855 pb-2">
                            <th class="py-2 px-2 w-[45%]">Description</th>
                            <th class="py-2 px-2 w-[20%]">Type</th>
                            <th class="py-2 px-2 text-right w-[12%]">Quantity</th>
                            <th class="py-2 px-2 text-right w-[15%]">Unit Price (Rs.)</th>
                            <th class="py-2 px-2 text-center w-[8%]">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="line-items-tbody" class="divide-y divide-slate-100 dark:divide-slate-850/60">
                        <!-- Initial default item row -->
                        <tr class="text-slate-750 dark:text-slate-300 line-item-row">
                            <td class="py-3 px-2">
                                <input type="text" name="item_desc[]" required placeholder="e.g., Replacement Spark Plug"
                                       class="w-full px-2 py-1.5 app-input rounded text-xs focus:outline-none focus:border-primary">
                            </td>
                            <td class="py-3 px-2">
                                <select name="item_type[]" required
                                        class="w-full px-2 py-1.5 app-input rounded text-xs focus:outline-none focus:border-primary cursor-pointer">
                                    <option value="part" selected>Part / Material</option>
                                    <option value="labor">Labor / Service</option>
                                    <option value="outsourcing">Outsourcing</option>
                                </select>
                            </td>
                            <td class="py-3 px-2">
                                <input type="number" step="0.01" name="item_qty[]" value="1.00" required
                                       class="w-full px-2 py-1.5 app-input rounded text-xs focus:outline-none focus:border-primary text-right font-mono">
                            </td>
                            <td class="py-3 px-2">
                                <input type="number" step="0.01" name="item_price[]" value="0.00" required
                                       class="w-full px-2 py-1.5 app-input rounded text-xs focus:outline-none focus:border-primary text-right font-mono">
                            </td>
                            <td class="py-3 px-2 text-center">
                                <button type="button" onclick="removeRow(this)"
                                        class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-950/20 rounded transition cursor-pointer">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 5. Discounts, Tax, Towing & Advanced Payments -->
        <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-2">
                <i data-lucide="percent" class="w-4 h-4 text-primary"></i>
                <span>Taxes, Discounts & Deductions</span>
            </h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label for="discount_percent" class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Discount Percent (%)</label>
                    <input type="number" step="0.01" name="discount_percent" id="discount_percent" value="0.00" min="0" max="100" required
                           class="w-full px-3.5 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary font-mono">
                </div>
                <div>
                    <label for="tax_percent" class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Tax Percent (%)</label>
                    <input type="number" step="0.01" name="tax_percent" id="tax_percent" value="0.00" min="0" required
                           class="w-full px-3.5 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary font-mono">
                </div>
                <div>
                    <label for="towing_fee" class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Towing & Transport Fee (Rs.)</label>
                    <input type="number" step="0.01" name="towing_fee" id="towing_fee" value="0.00" min="0" required
                           class="w-full px-3.5 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary font-mono">
                </div>
                <div>
                    <label for="advanced_payments" class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Advanced Payments (Rs.)</label>
                    <input type="number" step="0.01" name="advanced_payments" id="advanced_payments" value="0.00" min="0" required
                           class="w-full px-3.5 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-xs focus:outline-none focus:border-primary font-mono">
                </div>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
            <a href="{{ route('tests.index') }}"
               class="px-4 py-2 border border-slate-350 dark:border-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-bold transition hover:bg-slate-50 dark:hover:bg-slate-850">
                Cancel
            </a>
            <button type="submit"
                    class="px-5 py-2.5 bg-primary hover:bg-primary-hover text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm shadow-primary/20 cursor-pointer">
                <i data-lucide="eye" class="w-4 h-4"></i>
                <span>Generate Preview & Print</span>
            </button>
        </div>

    </form>

</div>

<script>
    function addLineItem() {
        const tbody = document.getElementById('line-items-tbody');
        const tr = document.createElement('tr');
        tr.className = 'text-slate-750 dark:text-slate-300 line-item-row';
        tr.innerHTML = `
            <td class="py-3 px-2">
                <input type="text" name="item_desc[]" required placeholder="e.g., Service / Item Description"
                       class="w-full px-2 py-1.5 app-input rounded text-xs focus:outline-none focus:border-primary">
            </td>
            <td class="py-3 px-2">
                <select name="item_type[]" required
                        class="w-full px-2 py-1.5 app-input rounded text-xs focus:outline-none focus:border-primary cursor-pointer">
                    <option value="part" selected>Part / Material</option>
                    <option value="labor">Labor / Service</option>
                    <option value="outsourcing">Outsourcing</option>
                </select>
            </td>
            <td class="py-3 px-2">
                <input type="number" step="0.01" name="item_qty[]" value="1.00" required
                       class="w-full px-2 py-1.5 app-input rounded text-xs focus:outline-none focus:border-primary text-right font-mono">
            </td>
            <td class="py-3 px-2">
                <input type="number" step="0.01" name="item_price[]" value="0.00" required
                       class="w-full px-2 py-1.5 app-input rounded text-xs focus:outline-none focus:border-primary text-right font-mono">
            </td>
            <td class="py-3 px-2 text-center">
                <button type="button" onclick="removeRow(this)"
                        class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-950/20 rounded transition cursor-pointer">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
        // Refresh Lucide Icons for the newly added row
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    function removeRow(btn) {
        const rows = document.querySelectorAll('.line-item-row');
        if (rows.length > 1) {
            btn.closest('tr').remove();
        } else {
            alert('At least one line item is required for the invoice.');
        }
    }
</script>
@endsection
