<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Bible-Reader') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('scripts')
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gradient-to-br from-emerald-50 via-teal-50 to-amber-50 dark:from-gray-950 dark:via-teal-950 dark:to-emerald-950">
        <style>
            @keyframes floaty { 0%,100%{transform:translateY(0)} 50%{transform:translateY(8px)} }
            .mesh { background: radial-gradient(40% 50% at 10% 10%, rgba(20,184,166,.18) 0%, rgba(20,184,166,0) 100%),
                              radial-gradient(40% 50% at 90% 20%, rgba(59,130,246,.18) 0%, rgba(59,130,246,0) 100%),
                              radial-gradient(50% 60% at 20% 90%, rgba(245,158,11,.16) 0%, rgba(245,158,11,0) 100%); }
        </style>
        <!-- Simple Top Navigation -->
        <nav class="bg-white/90 dark:bg-gray-800/90 backdrop-blur border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <a href="/" class="flex items-center gap-3">
                    <x-application-logo class="w-8 h-8 text-indigo-600" />
                    <span class="font-semibold text-gray-900 dark:text-white">{{ config('app.name', 'Bible Tracker') }}</span>
                </a>
                <div class="flex items-center gap-6 text-sm">
                    <a href="{{ url('/#features') }}" class="text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400">Features</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">Dashboard</a>
                    @else
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400">Sign in</a>
                        @endif
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">Get started</a>
                        @endif
                    @endauth
                </div>
            </div>
        </nav>

        <div class="relative min-h-[calc(100vh-4rem)]">
            <div class="mesh absolute inset-0 pointer-events-none"></div>

            @if ($attributes->has('wide'))
                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 grid md:grid-cols-2 gap-10 items-center">
                    <div class="hidden md:block">
                        {{ $aside ?? '' }}
                    </div>
                    <div class="flex justify-center">
                        <div class="w-full sm:max-w-md px-6 py-6 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-xl border border-gray-200/80 dark:border-gray-700/60">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            @else
                <div class="relative flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
                    <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-xl border border-gray-200/80 dark:border-gray-700/60">
                        {{ $slot }}
                    </div>
                </div>
            @endif
        </div>
        
    </body>
</html>
