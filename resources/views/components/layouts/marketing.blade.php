<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'Bible Tracker') }}</title>
        <meta name="description" content="Track your daily Bible reading, follow structured plans, visualize progress, and grow together with your community.">

        <!-- Open Graph -->
        <meta property="og:title" content="Bible Reading Tracker" />
        <meta property="og:description" content="Structured reading plans, progress tracking, and community for your Scripture journey." />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="{{ url('/') }}" />
        <meta property="og:image" content="{{ asset('favicon.ico') }}" />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600;inter:400,500,700&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            @keyframes floaty { 0%,100%{transform:translateY(0)} 50%{transform:translateY(10px)} }
            .mesh { background: radial-gradient(40% 50% at 10% 10%, rgba(20,184,166,.25) 0%, rgba(20,184,166,0) 100%),
                                  radial-gradient(40% 50% at 90% 20%, rgba(59,130,246,.25) 0%, rgba(59,130,246,0) 100%),
                                  radial-gradient(50% 60% at 20% 90%, rgba(245,158,11,.22) 0%, rgba(245,158,11,0) 100%); }
        </style>
    </head>
    <body class="font-sans antialiased bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100">
        <!-- Top Navigation -->
        <header class="sticky top-0 z-40 border-b border-gray-200/70 dark:border-gray-800/80 bg-white/80 dark:bg-gray-950/70 backdrop-blur">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <a href="/" class="flex items-center gap-3">
                    <x-application-logo class="w-9 h-9 text-emerald-600" />
                    <span class="font-bold text-lg">{{ config('app.name', 'Bible Tracker') }}</span>
                </a>
                <nav class="hidden md:flex items-center gap-8 text-sm">
                    <a href="#features" class="hover:text-emerald-600">Features</a>
                    <a href="#how" class="hover:text-emerald-600">How it works</a>
                    <a href="#community" class="hover:text-emerald-600">Community</a>
                    <a href="#faq" class="hover:text-emerald-600">FAQ</a>
                </nav>
                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="hidden sm:inline-flex items-center px-4 py-2 rounded-lg border border-gray-300/70 dark:border-gray-700/70 hover:border-emerald-400/60">Sign in</a>
                        <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">Get started</a>
                    @endauth
                </div>
            </div>
        </header>

        <main>{{ $slot }}</main>

        <footer class="mt-24 border-t border-gray-200 dark:border-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 grid gap-8 md:grid-cols-3 text-sm">
                <div>
                    <div class="flex items-center gap-2">
                        <x-application-logo class="w-7 h-7 text-emerald-600" />
                        <span class="font-semibold">{{ config('app.name', 'Bible Tracker') }}</span>
                    </div>
                    <p class="mt-3 text-gray-600 dark:text-gray-400">Track scripture daily, follow meaningful plans, and grow with your community.</p>
                </div>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-semibold mb-3">Product</h4>
                        <ul class="space-y-2 text-gray-600 dark:text-gray-400">
                            <li><a href="#features" class="hover:text-emerald-600">Features</a></li>
                            <li><a href="#how" class="hover:text-emerald-600">How it works</a></li>
                            <li><a href="#community" class="hover:text-emerald-600">Community</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-3">Company</h4>
                        <ul class="space-y-2 text-gray-600 dark:text-gray-400">
                            <li><a href="#faq" class="hover:text-emerald-600">FAQ</a></li>
                            <li><a href="mailto:support@example.com" class="hover:text-emerald-600">Support</a></li>
                        </ul>
                    </div>
                </div>
                <div class="md:text-right text-gray-600 dark:text-gray-400">© {{ date('Y') }} {{ config('app.name', 'Bible Tracker') }}</div>
            </div>
        </footer>
    </body>
</html>
