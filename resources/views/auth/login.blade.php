<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | {{ config('app.name', 'Auto Workshop Manager') }}</title>
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
            <p class="text-slate-550 dark:text-slate-400 text-sm mt-2">Manage mechanical, electrical, and parts inventory</p>
        </div>

        <!-- Corporate card form -->
        <div class="app-card rounded-2xl p-8 shadow-sm">
            <h2 class="text-xl font-semibold text-slate-800 dark:text-slate-100 mb-6">Sign In</h2>

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

            <form action="{{ route('login') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Email Address</label>
                    <input type="email" name="email" id="email" required value="{{ old('email') }}" placeholder="admin@totaldrivecare.com"
                           class="w-full px-4 py-3 app-input rounded-lg text-slate-900 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:border-primary transition text-sm">
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Password</label>
                    <input type="password" name="password" id="password" required placeholder="••••••••"
                           class="w-full px-4 py-3 app-input rounded-lg text-slate-900 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:border-primary transition text-sm">
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="remember" id="remember" class="h-4 w-4 bg-white dark:bg-slate-950 border border-slate-350 dark:border-slate-800 text-primary rounded focus:ring-primary">
                    <label for="remember" class="ml-2 text-xs text-slate-550 dark:text-slate-400">Remember me</label>
                </div>

                <button type="submit"
                        class="w-full py-3 px-4 bg-primary hover:bg-primary-hover text-white font-semibold rounded-lg transition-all text-sm shadow-sm flex items-center justify-center gap-1.5 cursor-pointer">
                    <i data-lucide="log-in" class="w-4 h-4"></i>
                    <span>Sign In to Portal</span>
                </button>
            </form>
        </div>
        
        <!-- Default login helper text for demo -->
        <div class="text-center mt-6">
            <p class="text-xs text-slate-450">Demo Credentials: <span class="text-slate-500 font-mono">admin@totaldrivecare.com</span> / <span class="text-slate-500 font-mono">Password123!</span></p>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
