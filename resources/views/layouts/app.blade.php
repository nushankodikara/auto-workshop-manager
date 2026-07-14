<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Workspace') | {{ config('app.name', 'Auto Workshop Manager') }}</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Theme & Accent setup (Prevents Flash of Unstyled Theme) -->
    <script>
        const theme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        
        const accent = localStorage.getItem('accent') || 'blue';
        document.documentElement.className = theme + ' accent-' + accent + ' h-full';
    </script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- PWA Capabilities -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#3b82f6">

    <!-- Simple-DataTables CSS -->
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">
    <style>
        /* Custom Simple-DataTables styles matching dark glassmorphism theme */
        .datatable-wrapper {
            width: 100%;
        }
        .datatable-table th, .datatable-table td {
            padding: 1rem 1.25rem !important;
        }
        .datatable-top, .datatable-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
        }
        .dark .datatable-top, .dark .datatable-bottom {
            color: #94a3b8;
        }
        .datatable-top {
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }
        .datatable-bottom {
            border-top: 1px solid rgba(148, 163, 184, 0.1);
        }
        .datatable-search input, .datatable-dropdown select {
            background-color: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 0.5rem !important;
            color: #0f172a !important;
            padding: 0.5rem 0.75rem !important;
            font-size: 0.875rem !important;
            outline: none !important;
            text-transform: none !important;
            font-weight: normal !important;
            letter-spacing: normal !important;
            transition: border-color 0.2s !important;
        }
        .dark .datatable-search input, .dark .datatable-dropdown select {
            background-color: rgba(15, 23, 42, 0.3) !important;
            border: 1px solid rgba(148, 163, 184, 0.2) !important;
            color: #f1f5f9 !important;
        }
        .datatable-search input:focus, .datatable-dropdown select:focus {
            border-color: var(--color-primary, #3b82f6) !important;
        }
        .datatable-pagination ul {
            display: flex;
            list-style: none;
            padding-left: 0;
            gap: 0.25rem;
            margin: 0;
        }
        .datatable-pagination li {
            margin: 0;
        }
        .datatable-pagination a {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 2rem;
            height: 2rem;
            padding: 0 0.5rem;
            border-radius: 0.375rem;
            border: 1px solid #e2e8f0;
            color: #475569;
            text-decoration: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
            text-transform: none !important;
        }
        .dark .datatable-pagination a {
            border-color: rgba(148, 163, 184, 0.1);
            color: #cbd5e1;
        }
        .datatable-pagination a:hover {
            background-color: #f1f5f9;
        }
        .dark .datatable-pagination a:hover {
            background-color: rgba(148, 163, 184, 0.1);
        }
        .datatable-pagination .active a {
            background-color: var(--primary-color, #3b82f6) !important;
            border-color: var(--primary-color, #3b82f6) !important;
            color: #ffffff !important;
        }
        .datatable-pagination .disabled a {
            opacity: 0.4;
            cursor: not-allowed;
        }
        .datatable-pagination .disabled a:hover {
            background-color: transparent;
        }
        .datatable-sorter {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            color: inherit;
            text-decoration: none;
            cursor: pointer;
            width: 100%;
        }

        /* Collapsible Sidebar Styles */
        @media (min-width: 768px) {
            aside {
                transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            }
            aside.collapsed {
                width: 4.75rem !important; /* 76px */
            }
            aside.collapsed .truncate,
            aside.collapsed nav a span,
            aside.collapsed .user-details {
                display: none !important;
            }
            aside.collapsed nav a {
                justify-content: center;
                padding-left: 0 !important;
                padding-right: 0 !important;
            }
            aside.collapsed .branding-container {
                justify-content: center;
                padding-left: 0 !important;
                padding-right: 0 !important;
            }
            aside.collapsed .user-container {
                justify-content: center;
                padding: 1rem 0.5rem !important;
            }
            aside.collapsed .user-container form {
                width: 100%;
            }
            aside.collapsed .user-container button {
                width: 100%;
                justify-content: center;
                padding: 0.5rem 0 !important;
            }
            aside.collapsed .user-container button span {
                display: none !important;
            }
        }
    </style>
</head>
<body class="h-full flex flex-col md:flex-row bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors duration-200">


    <!-- Sidebar Navigation -->
    <aside class="w-full md:w-64 bg-slate-100 dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col justify-between shrink-0 transition-all duration-300 md:h-screen md:sticky md:top-0">
        <div class="flex flex-col flex-1 min-h-0">
            <!-- Branding -->
            <div class="h-16 flex items-center gap-3 px-6 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950/40 shrink-0 branding-container">
                @if(file_exists(public_path('images/logo.png')))
                    <img src="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" alt="Logo" class="w-8 h-8 object-contain rounded-lg shrink-0">
                @else
                    <!-- Fallback SVG Logo -->
                    <svg class="w-8 h-8 text-primary shrink-0" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M50 10 L85 30 L85 70 L50 90 L15 70 L15 30 Z" stroke="currentColor" stroke-width="6" stroke-linejoin="round" fill="currentColor" fill-opacity="0.05"/>
                        <circle cx="50" cy="50" r="18" stroke="currentColor" stroke-width="6"/>
                        <path d="M50 24 L50 32 M50 68 L50 76 M24 50 L32 50 M68 50 L76 50 M32 32 L38 38 M62 62 L68 68 M32 68 L38 62 M62 32 L68 38" stroke="currentColor" stroke-width="6" stroke-linecap="round"/>
                        <path d="M35 65 L65 35" stroke="currentColor" stroke-width="8" stroke-linecap="round"/>
                    </svg>
                @endif
                <span class="text-base font-bold text-slate-800 dark:text-slate-100 truncate">
                    {{ config('app.name', 'Auto Workshop Manager') }}
                </span>
                <button onclick="toggleSidebarCollapse()" class="hidden md:flex p-1.5 hover:bg-slate-250 dark:hover:bg-slate-800 rounded-lg text-slate-500 dark:text-slate-400 cursor-pointer ml-auto shrink-0 transition" title="Toggle Sidebar Width">
                    <i id="collapse-icon" data-lucide="chevron-left" class="w-4 h-4"></i>
                </button>
            </div>
            
            <!-- Navigation Links -->
            <nav class="flex-1 overflow-y-auto mt-6 px-4 space-y-1 pr-2">
                @if(auth()->user()->hasModuleAccess('dashboard'))
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition {{ request()->routeIs('dashboard') ? 'bg-primary text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100' }}">
                        <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0"></i>
                        <span>Dashboard</span>
                    </a>
                @endif

                @if(auth()->user()->hasModuleAccess('job-cards'))
                    <a href="{{ route('job-cards.board') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition {{ request()->routeIs('job-cards.*') ? 'bg-primary text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100' }}">
                        <i data-lucide="clipboard-list" class="w-4 h-4 shrink-0"></i>
                        <span>Job Cards Board</span>
                    </a>
                @endif

                @if(auth()->user()->hasModuleAccess('appointments'))
                    <a href="{{ route('appointments.index') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition {{ request()->routeIs('appointments.*') ? 'bg-primary text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100' }}">
                        <i data-lucide="calendar" class="w-4 h-4 shrink-0"></i>
                        <span>Appointments</span>
                    </a>
                @endif

                @if(auth()->user()->hasModuleAccess('quotations'))
                    <a href="{{ route('quotations.index') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition {{ request()->routeIs('quotations.*') ? 'bg-primary text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100' }}">
                        <i data-lucide="file-text" class="w-4 h-4 shrink-0"></i>
                        <span>Quotation Workspace</span>
                    </a>
                @endif

                @if(auth()->user()->hasModuleAccess('clients'))
                    <a href="{{ route('clients.index') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition {{ request()->routeIs('clients.*') ? 'bg-primary text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100' }}">
                        <i data-lucide="users" class="w-4 h-4 shrink-0"></i>
                        <span>Clients Directory</span>
                    </a>
                    <a href="{{ route('vehicles.index') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition {{ request()->routeIs('vehicles.*') ? 'bg-primary text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100' }}">
                        <i data-lucide="car" class="w-4 h-4 shrink-0"></i>
                        <span>Vehicles Directory</span>
                    </a>
                @endif

                @if(auth()->user()->hasModuleAccess('inventory'))
                    <a href="{{ route('inventory.index') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition {{ request()->routeIs('inventory.*') ? 'bg-primary text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100' }}">
                        <i data-lucide="package" class="w-4 h-4 shrink-0"></i>
                        <span>Inventory & Stock</span>
                    </a>
                @endif

                @if(auth()->user()->hasModuleAccess('payroll'))
                    <a href="{{ route('payroll.index') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition {{ request()->routeIs('payroll.*') ? 'bg-primary text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100' }}">
                        <i data-lucide="coins" class="w-4 h-4 shrink-0"></i>
                        <span>Payroll & HR</span>
                    </a>
                @endif

                @if(auth()->user()->hasModuleAccess('insights'))
                    <a href="{{ route('dashboard.insights') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition {{ request()->routeIs('dashboard.insights') ? 'bg-primary text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100' }}">
                        <i data-lucide="line-chart" class="w-4 h-4 shrink-0"></i>
                        <span>Data Insights</span>
                    </a>
                @endif
                @if(auth()->user()->hasModuleAccess('statistics'))
                    <a href="{{ route('dashboard.statistics') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition {{ request()->routeIs('dashboard.statistics') ? 'bg-primary text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100' }}">
                        <i data-lucide="trending-up" class="w-4 h-4 shrink-0"></i>
                        <span>Statistics & Finance</span>
                    </a>
                @endif
                @if(auth()->user()->hasModuleAccess('finance'))
                    <a href="{{ route('finance.index') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition {{ request()->routeIs('finance.*') ? 'bg-primary text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100' }}">
                        <i data-lucide="wallet" class="w-4 h-4 shrink-0"></i>
                        <span>Bookkeeping & Ledger</span>
                    </a>
                @endif
                @if(auth()->user()->hasModuleAccess('outsourcing'))
                    <a href="{{ route('outsourcing.index') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition {{ request()->routeIs('outsourcing.*') ? 'bg-primary text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100' }}">
                        <i data-lucide="handshake" class="w-4 h-4 shrink-0"></i>
                        <span>Outsourcing Partners</span>
                    </a>
                @endif
                @if(auth()->user()->hasModuleAccess('predefined-services'))
                    <a href="{{ route('services.index') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition {{ request()->routeIs('services.*') ? 'bg-primary text-white' : 'text-slate-650 dark:text-slate-405 hover:bg-slate-200 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-105' }}">
                        <i data-lucide="clipboard-list" class="w-4 h-4 shrink-0"></i>
                        <span>Predefined Services</span>
                    </a>
                @endif
                @if(auth()->user()->hasModuleAccess('broadcast'))
                    <a href="{{ route('broadcast.index') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition {{ request()->routeIs('broadcast.*') ? 'bg-primary text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100' }}">
                        <i data-lucide="megaphone" class="w-4 h-4 shrink-0"></i>
                        <span>Broadcast Messages</span>
                    </a>
                @endif
                @if(auth()->user()->hasModuleAccess('settings'))
                    <a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition {{ request()->routeIs('settings.index') ? 'bg-primary text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100' }}">
                        <i data-lucide="settings" class="w-4 h-4 shrink-0"></i>
                        <span>Settings & Backups</span>
                    </a>
                @endif
                @if(auth()->user()->hasModuleAccess('telemetry'))
                    <a href="{{ route('telemetry.index') }}" class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition {{ request()->routeIs('telemetry.index') ? 'bg-primary text-white' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100' }}">
                        <i data-lucide="activity" class="w-4 h-4 shrink-0"></i>
                        <span>Tracker Telemetry</span>
                    </a>
                @endif

            </nav>
        </div>

        <!-- User Profile Summary at bottom -->
        <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-white/40 dark:bg-slate-950/20 shrink-0 user-container">
            <div class="flex items-center justify-between gap-2">
                <div class="user-details min-w-0">
                    <div class="text-sm font-semibold text-slate-800 dark:text-slate-200 truncate">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-slate-500 capitalize truncate">{{ auth()->user()->role }}</div>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="shrink-0">
                    @csrf
                    <button type="submit" class="text-xs text-red-500 hover:text-red-400 font-semibold p-2 hover:bg-red-500/10 rounded-lg transition flex items-center gap-1 cursor-pointer" title="Logout">
                        <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top bar -->
        <header class="h-16 flex items-center justify-between px-8 bg-white/80 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-800 backdrop-blur-md transition-colors duration-200">
            <h1 class="text-lg font-semibold text-slate-850 dark:text-slate-200">@yield('title')</h1>
            
            <div class="flex items-center gap-4">
                <!-- Theme Toggle Button -->
                <button onclick="toggleTheme()" class="p-2 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 rounded-lg transition" title="Toggle Light/Dark Theme">
                    <i id="theme-icon" data-lucide="moon" class="w-4 h-4 hidden dark:block"></i>
                    <i id="theme-icon-light" data-lucide="sun" class="w-4 h-4 block dark:hidden"></i>
                </button>

                <div class="text-xs text-slate-500 hidden sm:block">
                    Date: {{ date('Y-m-d') }}
                </div>
            </div>
        </header>

        <!-- Dynamic Content Body -->
        <main class="flex-1 overflow-y-auto p-8">
            <!-- Flash message alerts -->
            @if(session('success'))
                <div class="mb-6 p-4 rounded-lg bg-green-550/10 border border-green-500/20 text-green-600 dark:text-green-400 text-sm flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 rounded-lg bg-red-550/10 border border-red-500/20 text-red-600 dark:text-red-400 text-sm">
                    <div class="flex items-center gap-2 mb-2 font-semibold">
                        <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0"></i>
                        <span>Please fix the errors below:</span>
                    </div>
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Mock Notification Toasts -->
            @if($mockNotifs = session()->pull('mock_notifications'))
                <div class="space-y-4 mb-6" id="mock-toast-container">
                    @foreach($mockNotifs as $notif)
                        <div id="mock-toast-{{ $loop->index }}" 
                             class="p-4 rounded-xl border border-primary/20 bg-primary/10 text-slate-800 dark:text-slate-200 text-sm flex flex-col gap-1.5 shadow-sm relative overflow-hidden transition-all duration-300 transform scale-100 opacity-100">
                            <!-- Left Accent Strip -->
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-primary"></div>
                            
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-primary flex items-center gap-1.5">
                                    @if($notif['type'] === 'sms')
                                        <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                                        <span>Mock SMS Notification (To: {{ $notif['to'] }})</span>
                                    @else
                                        <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                                        <span>Mock Email Notification (To: {{ $notif['to'] }})</span>
                                    @endif
                                </span>
                                <div class="flex items-center gap-2">
                                    <span class="text-[9px] font-semibold text-slate-500 dark:text-slate-400">Environment: Mock Mode</span>
                                    <button type="button" 
                                            onclick="dismissMockToast('mock-toast-{{ $loop->index }}')" 
                                            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-0.5 rounded-lg hover:bg-slate-500/10" 
                                            title="Dismiss">
                                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                    </button>
                                </div>
                            </div>
                            @if($notif['type'] === 'email')
                                <div class="text-xs font-bold text-slate-700 dark:text-slate-300">Subject: {{ $notif['subject'] }}</div>
                            @endif
                            <div class="text-xs text-slate-650 dark:text-slate-350 leading-relaxed whitespace-pre-line">{{ $notif['message'] }}</div>
                        </div>
                    @endforeach
                </div>
                
                <script>
                    function dismissMockToast(id) {
                        const el = document.getElementById(id);
                        if (el) {
                            el.classList.add('opacity-0', 'scale-95', '-translate-y-2');
                            setTimeout(() => {
                                el.remove();
                                const container = document.getElementById('mock-toast-container');
                                if (container && !container.children.length) {
                                    container.remove();
                                }
                            }, 300);
                        }
                    }

                    document.addEventListener('DOMContentLoaded', () => {
                        @foreach($mockNotifs as $notif)
                            setTimeout(() => {
                                dismissMockToast('mock-toast-{{ $loop->index }}');
                            }, 10000 + ({{ $loop->index }} * 500));
                        @endforeach
                    });
                </script>
            @endif

            @yield('content')
        </main>
    </div>

    <script>
        // Set Lucide Icons
        lucide.createIcons();

        // Theme toggler function
        function toggleTheme() {
            const html = document.documentElement;
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
            // Keep dynamic classes updated
            const accent = localStorage.getItem('accent') || 'blue';
            html.className = (html.classList.contains('dark') ? 'dark' : '') + ' accent-' + accent + ' h-full';
        }

        // Set active accent color from localStorage or settings
        function setAccentColor(color) {
            localStorage.setItem('accent', color);
            const html = document.documentElement;
            const isDark = html.classList.contains('dark');
            html.className = (isDark ? 'dark' : '') + ' accent-' + color + ' h-full';
        }

        // Sidebar Collapsible Toggler
        function toggleSidebarCollapse() {
            const aside = document.querySelector('aside');
            const icon = document.getElementById('collapse-icon');
            
            if (aside.classList.contains('collapsed')) {
                aside.classList.remove('collapsed');
                localStorage.setItem('sidebar-collapsed', 'false');
                if (icon) {
                    icon.setAttribute('data-lucide', 'chevron-left');
                }
            } else {
                aside.classList.add('collapsed');
                localStorage.setItem('sidebar-collapsed', 'true');
                if (icon) {
                    icon.setAttribute('data-lucide', 'chevron-right');
                }
            }
            
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        // Apply stored collapse preference on load
        (function() {
            const collapsed = localStorage.getItem('sidebar-collapsed') === 'true';
            if (collapsed && window.innerWidth >= 768) {
                document.addEventListener('DOMContentLoaded', () => {
                    const aside = document.querySelector('aside');
                    const icon = document.getElementById('collapse-icon');
                    if (aside) {
                        aside.classList.add('collapsed');
                    }
                    if (icon) {
                        icon.setAttribute('data-lucide', 'chevron-right');
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                    }
                });
            }
        })();
    </script>
    
    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('Service Worker registered successfully.', reg))
                    .catch(err => console.log('Service Worker registration failed.', err));
            });
        }
    </script>

    <!-- Simple-DataTables JS -->
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" type="text/javascript"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Find all tables that have the 'datatable' class
            const tables = document.querySelectorAll('table.datatable');
            tables.forEach(table => {
                new simpleDatatables.DataTable(table, {
                    searchable: true,
                    perPage: 10,
                    perPageSelect: [10, 25, 50, 100],
                    labels: {
                        placeholder: "Search list...",
                        perPage: "entries per page",
                        noRows: "No entries found matching search query",
                        info: "Showing {start} to {end} of {rows} entries",
                    }
                });
            });
        });
    </script>
</body>
</html>

