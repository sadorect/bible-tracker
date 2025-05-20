<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Bible Reading App') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Livewire Styles -->
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-blue-800 text-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <i class="fas fa-book-bible text-xl mr-2"></i>
                        <a href="{{ route('dashboard') }}" class="text-xl font-bold">Daily Word</a>
                    </div>
                    <div class="flex items-center">
                      <i class="fas fa-book-bible text-xl mr-2"></i>
                      <a href="{{ route('dashboard') }}" class="text-xl font-bold">Dashboard</a>
                  </div>
                  <div class="flex items-center">
                    <i class="fas fa-book-bible text-xl mr-2"></i>
                    <a href="{{ route('reading-plans.index') }}" class="text-xl font-bold">Reading Plans</a>
                </div>
                    <div class="flex items-center">
                        <a href="#" class="p-2 rounded hover:bg-blue-700">
                            <i class="fas fa-bell mr-1"></i>
                            <span class="bg-red-500 text-white text-xs rounded-full px-1">
                                                           </span>
                        </a>
                        <div class="ml-4 relative" x-data="{ open: false }">
                            <div @click="open = !open" class="flex items-center cursor-pointer">
                                <img class="h-8 w-8 rounded-full" src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}" alt="User avatar">
                                <span class="ml-2">{{ auth()->user()->name }}</span>
                                <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            </div>
                            
                            <div x-show="open" 
                                 @click.away="open = false"
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Log Out</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>
    
    <!-- Livewire Scripts -->
    @livewireScripts
    
    <!-- AlpineJS -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>