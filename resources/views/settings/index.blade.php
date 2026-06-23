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
            <div>
                <label for="job_card_prefix" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Job Card Number Prefix</label>
                <div class="flex gap-3">
                    <input type="text" name="job_card_prefix" id="job_card_prefix" required 
                           value="{{ \App\Models\Setting::get('job_card_prefix', 'TDC-') }}" 
                           placeholder="TDC-"
                           class="w-full max-w-xs px-4 py-2.5 app-input rounded-lg text-slate-900 dark:text-slate-200 focus:outline-none focus:border-primary text-sm font-mono">
                    <button type="submit" 
                            class="px-4 py-2.5 bg-primary hover:bg-primary-hover text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span>Save Setting</span>
                    </button>
                </div>
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
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200 flex items-center gap-2">
                    <i data-lucide="database" class="w-4 h-4 text-primary"></i>
                    <span>Database Backup & Recovery</span>
                </h3>
                <p class="text-xs text-slate-500 mt-1">Manage SQLite database file archives. Automatic cron backups run every hour.</p>
            </div>

            <!-- Trigger manual backup -->
            <form action="{{ route('settings.backup') }}" method="POST">
                @csrf
                <button type="submit" 
                        class="px-4 py-2 bg-primary hover:bg-primary-hover text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                    <span>Run Manual Backup</span>
                </button>
            </form>
        </div>

        <!-- Backups List -->
        <div class="space-y-4">
            @if(empty($backups))
                <div class="text-slate-500 text-sm py-8 text-center bg-slate-50 dark:bg-slate-950/20 rounded-xl border border-slate-200 dark:border-slate-800 border-dashed">
                    No database backup files found inside the backups folder.
                </div>
            @else
                <div class="app-card rounded-xl overflow-hidden shadow-xs">
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
                                        <!-- Restore form -->
                                        <form action="{{ route('settings.restore') }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="filename" value="{{ $bkp['name'] }}">
                                            <button type="submit" 
                                                    onclick="return confirm('WARNING: Restoring will completely overwrite the active database. Proceed?')"
                                                    class="px-2.5 py-1 bg-green-500/10 hover:bg-green-500/25 border border-green-500/20 text-green-600 dark:text-green-400 font-semibold rounded transition text-[10px]">
                                                Restore Database
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
