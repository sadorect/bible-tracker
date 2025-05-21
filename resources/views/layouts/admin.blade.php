<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - <a href="{{ route('admin.dashboard') }}">Admin</a></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        <!-- Admin Navigation -->
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <a href="{{ route('admin.dashboard') }}" class="text-xl font-bold text-gray-800">
                                {{ config('app.name', 'SGS') }} Admin
                            </a>
                        </div>
                        
                        <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition">
                                Users
                            </a>
                            <a href="{{ route('admin.reading-plans.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition">
                              Reading Plans
                          </a>
                          <a href="{{ route('hierarchy.manage') }}" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition">
                            Manage Structure
                        </a>
                        <a href="{{ route('progress.view') }}" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition">
                          Reading Progress
                      </a>
                      </div>
                  </div>
                  
                  <div class="hidden sm:flex sm:items-center sm:ml-6">
                      <div class="ml-3 relative">
                          <div class="flex items-center">
                              <span class="text-gray-700 mr-2">{{ Auth::user()->name }}</span>
                              <form method="POST" action="{{ route('logout') }}">
                                  @csrf
                                  <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">
                                      Logout
                                  </button>
                              </form>
                          </div>
                      </div>
                  </div>
                  
                  <!-- Mobile menu button -->
                  <div class="-mr-2 flex items-center sm:hidden">
                      <button type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition" aria-controls="mobile-menu" aria-expanded="false">
                          <span class="sr-only">Open main menu</span>
                          <!-- Icon when menu is closed -->
                          <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                          </svg>
                          <!-- Icon when menu is open -->
                          <svg class="hidden h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                          </svg>
                      </button>
                  </div>
              </div>
          </div>
          
          <!-- Mobile menu, show/hide based on menu state. -->
          <div class="sm:hidden" id="mobile-menu">
              <div class="pt-2 pb-3 space-y-1">
                  <a href="{{ route('admin.users.index') }}" class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:text-gray-800 focus:bg-gray-50 focus:border-gray-300 transition">
                      Users
                  </a>
                  <a href="{{ route('admin.reading-plans.index') }}" class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:text-gray-800 focus:bg-gray-50 focus:border-gray-300 transition">
                      Reading Plans
                  </a>
              </div>
              
              <div class="pt-4 pb-3 border-t border-gray-200">
                  <div class="flex items-center px-4">
                      <div class="flex-shrink-0">
                          <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                              <span class="text-gray-600">{{ substr(Auth::user()->name, 0, 1) }}</span>
                          </div>
                      </div>
                      <div class="ml-3">
                          <div class="text-base font-medium text-gray-800">{{ Auth::user()->name }}</div>
                          <div class="text-sm font-medium text-gray-500">{{ Auth::user()->email }}</div>
                      </div>
                  </div>
                  <div class="mt-3 space-y-1">
                      <form method="POST" action="{{ route('logout') }}">
                          @csrf
                          <button type="submit" class="block w-full text-left px-4 py-2 text-base font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100 focus:outline-none focus:text-gray-800 focus:bg-gray-100 transition">
                              Logout
                          </button>
                      </form>
                  </div>
              </div>
          </div>
      </nav>

      <!-- Page Heading -->
      <header class="bg-white shadow">
          <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
              <h1 class="text-2xl font-semibold text-gray-900">
                  {{ $header ?? 'Admin Dashboard' }}
              </h1>
          </div>
      </header>

      <!-- Flash Messages -->
      @if (session('success'))
          <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
              <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4" role="alert">
                  <p>{{ session('success') }}</p>
              </div>
          </div>
      @endif

      @if (session('error'))
          <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
              <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
                  <p>{{ session('error') }}</p>
              </div>
          </div>
      @endif

      <!-- Page Content -->
      <main>
          <div class="py-6">
              <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                  {{ $slot }}
              </div>
          </div>
      </main>
  </div>
</body>
</html>
