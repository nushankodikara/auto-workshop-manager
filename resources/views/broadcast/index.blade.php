@extends('layouts.app')

@section('title', 'Broadcast Messages')

@section('content')
<div class="space-y-6">

    <!-- Header Actions -->
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
        <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100 flex items-center gap-2">
            <i data-lucide="megaphone" class="w-6 h-6 text-primary"></i>
            <span>Broadcast Messages</span>
        </h2>
    </div>

    <!-- Segment Switcher Tabs -->
    <div class="flex border-b border-slate-200 dark:border-slate-800 pb-px">
        <a href="{{ route('broadcast.index', ['target' => 'customers']) }}" 
           class="px-4 py-2 font-semibold text-sm border-b-2 transition {{ $target === 'customers' ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-350' }}">
            Customer Broadcast
        </a>
        <a href="{{ route('broadcast.index', ['target' => 'employees']) }}" 
           class="px-4 py-2 font-semibold text-sm border-b-2 transition {{ $target === 'employees' ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-350' }}">
            Employee Broadcast
        </a>
    </div>

    <!-- Filters and Sending Section -->
    <div class="grid grid-cols-1 {{ $target === 'customers' ? 'lg:grid-cols-3' : '' }} gap-6">

        @if($target === 'customers')
            <!-- Left Column: Filters (Only for Customers) -->
            <div class="space-y-6 lg:col-span-1">
                <div class="app-card rounded-2xl p-6 space-y-5 shadow-xs">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-2">
                        <i data-lucide="filter" class="w-4 h-4 text-primary"></i>
                        <span>Select Target Customers</span>
                    </h3>

                    <form id="filter-form" action="{{ route('broadcast.index') }}" method="GET" class="space-y-4">
                        <input type="hidden" name="target" value="customers">
                        <!-- Timeframe -->
                        <div>
                            <label for="timeframe" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Last Service Date</label>
                            <select name="timeframe" id="timeframe" class="w-full px-3 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 text-sm focus:outline-none focus:border-primary cursor-pointer">
                                <option value="all" {{ $timeframe === 'all' ? 'selected' : '' }}>All Customers</option>
                                <option value="last_week" {{ $timeframe === 'last_week' ? 'selected' : '' }}>Last Week (7 Days)</option>
                                <option value="last_month" {{ $timeframe === 'last_month' ? 'selected' : '' }}>Last Month (30 Days)</option>
                                <option value="last_3_months" {{ $timeframe === 'last_3_months' ? 'selected' : '' }}>Last 3 Months (90 Days)</option>
                                <option value="last_6_months" {{ $timeframe === 'last_6_months' ? 'selected' : '' }}>Last 6 Months (180 Days)</option>
                                <option value="custom" {{ $timeframe === 'custom' ? 'selected' : '' }}>Custom Date Range</option>
                            </select>
                        </div>

                        <!-- Custom Dates Range -->
                        <div id="custom-dates-range" class="space-y-4 {{ $timeframe === 'custom' ? '' : 'hidden' }}">
                            <div>
                                <label for="start_date" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">From Date</label>
                                <input type="date" name="start_date" id="start_date" value="{{ $startDate }}"
                                       class="w-full px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-sm focus:outline-none focus:border-primary">
                            </div>
                            <div>
                                <label for="end_date" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">To Date</label>
                                <input type="date" name="end_date" id="end_date" value="{{ $endDate }}"
                                       class="w-full px-4 py-2 app-input rounded-lg text-slate-900 dark:text-slate-200 text-sm focus:outline-none focus:border-primary">
                            </div>
                            <button type="submit" class="w-full py-2.5 px-4 bg-primary hover:bg-primary-hover text-white font-medium rounded-lg text-sm transition shadow-sm cursor-pointer flex items-center justify-center gap-1.5">
                                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                                <span>Apply Custom Filter</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <!-- Right Column: Recipient Selection & Compose Message -->
        <div class="{{ $target === 'customers' ? 'lg:col-span-2' : 'w-full' }} space-y-6">
            <form action="{{ route('broadcast.send') }}" method="POST" id="broadcast-form" class="space-y-6">
                @csrf
                <input type="hidden" name="target" value="{{ $target }}">

                <!-- Recipient Selection List -->
                <div class="app-card rounded-2xl overflow-hidden shadow-xs">
                    <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-955/20">
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Recipient Selection</h3>
                            @if($target === 'customers')
                                <p class="text-xs text-slate-500 mt-1" id="selected-count">0 of {{ count($clients) }} selected</p>
                            @else
                                <p class="text-xs text-slate-500 mt-1" id="selected-count">0 of {{ count($employees) }} selected</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="select-all" class="w-4 h-4 text-primary focus:ring-primary border-slate-300 dark:border-slate-700 rounded cursor-pointer">
                            <label for="select-all" class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider cursor-pointer">Select All Eligible</label>
                        </div>
                    </div>

                    <div class="max-h-[350px] overflow-y-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            @if($target === 'customers')
                                <thead>
                                    <tr class="bg-slate-100/40 dark:bg-slate-900/40 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase text-[10px] tracking-wider sticky top-0 bg-white dark:bg-slate-950 z-10">
                                        <th class="py-3 px-6 w-12"></th>
                                        <th class="py-3 px-6">Customer</th>
                                        <th class="py-3 px-6">Phone</th>
                                        <th class="py-3 px-6">Email</th>
                                        <th class="py-3 px-6 text-right">Last Service</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-850/60" id="recipients-tbody">
                                    @forelse($clients as $client)
                                        <tr class="transition text-slate-750 dark:text-slate-300 recipient-row hover:bg-slate-100/50 dark:hover:bg-slate-900/30">
                                            <td class="py-3.5 px-6">
                                                <input type="checkbox" name="recipients[]" value="{{ $client->id }}"
                                                       data-has-phone="{{ !empty($client->phone) ? 'true' : 'false' }}"
                                                       data-has-email="{{ !empty($client->email) ? 'true' : 'false' }}"
                                                       class="recipient-checkbox w-4 h-4 text-primary focus:ring-primary border-slate-300 dark:border-slate-700 rounded cursor-pointer">
                                            </td>
                                            <td class="py-3.5 px-6 font-semibold text-slate-850 dark:text-slate-200">
                                                {{ $client->name }}
                                            </td>
                                            <td class="py-3.5 px-6 text-xs font-mono">
                                                @if(!empty($client->phone))
                                                    {{ $client->phone }}
                                                @else
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-red-500/10 text-red-500 border border-red-500/20">No Phone</span>
                                                @endif
                                            </td>
                                            <td class="py-3.5 px-6 text-xs">
                                                @if(!empty($client->email))
                                                    {{ $client->email }}
                                                @else
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-red-500/10 text-red-500 border border-red-500/20">No Email</span>
                                                @endif
                                            </td>
                                            <td class="py-3.5 px-6 text-right text-xs font-mono">
                                                {{ $client->last_service_date ? \Carbon\Carbon::parse($client->last_service_date)->format('Y-m-d') : 'Never' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="py-12 text-center text-slate-500">
                                                No customers found matching the selected timeframe.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            @else
                                <thead>
                                    <tr class="bg-slate-100/40 dark:bg-slate-900/40 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase text-[10px] tracking-wider sticky top-0 bg-white dark:bg-slate-950 z-10">
                                        <th class="py-3 px-6 w-12"></th>
                                        <th class="py-3 px-6">Employee</th>
                                        <th class="py-3 px-6">Contact Number</th>
                                        <th class="py-3 px-6">Email</th>
                                        <th class="py-3 px-6 text-right">System Role</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-850/60" id="recipients-tbody">
                                    @forelse($employees as $emp)
                                        <tr class="transition text-slate-750 dark:text-slate-300 recipient-row hover:bg-slate-100/50 dark:hover:bg-slate-900/30">
                                            <td class="py-3.5 px-6">
                                                <input type="checkbox" name="recipients[]" value="{{ $emp->id }}"
                                                       data-has-phone="{{ !empty($emp->contact_number) ? 'true' : 'false' }}"
                                                       data-has-email="{{ !empty($emp->email) ? 'true' : 'false' }}"
                                                       class="recipient-checkbox w-4 h-4 text-primary focus:ring-primary border-slate-300 dark:border-slate-700 rounded cursor-pointer">
                                            </td>
                                            <td class="py-3.5 px-6 font-semibold text-slate-850 dark:text-slate-200">
                                                {{ $emp->name }}
                                            </td>
                                            <td class="py-3.5 px-6 text-xs font-mono">
                                                @if(!empty($emp->contact_number))
                                                    {{ $emp->contact_number }}
                                                @else
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-red-500/10 text-red-500 border border-red-500/20">No Mobile</span>
                                                @endif
                                            </td>
                                            <td class="py-3.5 px-6 text-xs">
                                                @if(!empty($emp->email))
                                                    {{ $emp->email }}
                                                @else
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-red-500/10 text-red-500 border border-red-500/20">No Email</span>
                                                @endif
                                            </td>
                                            <td class="py-3.5 px-6 text-right text-xs">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-650 dark:text-slate-400 border border-slate-200 dark:border-slate-705 capitalize">
                                                    {{ $emp->role }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="py-12 text-center text-slate-500">
                                                No active employees found in directory.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            @endif
                        </table>
                    </div>
                </div>

                <!-- Message Composition -->
                <div class="app-card rounded-2xl p-6 space-y-5 shadow-xs">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-2">
                        <i data-lucide="edit-3" class="w-4 h-4 text-primary"></i>
                        <span>Compose Message</span>
                    </h3>

                    <!-- Type / Pathway -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="type" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Delivery Method</label>
                            <select name="type" id="type" class="w-full px-3 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 text-sm focus:outline-none focus:border-primary cursor-pointer">
                                <option value="sms">SMS (FitSMS Pathway)</option>
                                <option value="email">Email (SMTP Pathway)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Email Subject (only visible for Email type) -->
                    <div id="subject-wrapper" class="hidden">
                        <label for="subject" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Email Subject</label>
                        <input type="text" name="subject" id="subject" placeholder="e.g., Important Staff Notification"
                               class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 text-sm focus:outline-none focus:border-primary">
                    </div>

                    <!-- Message Body -->
                    <div>
                        <label for="message" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Message Body</label>
                        <textarea name="message" id="message" rows="6" required placeholder="Write your announcement here..."
                                  class="w-full px-4 py-3 app-input rounded-lg text-slate-900 dark:text-slate-200 text-sm focus:outline-none focus:border-primary"></textarea>
                    </div>

                    <!-- Action Button -->
                    <div class="flex justify-end pt-2">
                        <button type="submit" id="send-button" disabled
                                class="px-6 py-3 bg-primary hover:bg-primary-hover disabled:bg-slate-300 dark:disabled:bg-slate-800 disabled:text-slate-500 dark:disabled:text-slate-650 disabled:cursor-not-allowed text-white font-medium rounded-lg text-sm transition flex items-center gap-2 shadow-sm cursor-pointer">
                            <i data-lucide="send" class="w-4 h-4"></i>
                            <span>Send Broadcast Campaign</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const timeframeSelect = document.getElementById('timeframe');
        const customDatesDiv = document.getElementById('custom-dates-range');
        const methodSelect = document.getElementById('type');
        const subjectWrapper = document.getElementById('subject-wrapper');
        const subjectInput = document.getElementById('subject');
        const selectAllCheckbox = document.getElementById('select-all');
        const recipientCheckboxes = document.querySelectorAll('.recipient-checkbox');
        const selectedCountEl = document.getElementById('selected-count');
        const sendButton = document.getElementById('send-button');
        const broadcastForm = document.getElementById('broadcast-form');

        // Toggle custom dates view and submit filter form automatically (Customers only)
        if (timeframeSelect) {
            timeframeSelect.addEventListener('change', function() {
                if (timeframeSelect.value === 'custom') {
                    customDatesDiv.classList.remove('hidden');
                } else {
                    customDatesDiv.classList.add('hidden');
                    document.getElementById('filter-form').submit();
                }
            });
        }

        // Toggle subject line display and validate fields based on selected method
        function updateMethodFormFields() {
            const method = methodSelect.value;
            if (method === 'email') {
                subjectWrapper.classList.remove('hidden');
                subjectInput.setAttribute('required', 'required');
            } else {
                subjectWrapper.classList.add('hidden');
                subjectInput.removeAttribute('required');
                subjectInput.value = '';
            }
            updateCheckboxesEligibility();
        }

        // Adjust eligibility based on contact info details
        function updateCheckboxesEligibility() {
            const method = methodSelect.value;
            let selectableCount = 0;
            let checkedCount = 0;

            recipientCheckboxes.forEach(cb => {
                const hasPhone = cb.getAttribute('data-has-phone') === 'true';
                const hasEmail = cb.getAttribute('data-has-email') === 'true';
                
                const isEligible = (method === 'sms' && hasPhone) || (method === 'email' && hasEmail);
                cb.disabled = !isEligible;
                
                const row = cb.closest('tr');
                if (!isEligible) {
                    cb.checked = false;
                    row.classList.add('opacity-40');
                    row.classList.remove('hover:bg-slate-100/50', 'dark:hover:bg-slate-900/30');
                } else {
                    row.classList.remove('opacity-40');
                    row.classList.add('hover:bg-slate-100/50', 'dark:hover:bg-slate-900/30');
                    selectableCount++;
                    if (cb.checked) {
                        checkedCount++;
                    }
                }
            });

            // Update Select All Checkbox status
            if (selectableCount > 0 && checkedCount === selectableCount) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else if (checkedCount > 0 && checkedCount < selectableCount) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            }

            // Disable select all checkbox if no client is eligible
            selectAllCheckbox.disabled = (selectableCount === 0);

            // Update Counter
            selectedCountEl.textContent = `${checkedCount} of ${recipientCheckboxes.length} eligible selected`;

            // Enable/disable send button
            sendButton.disabled = (checkedCount === 0);
        }

        // Event listener for individual checkboxes
        recipientCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateCheckboxesEligibility);
        });

        // Event listener for select all
        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = selectAllCheckbox.checked;
            recipientCheckboxes.forEach(cb => {
                if (!cb.disabled) {
                    cb.checked = isChecked;
                }
            });
            updateCheckboxesEligibility();
        });

        methodSelect.addEventListener('change', updateMethodFormFields);

        // Run once on load
        updateMethodFormFields();

        // Confirmation before sending campaign
        broadcastForm.addEventListener('submit', function(e) {
            const count = Array.from(recipientCheckboxes).filter(cb => cb.checked).length;
            const method = methodSelect.value.toUpperCase();
            const targetLabel = "{{ $target === 'customers' ? 'customers' : 'employees' }}";
            if (!confirm(`Are you sure you want to send this broadcast message to ${count} ${targetLabel} via ${method}?`)) {
                e.preventDefault();
            }
        });
    });
</script>
@endsection
