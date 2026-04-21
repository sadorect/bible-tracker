<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Reading Progress: {{ $readingPlan->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Your Progress</h3>
                        <p class="text-gray-600 mt-1">Track your reading progress through the plan.</p>
                    </div>
                    
                    <div
                        class="overflow-x-auto"
                        data-table-columns="legacy-reading-plan-progress"
                        data-default-columns='{"completed-date":false}'
                        data-default-columns-md='{"completed-date":true}'
                    >
                        <div class="mb-4 flex justify-end">
                            <details class="relative">
                                <summary class="flex cursor-pointer list-none items-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:border-gray-300 hover:text-gray-900">
                                    <i class="fas fa-table-columns text-gray-400"></i>
                                    Display columns
                                    <i class="fas fa-chevron-down text-xs text-gray-400"></i>
                                </summary>
                                <div class="absolute right-0 z-10 mt-3 w-72 rounded-3xl border border-gray-200 bg-white p-4 shadow-2xl">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Progress table</p>
                                    <p class="mt-2 text-sm text-gray-500">Choose whether completed dates stay visible on this device.</p>
                                    <div class="mt-4 grid gap-3">
                                        <label class="flex items-center gap-3 text-sm text-gray-700">
                                            <input type="checkbox" data-column-toggle="completed-date" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            Completed date
                                        </label>
                                    </div>
                                    <button type="button" data-table-columns-reset class="mt-4 inline-flex items-center rounded-2xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-white hover:text-gray-900">
                                        Reset compact defaults
                                    </button>
                                </div>
                            </details>
                        </div>

                        <div data-table-columns-root>
                        <table class="min-w-full bg-white">
                            <thead>
                                <tr>
                                    <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Day</th>
                                    <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Reading</th>
                                    <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Status</th>
                                    <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider" data-column="completed-date">Completed Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($progress as $day)
                                    <tr>
                                        <td class="py-4 px-4 text-sm">Day {{ $day['day'] }}</td>
                                        <td class="py-4 px-4 text-sm">{{ $day['reading'] }}</td>
                                        <td class="py-4 px-4 text-sm">
                                            @if($day['is_break_day'])
                                                <span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded-full">Break Day</span>
                                            @elseif($day['completed'])
                                                <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Completed</span>
                                            @else
                                                <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full">Not Completed</span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-4 text-sm" data-column="completed-date">
                                            {{ $day['completed_date'] ? \Carbon\Carbon::parse($day['completed_date'])->format('M d, Y') : '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                    </div>
                    
                    <div class="mt-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Actions</h3>
                        <div class="flex space-x-4">
                            <form action="{{ route('reading-plans.reset', $readingPlan) }}" method="POST" onsubmit="return confirm('Are you sure you want to reset your progress? This cannot be undone.');">
                                @csrf
                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm">
                                    Reset Progress
                                </button>
                            </form>
                            
                            <form action="{{ route('reading-plans.skip', $readingPlan) }}" method="POST" class="flex items-center space-x-2">
                                @csrf
                                <label for="day" class="text-sm text-gray-700">Skip to day:</label>
                                <input type="number" name="day" id="day" min="1" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50 w-20" required>
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm">
                                    Skip
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-6">
                <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-800">
                    &larr; Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
