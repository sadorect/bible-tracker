<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Reading Progress Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @livewire('reading-plan-progress', ['planId' => $planId])
            
            <div class="mt-6">
                <a href="{{ route('reading.progress') }}" class="text-blue-600 hover:text-blue-800">
                    &larr; Back to All Reading Plans
                </a>
            </div>
        </div>
    </div>
</x-app-layout>