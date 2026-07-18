@extends('layouts.app')

@section('title', 'System Settings & Backups')

@section('content')
<!-- Cropper.js CSS CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">

<div class="space-y-8 max-w-4xl mx-auto">

    <!-- Workshop Brand Logo Section -->
    <div class="app-card rounded-2xl p-6 space-y-6 shadow-xs">
        <div class="border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                    <i data-lucide="image" class="w-4 h-4 text-primary"></i>
                    <span>Workshop Brand Logo</span>
                </h3>
                <p class="text-xs text-slate-500 mt-1">Upload and crop a custom brand logo image (PNG/JPG) to show in the sidebar, login screen, and invoices.</p>
            </div>
            @if(file_exists(public_path('images/logo.png')))
                <form action="{{ route('settings.logo.delete') }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" onclick="return confirm('Are you sure you want to delete the custom logo?')" 
                            class="px-3 py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-650 dark:text-red-400 font-semibold rounded-lg text-xs transition flex items-center gap-1 cursor-pointer">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        <span>Delete Custom Logo</span>
                    </button>
                </form>
            @endif
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-8">
            <!-- Current Logo Preview -->
            <div class="flex flex-col items-center gap-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Current Logo</span>
                <div class="w-24 h-24 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 flex items-center justify-center p-3 shadow-inner">
                    @if(file_exists(public_path('images/logo.png')))
                        <img id="current-brand-logo" src="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" alt="Brand Logo" class="w-full h-full object-contain">
                    @else
                        <!-- Default SVG Logo -->
                        <svg class="w-12 h-12 text-primary" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M50 10 L85 30 L85 70 L50 90 L15 70 L15 30 Z" stroke="currentColor" stroke-width="6" stroke-linejoin="round" fill="currentColor" fill-opacity="0.05"/>
                            <circle cx="50" cy="50" r="18" stroke="currentColor" stroke-width="6"/>
                            <path d="M50 24 L50 32 M50 68 L50 76 M24 50 L32 50 M68 50 L76 50 M32 32 L38 38 M62 62 L68 68 M32 68 L38 62 M62 32 L68 38" stroke="currentColor" stroke-width="6" stroke-linecap="round"/>
                            <path d="M35 65 L65 35" stroke="currentColor" stroke-width="8" stroke-linecap="round"/>
                        </svg>
                    @endif
                </div>
                <span class="text-[10px] font-bold uppercase tracking-wider {{ file_exists(public_path('images/logo.png')) ? 'text-green-600 dark:text-green-400' : 'text-slate-400' }}">
                    {{ file_exists(public_path('images/logo.png')) ? 'Custom Logo' : 'Default SVG Logo' }}
                </span>
            </div>

            <!-- Upload input and Cropper loader -->
            <div class="flex-1 w-full">
                <div class="border-2 border-dashed border-slate-350 dark:border-slate-800 rounded-xl p-6 text-center hover:border-primary transition cursor-pointer relative" onclick="document.getElementById('logo-file-input').click()">
                    <input type="file" id="logo-file-input" accept="image/png, image/jpeg" class="hidden" onchange="loadCropper(this)">
                    <i data-lucide="upload-cloud" class="w-8 h-8 text-slate-400 mx-auto mb-2"></i>
                    <span class="text-xs font-semibold text-slate-650 dark:text-slate-300 block">Click to upload brand logo image</span>
                    <span class="text-[10px] text-slate-500 block mt-1">Supports PNG, JPG up to 2MB (recommended square 1:1 image)</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 1: UI Customization (Theme Accent Options) -->
    <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
        <div class="border-b border-slate-200 dark:border-slate-800 pb-3">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                <i data-lucide="palette" class="w-4 h-4 text-primary"></i>
                <span>UI Theme Accent Palette</span>
            </h3>
            <p class="text-xs text-slate-500 mt-1">Select a main corporate accent theme color for the buttons, links, and highlighted borders.</p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 pt-2">
            <!-- Accent Blue -->
            <button id="btn-accent-blue" onclick="selectAccent('blue')"
                    class="accent-btn flex flex-col items-center justify-center p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 transition hover:bg-slate-100 dark:hover:bg-slate-850 cursor-pointer">
                <span class="w-6 h-6 rounded-full bg-blue-600 block mb-2"></span>
                <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">Corporate Blue</span>
            </button>

            <!-- Accent Emerald -->
            <button id="btn-accent-emerald" onclick="selectAccent('emerald')"
                    class="accent-btn flex flex-col items-center justify-center p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 transition hover:bg-slate-100 dark:hover:bg-slate-850 cursor-pointer">
                <span class="w-6 h-6 rounded-full bg-emerald-600 block mb-2"></span>
                <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">Emerald Green</span>
            </button>

            <!-- Accent Indigo -->
            <button id="btn-accent-indigo" onclick="selectAccent('indigo')"
                    class="accent-btn flex flex-col items-center justify-center p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 transition hover:bg-slate-100 dark:hover:bg-slate-850 cursor-pointer">
                <span class="w-6 h-6 rounded-full bg-indigo-650 block mb-2"></span>
                <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">Royal Indigo</span>
            </button>

            <!-- Accent Red -->
            <button id="btn-accent-red" onclick="selectAccent('red')"
                    class="accent-btn flex flex-col items-center justify-center p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 transition hover:bg-slate-100 dark:hover:bg-slate-850 cursor-pointer">
                <span class="w-6 h-6 rounded-full bg-red-600 block mb-2"></span>
                <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">Crimson Red</span>
            </button>

            <!-- Accent Slate -->
            <button id="btn-accent-slate" onclick="selectAccent('slate')"
                    class="accent-btn flex flex-col items-center justify-center p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 transition hover:bg-slate-100 dark:hover:bg-slate-850 cursor-pointer">
                <span class="w-6 h-6 rounded-full bg-slate-500 block mb-2"></span>
                <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">Charcoal Slate</span>
            </button>
        </div>
    </div>

    <!-- Section 1.5: Job Card Number Settings -->
    <div class="app-card rounded-2xl p-6 space-y-4 shadow-xs">
        <div class="border-b border-slate-200 dark:border-slate-800 pb-3">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                <i data-lucide="hash" class="w-4 h-4 text-primary"></i>
                <span>Job Card Number Settings</span>
            </h3>
            <p class="text-xs text-slate-500 mt-1">Configure the prefix format for newly generated job card numbers (e.g., TDC-).</p>
        </div>

        <form action="{{ route('settings.update') }}" method="POST" class="space-y-4 pt-2">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="job_card_prefix" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Job Card Number Prefix</label>
                    <input type="text" name="job_card_prefix" id="job_card_prefix" required 
                           value="{{ \App\Models\Setting::get('job_card_prefix', 'TDC-') }}" 
                           placeholder="TDC-"
                           class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                </div>
                <div>
                    <label for="total_shares" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Total Company Shares (Pvt Ltd)</label>
                    <input type="number" name="total_shares" id="total_shares" required 
                           value="{{ \App\Models\Setting::get('total_shares', '100000') }}" 
                           placeholder="100000"
                           class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                </div>
            </div>

            <div class="border-t border-slate-200 dark:border-slate-800/80 pt-4 mt-6">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-1.5 mb-3">
                    <i data-lucide="cloud" class="w-4 h-4 text-primary"></i>
                    <span>S3 Cloud Backup Integration</span>
                </h4>
                
                <div class="flex items-center gap-2 mb-4">
                    <input type="checkbox" name="s3_enabled" id="s3_enabled" value="1"
                           {{ \App\Models\Setting::get('s3_enabled', '0') === '1' ? 'checked' : '' }}
                           class="rounded text-primary border-slate-350 focus:ring-primary w-4 h-4 cursor-pointer">
                    <label for="s3_enabled" class="text-xs font-semibold text-slate-655 dark:text-slate-300 cursor-pointer">Enable Automatic Cloud Backups to S3 bucket</label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4" id="s3-config-fields">
                    <div>
                        <label for="s3_key" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">AWS Access Key</label>
                        <input type="text" name="s3_key" id="s3_key" 
                               value="{{ \App\Models\Setting::get('s3_key', '') }}" 
                               placeholder="AKIAIOSFODNN7EXAMPLE"
                               class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                    </div>
                    <div>
                        <label for="s3_secret" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">AWS Secret Key</label>
                        <input type="password" name="s3_secret" id="s3_secret" 
                               value="{{ \App\Models\Setting::get('s3_secret', '') }}" 
                               placeholder="••••••••••••••••••••••••••••••••"
                               class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                    </div>
                    <div>
                        <label for="s3_bucket" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">S3 Bucket Name</label>
                        <input type="text" name="s3_bucket" id="s3_bucket" 
                               value="{{ \App\Models\Setting::get('s3_bucket', '') }}" 
                               placeholder="my-workshop-backups"
                               class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                    </div>
                    <div>
                        <label for="s3_region" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">AWS S3 Region</label>
                        <input type="text" name="s3_region" id="s3_region" 
                               value="{{ \App\Models\Setting::get('s3_region', 'us-east-1') }}" 
                               placeholder="us-east-1"
                               class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                    </div>
                    <div class="md:col-span-2">
                        <label for="s3_endpoint" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Custom Endpoint URL (Optional, for Cloudflare R2 / MinIO)</label>
                        <input type="text" name="s3_endpoint" id="s3_endpoint" 
                               value="{{ \App\Models\Setting::get('s3_endpoint', '') }}" 
                               placeholder="https://account-id.r2.cloudflarestorage.com"
                               class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-200 dark:border-slate-800/80 pt-4 mt-6">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-1.5 mb-1">
                    <i data-lucide="book-open" class="w-4 h-4 text-primary"></i>
                    <span>Operational Ledger Accounts Mapping</span>
                </h4>
                <p class="text-xs text-slate-505 dark:text-slate-400 mb-4">Map automated system transactions (like job card invoicing, payroll, purchases) to specific double-entry accounts.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @php
                        $mappingFields = [
                            ['name' => 'account_cashbook', 'label' => 'Cashbook Account', 'default' => '1000', 'desc' => 'Debit on payments, credit on expense payouts'],
                            ['name' => 'account_receivable', 'label' => 'Accounts Receivable', 'default' => '1200', 'desc' => 'Credit on payments, debit on draft billing'],
                            ['name' => 'account_inventory', 'label' => 'Parts Inventory Asset', 'default' => '1300', 'desc' => 'Debit on parts batch purchase inflow'],
                            ['name' => 'account_payable', 'label' => 'Accounts Payable Liability', 'default' => '2000', 'desc' => 'Credit on draft payroll slips'],
                            ['name' => 'account_service_revenue', 'label' => 'Service Revenue', 'default' => '4000', 'desc' => 'Credit on job card service revenue'],
                            ['name' => 'account_parts_revenue', 'label' => 'Parts Revenue', 'default' => '4105', 'desc' => 'Credit on job card parts inventory revenue'],
                            ['name' => 'account_cogs', 'label' => 'Cost of Goods Sold (COGS)', 'default' => '5000', 'desc' => 'Debit on parts stock consumption'],
                            ['name' => 'account_salaries', 'label' => 'Salaries Expense', 'default' => '5100', 'desc' => 'Debit on payroll slips processing'],
                            ['name' => 'account_consumables', 'label' => 'Tools & Consumables Expense', 'default' => '5400', 'desc' => 'Debit on consumable purchases'],
                        ];
                    @endphp

                    @foreach($mappingFields as $field)
                        <div>
                            <label for="{{ $field['name'] }}" class="block text-xs font-semibold uppercase tracking-wider text-slate-550 dark:text-slate-400 mb-1.5">
                                {{ $field['label'] }}
                            </label>
                            <select name="{{ $field['name'] }}" id="{{ $field['name'] }}" required
                                    class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs font-mono">
                                @php
                                    $currentVal = \App\Models\Setting::get($field['name'], $field['default']);
                                @endphp
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->code }}" {{ $currentVal == $acc->code ? 'selected' : '' }}>
                                        [{{ $acc->code }}] {{ $acc->name }} ({{ ucfirst($acc->type) }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-slate-500 dark:text-slate-500 mt-1 leading-normal font-medium">{{ $field['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <button type="submit" 
                        class="px-4 py-2.5 bg-primary hover:bg-primary-hover text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer border-0">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Save Settings</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Section 1.7: Shop Locations Management -->
    <div class="app-card rounded-2xl p-6 space-y-6 shadow-xs">
        <div class="border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                    <i data-lucide="map-pin" class="w-4 h-4 text-primary"></i>
                    <span>Shop Locations</span>
                </h3>
                <p class="text-xs text-slate-500 mt-1">Configure work bays and workshop locations for job card assignments.</p>
            </div>
        </div>

        @error('shop')
            <div class="px-4 py-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-650 dark:text-red-400 text-xs font-semibold animate-pulse">
                {{ $message }}
            </div>
        @enderror

        <!-- Add Shop Form -->
        <form action="{{ route('settings.shops.store') }}" method="POST" class="space-y-4 pt-2">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="shop_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Shop Name</label>
                    <input type="text" name="name" id="shop_name" required placeholder="e.g. Main Workshop (Bay 2)"
                           class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                </div>
                <div>
                    <label for="shop_address" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Address / Location Details</label>
                    <div class="flex gap-3">
                        <input type="text" name="address" id="shop_address" placeholder="e.g. 123 Engine Lane, Auto City"
                               class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                        <button type="submit" 
                                class="px-4 py-2.5 bg-primary hover:bg-primary-hover text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer whitespace-nowrap">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Add Shop</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Shops List -->
        <div class="space-y-4 pt-2">
            @if($shops->isEmpty())
                <div class="text-slate-500 text-sm py-8 text-center bg-slate-50 dark:bg-slate-950/20 rounded-xl border border-slate-200 dark:border-slate-800 border-dashed">
                    No shop locations configured. Please add at least one shop location.
                </div>
            @else
                <div class="app-card rounded-xl overflow-hidden shadow-xs">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-100/60 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                                <th class="py-3 px-5">Shop Name</th>
                                <th class="py-3 px-5">Address / Location Details</th>
                                <th class="py-3 px-5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-850/60">
                            @foreach($shops as $shop)
                                <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-900/20 transition">
                                    <td class="py-3 px-5 font-semibold text-slate-800 dark:text-slate-200">{{ $shop->name }}</td>
                                    <td class="py-3 px-5 text-slate-500">{{ $shop->address ?? 'N/A' }}</td>
                                    <td class="py-3 px-5 text-right">
                                        <form action="{{ route('settings.shops.delete', $shop) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    onclick="return confirm('Are you sure you want to delete this shop location?')"
                                                    class="px-2.5 py-1 bg-red-500/10 hover:bg-red-500/25 border border-red-500/20 text-red-650 dark:text-red-400 font-semibold rounded transition text-[10px] cursor-pointer">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Section 2: Backup Engine -->
    <div class="app-card rounded-2xl p-6 space-y-6 shadow-xs">
        <div class="flex flex-col md:flex-row justify-between md:items-center gap-4 border-b border-slate-200 dark:border-slate-800 pb-3">
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                    <i data-lucide="database" class="w-4 h-4 text-primary"></i>
                    <span>Database Backup & Recovery</span>
                </h3>
                <p class="text-xs text-slate-500 mt-1">Manage SQLite database file archives. Automatic cron backups run every hour.</p>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <!-- Upload Backup form -->
                <form action="{{ route('settings.backup.upload-restore') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                    @csrf
                    <label for="backup_file" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-850 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-xs border border-slate-200 dark:border-slate-800">
                        <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                        <span>Upload & Restore</span>
                    </label>
                    <input type="file" name="backup_file" id="backup_file" required accept=".sqlite" class="hidden" onchange="if(confirm('WARNING: Uploading this file will immediately restore it and completely overwrite the active database. Proceed?')) { this.form.submit(); }">
                </form>

                <!-- Trigger manual backup -->
                <form action="{{ route('settings.backup') }}" method="POST">
                    @csrf
                    <button type="submit" 
                            class="px-4 py-2 bg-primary hover:bg-primary-hover text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm border-0 cursor-pointer">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Run Manual Backup</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Backups List -->
        <div class="space-y-4">
            @if(empty($backups))
                <div class="text-slate-500 text-sm py-8 text-center bg-slate-50 dark:bg-slate-955/20 rounded-xl border border-slate-200 dark:border-slate-800 border-dashed">
                    No database backup files found inside the backups folder.
                </div>
            @else
                <div class="app-card rounded-xl overflow-hidden shadow-xs border border-slate-200 dark:border-slate-800">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-100/60 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                                <th class="py-3 px-5">Backup Filename</th>
                                <th class="py-3 px-5">File Size</th>
                                <th class="py-3 px-5">Created At</th>
                                <th class="py-3 px-5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-850/60">
                            @foreach($backups as $bkp)
                                <tr class="hover:bg-slate-100/30 dark:hover:bg-slate-900/20 transition">
                                    <td class="py-3 px-5 font-mono text-slate-800 dark:text-slate-200">{{ $bkp['name'] }}</td>
                                    <td class="py-3 px-5 text-slate-500">{{ $bkp['size'] }}</td>
                                    <td class="py-3 px-5 text-slate-500 font-mono">{{ $bkp['time'] }}</td>
                                    <td class="py-3 px-5 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <!-- Download Link -->
                                            <a href="{{ route('settings.backup.download', $bkp['name']) }}"
                                               class="px-2.5 py-1 bg-blue-500/10 hover:bg-blue-500/25 border border-blue-500/20 text-blue-600 dark:text-blue-400 font-semibold rounded transition text-[10px]">
                                                Download
                                            </a>
                                            
                                            <!-- Restore form -->
                                            <form action="{{ route('settings.restore') }}" method="POST" class="inline">
                                                @csrf
                                                <input type="hidden" name="filename" value="{{ $bkp['name'] }}">
                                                <button type="submit" 
                                                        onclick="return confirm('WARNING: Restoring will completely overwrite the active database. Proceed?')"
                                                        class="px-2.5 py-1 bg-green-500/10 hover:bg-green-500/25 border border-green-500/20 text-green-600 dark:text-green-400 font-semibold rounded transition text-[10px] cursor-pointer">
                                                    Restore Database
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Section 3: Branding details -->
    <div class="app-card rounded-2xl p-6 shadow-xs">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 border-b border-slate-200 dark:border-slate-800 pb-3 mb-4">
            Workshop Information
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="p-3 bg-slate-50 dark:bg-slate-950/40 rounded-lg border border-slate-200 dark:border-slate-800">
                <span class="text-slate-500 block font-semibold">Active Portal Branding</span>
                <span class="font-bold text-slate-700 dark:text-slate-200 mt-1 block">{{ config('app.name', 'Auto Workshop Manager') }}</span>
            </div>
            <div class="p-3 bg-slate-50 dark:bg-slate-950/40 rounded-lg border border-slate-200 dark:border-slate-800">
                <span class="text-slate-500 block font-semibold">Database Storage Driver</span>
                <span class="font-bold text-slate-700 dark:text-slate-200 mt-1 block uppercase">SQLite v3</span>
            </div>
        </div>
    </div>

    <!-- Section 4: Dynamic Role & Feature Access Management -->
    <div class="app-card rounded-2xl p-6 space-y-6 shadow-xs">
        <div class="border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                    <i data-lucide="shield-check" class="w-4 h-4 text-primary"></i>
                    <span>User Roles & Feature Access Control</span>
                </h3>
                <p class="text-xs text-slate-500 mt-1">Configure feature visibility and permissions for each system and custom role.</p>
            </div>
        </div>

        @error('role')
            <div class="px-4 py-3 rounded-lg bg-red-550/10 border border-red-500/20 text-red-650 dark:text-red-400 text-xs font-semibold animate-pulse">
                {{ $message }}
            </div>
        @enderror

        <!-- Roles Grid -->
        <div class="grid grid-cols-1 gap-6">
            @foreach($roles as $role)
                <div class="p-5 rounded-xl border border-slate-200 dark:border-slate-800/80 bg-slate-50/45 dark:bg-slate-900/30 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                                <span class="capitalize">{{ $role->label }}</span>
                                <span class="text-[9px] font-mono px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-550">{{ $role->name }}</span>
                            </h4>
                        </div>
                        @if($role->is_custom)
                            <form action="{{ route('settings.roles.destroy', $role) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Are you sure you want to delete this custom role?')" 
                                        class="px-2.5 py-1 bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 text-red-650 dark:text-red-450 font-bold rounded text-[10px] transition cursor-pointer">
                                    Delete Role
                                </button>
                            </form>
                        @endif
                    </div>

                    <!-- Permissions Checkboxes Form -->
                    <form action="{{ route('settings.roles.update', $role) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-2.5">Enabled Features / Access</label>
                            
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                                @foreach(\App\Models\Role::$modules as $modKey => $modLabel)
                                    @php
                                        $checked = in_array($modKey, $role->allowed_modules ?? []);
                                        if ($role->name === 'super-manager') {
                                            $checked = true;
                                        }
                                    @endphp
                                    <label class="flex items-start gap-2.5 p-2 rounded-lg border border-slate-200/60 dark:border-slate-800/40 bg-white dark:bg-slate-950/40 hover:bg-slate-100/50 dark:hover:bg-slate-900/40 cursor-pointer transition text-xs select-none">
                                        <input type="checkbox" name="allowed_modules[]" value="{{ $modKey }}" 
                                               {{ $checked ? 'checked' : '' }}
                                               {{ $role->name === 'super-manager' ? 'disabled' : '' }}
                                               class="h-4 w-4 text-primary rounded bg-white dark:bg-slate-905 border-slate-300 dark:border-slate-800 focus:ring-primary mt-0.5">
                                        <div>
                                            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $modLabel }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Update role fields -->
                        <div class="flex items-center gap-3 pt-2">
                            <div class="w-full max-w-xs">
                                <label class="block text-[9px] font-bold uppercase tracking-wider text-slate-500 mb-1">Role Display Name</label>
                                <input type="text" name="label" value="{{ $role->label }}" required
                                       class="w-full px-3 py-1.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-xs">
                            </div>
                            <button type="submit" 
                                    class="px-3.5 py-2 bg-primary hover:bg-primary-hover text-white rounded-lg text-xs font-bold transition flex items-center gap-1 mt-4 shadow-xs cursor-pointer">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                <span>Save Permissions</span>
                            </button>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>

        <!-- Add Custom Role Section -->
        <div class="border-t border-slate-200 dark:border-slate-800 pt-6">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 mb-3 flex items-center gap-1.5">
                <i data-lucide="plus" class="w-3.5 h-3.5 text-primary"></i>
                <span>Add Custom Role</span>
            </h4>

            <form action="{{ route('settings.roles.store') }}" method="POST" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="role_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Role Identifier Slug (lowercase, no spaces)</label>
                        <input type="text" name="name" id="role_name" required placeholder="e.g. cashier"
                               class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                    </div>
                    <div>
                        <label for="role_label" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Display Label</label>
                        <input type="text" name="label" id="role_label" required placeholder="e.g. Head Cashier"
                               class="w-full px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3">Allowed Features / View Access</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                        @foreach(\App\Models\Role::$modules as $modKey => $modLabel)
                            <label class="flex items-start gap-2.5 p-3 rounded-xl border border-slate-200 dark:border-slate-800/80 bg-slate-50/20 dark:bg-slate-950/20 hover:bg-slate-100/50 dark:hover:bg-slate-900/20 cursor-pointer transition text-xs select-none">
                                <input type="checkbox" name="allowed_modules[]" value="{{ $modKey }}" 
                                       class="h-4 w-4 text-primary rounded bg-white dark:bg-slate-955 border-slate-300 dark:border-slate-800 focus:ring-primary mt-0.5">
                                <div>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $modLabel }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <button type="submit" 
                            class="px-4 py-2.5 bg-primary hover:bg-primary-hover text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span>Create Role</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>


<!-- Cropper.js JavaScript CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>

<!-- Cropping Modal -->
<div id="cropper-modal" class="fixed inset-0 z-50 overflow-hidden hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-slate-955/75 transition-opacity" onclick="closeCropperModal()"></div>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl flex flex-col h-[550px]">
            <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-955/40">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                    <i data-lucide="crop" class="w-4 h-4 text-primary"></i>
                    <span>Crop Brand Logo</span>
                </h3>
                <button onclick="closeCropperModal()" class="text-slate-500 hover:text-slate-400 font-bold p-2">✕</button>
            </div>
            
            <!-- Cropper Workspace -->
            <div class="flex-1 bg-slate-950 p-4 flex items-center justify-center overflow-hidden">
                <img id="cropper-image" src="" alt="Source Logo" class="max-h-full max-w-full block">
            </div>

            <!-- Footer / Action Buttons -->
            <div class="p-5 border-t border-slate-200 dark:border-slate-800 flex gap-3 bg-slate-50 dark:bg-slate-955/40">
                <form action="{{ route('settings.logo') }}" method="POST" id="logo-crop-form" class="flex w-full gap-3">
                    @csrf
                    <input type="hidden" name="logo_base64" id="logo-base-input">
                    <button type="button" onclick="submitCroppedLogo()"
                            class="flex-1 py-2.5 px-4 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg transition text-xs shadow-sm cursor-pointer">
                        Crop & Save Logo
                    </button>
                    <button type="button" onclick="closeCropperModal()"
                            class="py-2.5 px-4 bg-slate-250 dark:bg-slate-855 hover:bg-slate-350 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold rounded-lg transition text-xs cursor-pointer">
                        Cancel
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    let cropper = null;

    function loadCropper(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const imageEl = document.getElementById('cropper-image');
                imageEl.src = e.target.result;
                
                // Show modal
                document.getElementById('cropper-modal').classList.remove('hidden');

                // Initialize Cropper.js
                if (cropper) {
                    cropper.destroy();
                }
                
                setTimeout(() => {
                    cropper = new Cropper(imageEl, {
                        aspectRatio: 1, // Enforce square crop
                        viewMode: 1,
                        autoCropArea: 0.9,
                        background: false,
                        responsive: true
                    });
                }, 100);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function closeCropperModal() {
        document.getElementById('cropper-modal').classList.add('hidden');
        document.getElementById('logo-file-input').value = '';
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
    }

    function submitCroppedLogo() {
        if (!cropper) return;
        const canvas = cropper.getCroppedCanvas({
            width: 256,
            height: 256
        });
        const base64Data = canvas.toDataURL('image/png');
        document.getElementById('logo-base-input').value = base64Data;
        document.getElementById('logo-crop-form').submit();
    }

    function selectAccent(color) {
        setAccentColor(color);
        // Refresh button rings
        document.querySelectorAll('.accent-btn').forEach(btn => {
            btn.classList.remove('ring-4', 'ring-primary/45');
        });
        document.getElementById('btn-accent-' + color).classList.add('ring-4', 'ring-primary/45');
    }

    document.addEventListener('DOMContentLoaded', () => {
        const activeAccent = localStorage.getItem('accent') || 'blue';
        const activeBtn = document.getElementById('btn-accent-' + activeAccent);
        if (activeBtn) activeBtn.classList.add('ring-4', 'ring-primary/45');
    });
</script>
@endsection
