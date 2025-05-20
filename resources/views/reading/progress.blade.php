<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        <!-- New Testament Tracker -->
        <livewire:reading-tracker testament="new" />
        
        <!-- Old Testament Tracker -->
        <livewire:reading-tracker testament="old" />
    </div>
</x-app-layout>
