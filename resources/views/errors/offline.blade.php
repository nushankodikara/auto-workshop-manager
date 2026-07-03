<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline | {{ config('app.name', 'Auto Workshop Manager') }}</title>
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

    <div class="w-full max-w-md text-center">
        <!-- Icon -->
        <div class="inline-flex p-5 rounded-3xl bg-amber-500/10 border border-amber-500/20 text-amber-500 mb-6 animate-pulse">
            <i data-lucide="cloud-off" class="w-12 h-12"></i>
        </div>

        <h1 class="text-3xl font-extrabold tracking-tight text-slate-800 dark:text-slate-100 mb-2">
            You are offline
        </h1>
        <p class="text-sm text-slate-550 dark:text-slate-400 mb-8 leading-relaxed max-w-sm mx-auto">
            It looks like you've lost your internet connection. Please check your network status and try again.
        </p>

        <!-- Actions -->
        <div class="space-y-4">
            <button onclick="window.location.reload()" 
                    class="w-full py-3 px-4 bg-primary hover:bg-primary-hover text-white font-semibold rounded-xl transition-all text-sm shadow-sm flex items-center justify-center gap-2 cursor-pointer">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                <span>Retry Connection</span>
            </button>

            <a href="/" 
               class="inline-block text-xs font-semibold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 transition-colors">
                Back to Dashboard
            </a>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
