<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Today's Reading Card (Featured) -->
        <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-blue-600 mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg text-blue-800 font-bold">TODAY'S READING</h2>
                <span class="bg-blue-100 text-blue-800 font-medium py-1 px-3 rounded-full text-sm">DAY {{ $userPlan->pivot->current_day }}</span>
            </div>
            <div class="text-center py-4">
                @if($todayReading && $todayReading->is_break_day)
                    <h1 class="text-4xl font-bold text-gray-800 mb-2">Break Day</h1>
                    <p class="text-gray-600">Take a day to reflect on your readings so far</p>
                @else
                    <h1 class="text-4xl font-bold text-gray-800 mb-2">{{ $todayReading ? $todayReading->reading_range : 'No reading assigned' }}</h1>
                    <p class="text-gray-600">{{ $readingPlan->type == 'old_testament' ? 'Old Testament' : 'New Testament' }} Reading Plan</p>
                @endif
            </div>
            @if($todayReading && !$todayReading->is_break_day)
                <div class="mt-4 text-left">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Chapters to Read:</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                        @foreach($todayChapters as $chapter)
                            @if(Route::has('bible.chapter'))<a href="{{ route('bible.chapter', [$chapter->book_name, $chapter->chapter_number]) }}"                              class="bg-blue-50 hover:bg-blue-100 rounded-md p-2 text-center">
                                @else
                                <span class="text-sm font-medium text-blue-700">{{ $chapter->book_name }} {{ $chapter->chapter_number }}</span>
                            </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
            <div class="flex justify-center mt-4">
                @if($completedToday)
                    <button class="bg-green-200 text-green-800 font-bold py-2 px-6 rounded-lg cursor-default">
                        <i class="fas fa-check mr-2"></i> Completed
                    </button>
                @elseif($todayReading && !$todayReading->is_break_day) 
                   <button wire:click="markAsComplete" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg shadow transition">
                        Mark as Complete
                    </button>
                @endif
            </div>
        </div>

        <!-- Reading Stats & Calendar Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Reading Stats -->
            <div class="bg-white rounded-lg shadow p-6 col-span-1">
                <h3 class="font-bold text-lg mb-4 text-gray-700">Reading Stats</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Current Streak</span>
                        <span class="font-bold text-xl">{{ $userPlan->pivot->current_streak }} days</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Completion Rate</span>
                        <span class="font-bold text-xl">{{ number_format($userPlan->pivot->completion_rate, 0) }}%</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Next Break</span>
                        <span class="font-bold text-xl">{{ $nextBreakDays }} days</span>
                    </div>
                </div>
            </div>

            <!-- Progress Calendar -->
            <div class="bg-white rounded-lg shadow p-6 col-span-1 md:col-span-2">
                <h3 class="font-bold text-lg mb-4 text-gray-700">{{ now()->format('F Y') }}</h3>
                <div class="grid grid-cols-7 gap-2 text-center">
                    <div class="text-xs font-medium text-gray-500">Sun</div>
                    <div class="text-xs font-medium text-gray-500">Mon</div>
                    <div class="text-xs font-medium text-gray-500">Tue</div>
                    <div class="text-xs font-medium text-gray-500">Wed</div>
                    <div class="text-xs font-medium text-gray-500">Thu</div>
                    <div class="text-xs font-medium text-gray-500">Fri</div>
                    <div class="text-xs font-medium text-gray-500">Sat</div>
                    
                    <!-- Calendar days -->
                    @foreach($calendarDays as $day)
                        <div class="h-10 w-10 rounded-full flex items-center justify-center 
                            {{ !$day['is_current_month'] ? 'text-gray-400' : 'text-gray-600' }}
                            {{ $day['is_break_day'] && !$day['is_future'] ? 'bg-gray-200' : '' }}
                            {{ $day['is_completed'] ? 'bg-green-500 text-white' : '' }}
                            {{ $day['is_today'] ? 'font-bold border-2 border-blue-600' : '' }}">
                            {{ $day['day'] }}
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 flex justify-end space-x-4">
                    <div class="flex items-center">
                        <div class="h-4 w-4 rounded-full bg-green-500 mr-2"></div>
                        <span class="text-xs text-gray-600">Completed</span>
                    </div>
                    <div class="flex items-center">
                        <div class="h-4 w-4 rounded-full bg-gray-200 mr-2"></div>
                        <span class="text-xs text-gray-600">Break Day</span>
                    </div>
                    <div class="flex items-center">
                        <div class="h-4 w-4 rounded-full border-2 border-blue-600 mr-2"></div>
                        <span class="text-xs text-gray-600">Today</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reading History & Community Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Reading History -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-bold text-lg mb-4 text-gray-700">Reading History</h3>
                <div class="space-y-4">
                    @forelse($readingHistory as $history)
                        <div class="border-b pb-3">
                            <div class="flex justify-between">
                                <div>
                                    <span class="font-medium">Day {{ $history['day'] }} - {{ $history['date'] }}</span>
                                    <p class="text-gray-600">{{ $history['reading'] }}</p>
                                </div>
                                @if($history['completed'])
                                    <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full flex items-center">
                                        <i class="fas fa-check mr-1"></i> Completed
                                    </span>
                                @else
                                    <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full flex items-center">
                                        <i class="fas fa-times mr-1"></i> Missed
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-gray-600 py-4">
                            No reading history yet.
                        </div>
                    @endforelse
                </div>
                <div class="mt-4 text-center">
                    <a href="#" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View Full History</a>
                </div>
            </div>

            <!-- Community Updates -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-bold text-lg mb-4 text-gray-700">Group Updates</h3>
                <div class="space-y-4">
                    @forelse($groupMessages as $message)
                        <div class="border-b pb-3">
                            <p class="text-gray-800">
                                <span class="font-medium">{{ $message->is_admin_message ? 'Admin Message' : $message->title }}:</span> 
                                {{ $message->message }}
                            </p>
                            <p class="text-gray-500 text-sm mt-1">
                                {{ $message->created_at->diffForHumans() }}
                            </p>
                        </div>
                    @empty
                        <div class="text-center text-gray-600 py-4">
                            No group updates yet.
                        </div>
                    @endforelse
                </div>
                <div class="mt-4 text-center">
                    <a href="#" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View All Updates</a>
                </div>
            </div>
        </div>
    </div>
</div>