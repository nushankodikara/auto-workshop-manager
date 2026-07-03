<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | {{ config('app.name', 'Auto Workshop Manager') }}</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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
    
    <script src="https://unpkg.com/lucide@latest"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- PWA Capabilities -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#3b82f6">
</head>

<body class="h-full flex flex-col justify-center items-center p-6 bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors duration-200">

    <div class="w-full max-w-md">
        <!-- Brand Header -->
        <div class="text-center mb-8 flex flex-col items-center">
            @if(file_exists(public_path('images/logo.png')))
                <img src="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" alt="Logo" class="w-16 h-16 object-contain rounded-xl mb-4">
            @else
                <svg class="w-16 h-16 text-primary mb-4" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M50 10 L85 30 L85 70 L50 90 L15 70 L15 30 Z" stroke="currentColor" stroke-width="6" stroke-linejoin="round" fill="currentColor" fill-opacity="0.05"/>
                    <circle cx="50" cy="50" r="18" stroke="currentColor" stroke-width="6"/>
                    <path d="M50 24 L50 32 M50 68 L50 76 M24 50 L32 50 M68 50 L76 50 M32 32 L38 38 M62 62 L68 68 M32 68 L38 62 M62 32 L68 38" stroke="currentColor" stroke-width="6" stroke-linecap="round"/>
                    <path d="M35 65 L65 35" stroke="currentColor" stroke-width="8" stroke-linecap="round"/>
                </svg>
            @endif
            <h1 class="text-3xl font-bold text-slate-800 dark:text-slate-100">
                {{ config('app.name', 'Auto Workshop Manager') }}
            </h1>
            <p class="text-slate-550 dark:text-slate-400 text-sm mt-2">Employee Password Recovery</p>
        </div>

        <!-- Mock Notification Toasts for local testing/development -->
        @if($mockNotifs = session()->pull('mock_notifications'))
            <div class="space-y-4 mb-6" id="mock-toast-container">
                @foreach($mockNotifs as $notif)
                    <div id="mock-toast-{{ $loop->index }}" 
                         class="p-4 rounded-xl border border-primary/20 bg-primary/10 text-slate-800 dark:text-slate-200 text-sm flex flex-col gap-1.5 shadow-sm relative overflow-hidden transition-all duration-300 transform scale-100 opacity-100">
                        <!-- Left Accent Strip -->
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-primary"></div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-primary flex items-center gap-1.5">
                                <i data-lucide="mail" class="w-3.5 h-3.5"></i>
                                <span>Mock Email Notification (To: {{ $notif['to'] }})</span>
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
                        <div class="text-xs font-bold text-slate-700 dark:text-slate-300">Subject: {{ $notif['subject'] }}</div>
                        <div class="text-xs text-slate-650 dark:text-slate-355 leading-relaxed whitespace-pre-line bg-slate-900/10 dark:bg-slate-950/40 p-2 rounded-lg font-mono">{{ $notif['message'] }}</div>
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
            </script>
        @endif

        <!-- Corporate card form -->
        <div class="app-card rounded-2xl p-8 shadow-sm">
            <h2 class="text-xl font-semibold text-slate-800 dark:text-slate-100 mb-6 flex items-center gap-2">
                <i data-lucide="shield-check" class="w-5 h-5 text-primary"></i>
                <span>Reset Password</span>
            </h2>

            <!-- Errors -->
            @if ($errors->any())
                <div class="mb-4 p-3 rounded-lg bg-red-550/10 border border-red-500/20 text-red-650 dark:text-red-400 text-xs">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="mb-4 p-3 rounded-lg bg-green-550/10 border border-green-550/20 text-green-600 dark:text-green-400 text-xs">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('password.update') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Email Address</label>
                    <input type="email" name="email" id="email" required value="{{ old('email', $email) }}" placeholder="employee@totaldrivecare.com"
                           class="w-full px-4 py-3 app-input rounded-lg text-slate-900 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:border-primary transition text-sm">
                </div>

                <div>
                    <label for="code" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">6-Digit Verification Code</label>
                    <input type="text" name="code" id="code" required maxlength="6" placeholder="123456"
                           class="w-full px-4 py-3 app-input rounded-lg text-slate-900 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:border-primary transition text-sm text-center font-mono tracking-widest text-lg">
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">New Password</label>
                    <input type="password" name="password" id="password" required placeholder="••••••••"
                           class="w-full px-4 py-3 app-input rounded-lg text-slate-900 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:border-primary transition text-sm">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Confirm New Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required placeholder="••••••••"
                           class="w-full px-4 py-3 app-input rounded-lg text-slate-900 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:border-primary transition text-sm">
                </div>

                <button type="submit"
                        class="w-full py-3 px-4 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg transition-all text-sm shadow-sm flex items-center justify-center gap-1.5 cursor-pointer">
                    <i data-lucide="lock" class="w-4 h-4"></i>
                    <span>Reset Password</span>
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('password.request') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 flex items-center justify-center gap-1">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                    <span>Resend Code</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
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
</body>
</html>

