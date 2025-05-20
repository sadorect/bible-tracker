<x-app-layout>
    <div class="flex h-screen bg-gray-50">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-md">
            <div class="p-4 border-b">
                <h2 class="text-xl font-semibold text-primary-600">{{ auth()->user()->name }}</h2>
                <p class="text-sm text-gray-600">{{ ucfirst(auth()->user()->role) }}</p>
            </div>
            
            <nav class="mt-4">
                <x-sidebar-link route="dashboard" icon="home">
                    Dashboard
                </x-sidebar-link>
                
                @if(auth()->user()->canManageHierarchy())
                    <x-sidebar-link route="hierarchy.manage" icon="users">
                        Manage Structure
                    </x-sidebar-link>
                @endif
                
                <x-sidebar-link route="progress.view" icon="chart-bar">
                    Reading Progress
                </x-sidebar-link>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <header class="bg-white shadow">
                <div class="px-4 py-6">
                    <h1 class="text-2xl font-semibold text-gray-900">
                        {{ $pageTitle ?? 'Dashboard' }}
                    </h1>
                </div>
            </header>

            <main class="p-6">
                {{ $slot }}
            </main>
        </div>
    </div>
</x-app-layout>
