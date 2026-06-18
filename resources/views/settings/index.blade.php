@extends('layouts.app')

@section('title', 'System Settings & Backups')

@section('content')
<div class="space-y-8 max-w-4xl mx-auto">

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

<script>
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
