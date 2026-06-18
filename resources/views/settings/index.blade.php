@extends('layouts.app')

@section('title', 'System Settings & Backups')

@section('content')
<div class="space-y-8 max-w-4xl mx-auto">

    <!-- Section 1: Backup Engine -->
    <div class="glass-card rounded-2xl p-6 space-y-6">
        <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
            <div>
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-200">Database Backup & Recovery</h3>
                <p class="text-xs text-slate-500 mt-1">Manage SQLite database file archives. Automatic cron backups run every hour.</p>
            </div>

            <!-- Trigger manual backup -->
            <form action="{{ route('settings.backup') }}" method="POST">
                @csrf
                <button type="submit" 
                        class="px-4 py-2 bg-indigo-650 hover:bg-indigo-600 text-slate-100 border border-indigo-500/20 rounded-lg text-xs font-bold transition">
                    + Run Manual Backup
                </button>
            </form>
        </div>

        <!-- Backups List -->
        <div class="space-y-4">
            @if(empty($backups))
                <div class="text-slate-500 text-sm py-8 text-center bg-slate-900/20 rounded-xl border border-slate-850 border-dashed">
                    No database backup files found inside the backups folder.
                </div>
            @else
                <div class="glass-card rounded-xl overflow-hidden border border-slate-900">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-950/60 border-b border-slate-850/80 text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                                <th class="py-3 px-5">Backup Filename</th>
                                <th class="py-3 px-5">File Size</th>
                                <th class="py-3 px-5">Created At</th>
                                <th class="py-3 px-5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-850/60">
                            @foreach($backups as $bkp)
                                <tr class="hover:bg-slate-900/20 transition">
                                    <td class="py-3 px-5 font-mono text-slate-200">{{ $bkp['name'] }}</td>
                                    <td class="py-3 px-5 text-slate-400">{{ $bkp['size'] }}</td>
                                    <td class="py-3 px-5 text-slate-400 font-mono">{{ $bkp['time'] }}</td>
                                    <td class="py-3 px-5 text-right space-x-2">
                                        <!-- Restore form -->
                                        <form action="{{ route('settings.restore') }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="filename" value="{{ $bkp['name'] }}">
                                            <button type="submit" 
                                                    onclick="return confirm('WARNING: Restoring will completely overwrite the active database. Proceed?')"
                                                    class="px-2.5 py-1 bg-emerald-600/10 hover:bg-emerald-600/25 border border-emerald-500/25 text-emerald-400 font-semibold rounded transition">
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

    <!-- Section 2: Branding details -->
    <div class="glass-card rounded-2xl p-6">
        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-200 border-b border-slate-800/80 pb-3 mb-4">
            Workshop Information
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
            <div class="p-3 bg-slate-900/35 rounded-lg border border-slate-850">
                <span class="text-slate-500 block">Active Portal Branding</span>
                <span class="font-bold text-slate-200 mt-1 block">{{ config('app.name', 'Auto Workshop Manager') }}</span>
            </div>
            <div class="p-3 bg-slate-900/35 rounded-lg border border-slate-850">
                <span class="text-slate-500 block">Database Storage Driver</span>
                <span class="font-bold text-slate-200 mt-1 block uppercase">SQLite v3</span>
            </div>
        </div>
    </div>

</div>
@endsection
