<a href="{{ route($route) }}" 
   class="flex items-center px-4 py-2 text-gray-600 hover:bg-primary-50 hover:text-primary-700 rounded-md transition-colors {{ request()->routeIs($route) ? 'bg-primary-50 text-primary-700' : '' }}">
    <span class="mr-3">
        <x-heroicon-o-{{ $icon }} class="w-5 h-5" />
    </span>
    {{ $slot }}
</a>