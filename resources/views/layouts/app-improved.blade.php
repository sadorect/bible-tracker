<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Bible Reading Tracker') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Livewire Styles -->
    @livewireStyles

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="font-inter antialiased bg-gray-50" x-data="{ sidebarOpen: false }">
    <div class="min-h-screen">
        <!-- Mobile sidebar overlay -->
        <div 
            x-show="sidebarOpen" 
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-black bg-opacity-50 md:hidden"
        ></div>

        <!-- Top Navigation Bar -->
        <nav class="bg-white shadow-sm border-b border-gray-200 fixed w-full top-0 z-30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <!-- Left side -->
                    <div class="flex items-center">
                        <!-- Mobile menu button -->
                        <button 
                            @click="sidebarOpen = !sidebarOpen"
                            class="md:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
                        >
                            <i class="fas fa-bars text-lg"></i>
                        </button>
                        
                        <!-- Logo -->
                        <div class="flex items-center ml-2 md:ml-0">
                            <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-book-bible text-white text-sm"></i>
                            </div>
                            <a href="{{ route('dashboard') }}" class="text-xl font-bold text-gray-900 hidden sm:block">
                                Bible Tracker
                            </a>
                        </div>
                    </div>

                    <!-- Right side -->
                    <div class="flex items-center space-x-4">
                        <!-- Notifications -->
                        <div class="relative">
                            <button class="p-2 text-gray-400 hover:text-gray-500">
                                <i class="fas fa-bell text-lg"></i>
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                                    3
                                </span>
                            </button>
                        </div>

                        <!-- User menu -->
                        <div class="relative" x-data="{ open: false }">
                            <button 
                                @click="open = !open"
                                class="flex items-center space-x-3 p-1 rounded-full hover:bg-gray-100"
                            >
                                <img 
                                    class="w-8 h-8 rounded-full" 
                                    src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=3B82F6&color=fff" 
                                    alt="User avatar"
                                >
                                <div class="hidden sm:block text-left">
                                    <div class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</div>
                                    <div class="text-xs text-gray-500 capitalize">{{ auth()->user()->role }}</div>
                                </div>
                                <i class="fas fa-chevron-down text-gray-400 text-xs hidden sm:block"></i>
                            </button>
                            
                            <!-- Dropdown menu -->
                            <div 
                                x-show="open" 
                                @click.away="open = false"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 scale-95"
                                x-transition:enter-end="transform opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="transform opacity-100 scale-100"
                                x-transition:leave-end="transform opacity-0 scale-95"
                                class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50 border border-gray-200"
                            >
                                <a href="{{ route('profile.edit') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-user-edit mr-3 text-gray-400"></i>
                                    Profile Settings
                                </a>
                                @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-cog mr-3 text-gray-400"></i>
                                    Admin Panel
                                </a>
                                @endif
                                <hr class="my-2">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                        <i class="fas fa-sign-out-alt mr-3"></i>
                                        Sign Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Mobile Sidebar -->
        <div 
            x-show="sidebarOpen"
            x-transition:enter="transition ease-in-out duration-300"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in-out duration-300"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg md:hidden"
        >
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-book-bible text-white text-sm"></i>
                        </div>
                        <span class="text-lg font-bold text-gray-900">Bible Tracker</span>
                    </div>
                    <button @click="sidebarOpen = false" class="p-2 text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <nav class="mt-8">
                <div class="px-4 space-y-2">
                    <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700' : '' }}">
                        <i class="fas fa-home mr-3"></i>
                        Dashboard
                    </a>
                    <a href="{{ route('reading-plans.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 {{ request()->routeIs('reading-plans.*') ? 'bg-blue-50 text-blue-700' : '' }}">
                        <i class="fas fa-book-open mr-3"></i>
                        Reading Plans
                    </a>
                    <a href="{{ route('reading-history') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 {{ request()->routeIs('reading-history') ? 'bg-blue-50 text-blue-700' : '' }}">
                        <i class="fas fa-history mr-3"></i>
                        Reading History
                    </a>
                    <a href="{{ route('reading.progress') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 {{ (request()->routeIs('reading.progress') || request()->routeIs('progress.view')) ? 'bg-blue-50 text-blue-700' : '' }}">
                        <i class="fas fa-chart-line mr-3"></i>
                        Progress
                    </a>
                </div>
            </nav>
        </div>

        <!-- Desktop Sidebar -->
        <div class="hidden md:fixed md:inset-y-0 md:left-0 md:z-20 md:w-64 md:bg-white md:shadow-sm md:border-r md:border-gray-200">
            <div class="pt-20 pb-4">
                <nav class="px-4 space-y-2">
                    <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : '' }}">
                        <i class="fas fa-home mr-3"></i>
                        Dashboard
                    </a>
                    <a href="{{ route('reading-plans.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 {{ request()->routeIs('reading-plans.*') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : '' }}">
                        <i class="fas fa-book-open mr-3"></i>
                        Reading Plans
                    </a>
                    <a href="{{ route('reading-history') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 {{ request()->routeIs('reading-history') ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : '' }}">
                        <i class="fas fa-history mr-3"></i>
                        Reading History
                    </a>
                    <a href="{{ route('reading.progress') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100 {{ (request()->routeIs('reading.progress') || request()->routeIs('progress.view')) ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-600' : '' }}">
                        <i class="fas fa-chart-line mr-3"></i>
                        Progress
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="md:pl-64 pt-16">
            <main class="p-4 md:p-8">
                <!-- Flash Messages -->
                @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex">
                        <i class="fas fa-check-circle text-green-400 mr-3 mt-0.5"></i>
                        <p class="text-green-800">{{ session('success') }}</p>
                    </div>
                </div>
                @endif

                @if(session('error'))
                <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle text-red-400 mr-3 mt-0.5"></i>
                        <p class="text-red-800">{{ session('error') }}</p>
                    </div>
                </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
    
    <!-- Livewire Scripts -->
    @livewireScripts
</body>
</html>
