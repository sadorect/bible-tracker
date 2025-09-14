<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Bible Reading Tracker' }}</title>
    
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    @livewireStyles
</head>
<body class="bg-gray-50 font-inter" x-data="{ sidebarOpen: false }">
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

    <!-- Top Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200 fixed w-full top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <button 
                        @click="sidebarOpen = !sidebarOpen"
                        class="md:hidden p-2 rounded-md text-gray-400 hover:text-gray-500"
                    >
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="flex items-center ml-2 md:ml-0">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-book-bible text-white text-sm"></i>
                        </div>
                        <a href="/dashboard" class="text-xl font-bold text-gray-900 hidden sm:block">
                            Bible Tracker
                        </a>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <button class="p-2 text-gray-400 hover:text-gray-500 relative">
                        <i class="fas fa-bell"></i>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">3</span>
                    </button>
                    
                    <div class="flex items-center">
                        <img 
                            class="w-8 h-8 rounded-full" 
                            src="https://ui-avatars.com/api/?name=User&background=3B82F6&color=fff" 
                            alt="User"
                        >
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
                <span class="text-lg font-bold">Bible Tracker</span>
                <button @click="sidebarOpen = false">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <nav class="mt-8 px-4 space-y-2">
            <a href="/dashboard" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100">
                <i class="fas fa-home mr-3"></i>
                Dashboard
            </a>
            <a href="/reading-plans" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100">
                <i class="fas fa-book-open mr-3"></i>
                Reading Plans
            </a>
            <a href="/reading-history" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100">
                <i class="fas fa-history mr-3"></i>
                History
            </a>
        </nav>
    </div>

    <!-- Desktop Sidebar -->
    <div class="hidden md:fixed md:inset-y-0 md:left-0 md:z-20 md:w-64 md:bg-white md:shadow-sm md:border-r">
        <div class="pt-20 pb-4">
            <nav class="px-4 space-y-2">
                <a href="/dashboard" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-home mr-3"></i>
                    Dashboard
                </a>
                <a href="/reading-plans" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-book-open mr-3"></i>
                    Reading Plans
                </a>
                <a href="/reading-history" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-history mr-3"></i>
                    History
                </a>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="md:pl-64 pt-16">
        <main class="p-4 md:p-8">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
