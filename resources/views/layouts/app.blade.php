<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Workspace') | {{ config('app.name', 'Auto Workshop Manager') }}</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
        /* Glassmorphism custom styles */
        .glass-card {
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .glass-header {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex flex-col md:flex-row">

    <!-- Sidebar Navigation -->
    <aside class="w-full md:w-64 bg-slate-900 border-r border-slate-800 flex flex-col justify-between shrink-0">
        <div>
            <!-- Branding -->
            <div class="h-16 flex items-center px-6 border-b border-slate-800 bg-slate-950">
                <span class="text-xl font-bold bg-gradient-to-r from-blue-400 to-indigo-500 bg-clip-text text-transparent">
                    {{ config('app.name', 'Auto Workshop Manager') }}
                </span>
            </div>
            
            <!-- Navigation Links -->
            <nav class="mt-6 px-4 space-y-1">
                @if(auth()->user()->hasModuleAccess('dashboard'))
                    <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
                        <span>📊 Dashboard</span>
                    </a>
                @endif

                @if(auth()->user()->hasModuleAccess('job-cards'))
                    <a href="{{ route('job-cards.board') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition {{ request()->routeIs('job-cards.*') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
                        <span>📋 Job Cards Kanban</span>
                    </a>
                @endif

                @if(auth()->user()->hasModuleAccess('clients'))
                    <a href="{{ route('clients.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition {{ request()->routeIs('clients.*') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
                        <span>👥 Clients & Vehicles</span>
                    </a>
                @endif

                @if(auth()->user()->hasModuleAccess('inventory'))
                    <a href="{{ route('inventory.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition {{ request()->routeIs('inventory.*') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
                        <span>📦 Inventory & Stock</span>
                    </a>
                @endif

                @if(auth()->user()->hasModuleAccess('payroll'))
                    <a href="{{ route('payroll.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition {{ request()->routeIs('payroll.*') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
                        <span>💵 Payroll & HR</span>
                    </a>
                @endif

                @if(auth()->user()->isSuperManager())
                    <a href="{{ route('settings.index') }}" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition {{ request()->routeIs('settings.index') ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100' }}">
                        <span>⚙️ Settings & Backups</span>
                    </a>
                @endif
            </nav>
        </div>

        <!-- User Profile Summary at bottom -->
        <div class="p-4 border-t border-slate-800 bg-slate-950">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-200">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-slate-500 capitalize">{{ auth()->user()->role }}</div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-xs text-red-400 hover:text-red-300 font-semibold p-2 hover:bg-red-500/10 rounded-lg transition">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top bar -->
        <header class="h-16 flex items-center justify-between px-8 glass-header">
            <h1 class="text-lg font-semibold text-slate-200">@yield('title')</h1>
            <div class="text-xs text-slate-500">
                Local Time: {{ date('Y-m-d') }}
            </div>
        </header>

        <!-- Dynamic Content Body -->
        <main class="flex-1 overflow-y-auto p-8">
            <!-- Flash message alerts -->
            @if(session('success'))
                <div class="mb-6 p-4 rounded-lg bg-green-500/10 border border-green-500/30 text-green-400 text-sm flex items-center">
                    <span>✅ {{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 rounded-lg bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

</body>
</html>
