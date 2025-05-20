<div class="space-y-8">
    <!-- Today's Reading -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900">Today's Reading</h2>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm text-gray-600">Day {{ $currentDay }}</p>
                <p class="text-lg font-medium">Chapters: {{ $stats['todayChapters'] }}</p>
            </div>
            <div class="text-right">
                <button 
                    wire:click="markAsComplete"
                    class="btn-primary {{ $stats['completedToday'] ? 'bg-green-600' : '' }}">
                    {{ $stats['completedToday'] ? 'Completed' : 'Mark as Complete' }}
                </button>
            </div>
        </div>
    </div>

    <!-- Progress Overview -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-gray-900">My Progress</h2>
            <div class="text-right">
                <p class="text-sm text-gray-600">Overall Completion</p>
                <p class="text-2xl font-bold text-primary-600">{{ $stats['overallProgress'] }}%</p>
            </div>
        </div>

        <div class="grid grid-cols-7 gap-2">
            @foreach($readingProgress as $progress)
                <div 
                    class="aspect-square rounded-lg {{ $progress->is_completed ? 'bg-primary-500' : 'bg-gray-100' }} 
                           flex items-center justify-center cursor-pointer hover:opacity-75 transition-opacity"
                    wire:click="toggleDay({{ $progress->day_number }})">
                    <span class="text-sm {{ $progress->is_completed ? 'text-white' : 'text-gray-600' }}">
                        {{ $progress->day_number }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Reading History -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Reading History</h2>
        <div class="space-y-4">
            @foreach($readingProgress->take(10) as $progress)
                <div class="flex justify-between items-center p-4 border rounded-lg">
                    <div>
                        <p class="font-medium">Day {{ $progress->day_number }}</p>
                        <p class="text-sm text-gray-600">{{ $progress->chapters_range }}</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        @if($progress->is_completed)
                            <span class="text-green-600">Completed</span>
                            <span class="text-sm text-gray-600">
                                {{ $progress->completed_at->format('M d, Y') }}
                            </span>
                        @else
                            <span class="text-red-600">Pending</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
