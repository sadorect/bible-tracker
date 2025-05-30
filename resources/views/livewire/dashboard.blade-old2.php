<div>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Day Navigation -->
      <div class="bg-white rounded-lg shadow-lg p-4 mb-4">
          <div class="flex justify-between items-center mb-2">
              <h2 class="text-lg text-blue-800 font-bold">READING PLAN NAVIGATION</h2>
              @if($viewingDay != $userPlan->pivot->current_day)
                  <button wire:click="resetToToday" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md text-sm">
                      Back to Today
                  </button>
              @endif
          </div>
          
          <div class="relative">
              <div class="flex overflow-x-auto py-2 scrollbar-thin scrollbar-thumb-blue-500 scrollbar-track-blue-100">
                  @foreach($nearbyDays as $day)
                      <div wire:click="viewDay({{ $day['day'] }})" 
                          class="flex-shrink-0 mx-1 cursor-pointer {{ $day['is_current'] ? 'border-2 border-blue-600' : '' }}">
                          <div class="w-24 h-24 rounded-lg {{ $day['is_break_day'] ? 'bg-gray-200' : 'bg-white border' }} 
                              {{ $day['completed'] ? 'border-green-500' : '' }} 
                              {{ $day['is_today'] ? 'border-blue-600' : 'border-gray-200' }} 
                              flex flex-col items-center justify-center p-2">
                              <span class="text-xs font-medium {{ $day['is_today'] ? 'text-blue-600' : 'text-gray-500' }}">DAY</span>
                              <span class="text-xl font-bold {{ $day['is_today'] ? 'text-blue-600' : 'text-gray-800' }}">{{ $day['day'] }}</span>
                              @if($day['completed'])
                                  <span class="mt-1 text-xs px-2 py-0.5 bg-green-100 text-green-800 rounded-full">Completed</span>
                              @elseif($day['is_break_day'])
                                  <span class="mt-1 text-xs px-2 py-0.5 bg-gray-100 text-gray-800 rounded-full">Break Day</span>
                              @endif
                          </div>
                      </div>
                  @endforeach
              </div>
          </div>
      </div>

      <!-- Today's Reading Card (Featured) -->
      <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-blue-600 mb-8">
          <div class="flex justify-between items-center mb-4">
              <h2 class="text-lg text-blue-800 font-bold">
                  {{ $viewingDay == $userPlan->pivot->current_day ? "TODAY'S READING" : "DAY $viewingDay READING" }}
              </h2>
              <span class="bg-blue-100 text-blue-800 font-medium py-1 px-3 rounded-full text-sm">DAY {{ $viewingDay }}</span>
          </div>
          <div class="text-center py-4">
              @if($viewingReading && $viewingReading->is_break_day)
                  <h1 class="text-4xl font-bold text-gray-800 mb-2">Break Day</h1>
                  <p class="text-gray-600">Take a day to reflect on your readings so far</p>
              @else
                  <h1 class="text-4xl font-bold text-gray-800 mb-2">{{ $viewingReading ? $viewingReading->reading_range : 'No reading assigned' }}</h1>
                  <p class="text-gray-600">{{ $readingPlan->type == 'old_testament' ? 'Old Testament' : 'New Testament' }} Reading Plan</p>
              @endif
          </div>
          @if($viewingReading && !$viewingReading->is_break_day)
              <div class="mt-4 text-left">
                  <h3 class="text-sm font-medium text-gray-700 mb-2">Chapters to Read:</h3>
                  <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">

                        @foreach($viewingChapters as $chapter)
                            @if(Route::has('bible.chapter'))
                                <a href="{{ route('bible.chapter', [$chapter->book_name, $chapter->chapter_number]) }}" class="bg-blue-50 hover:bg-blue-100 rounded-md p-2 text-center">
                                    <span class="text-sm font-medium text-blue-700">{{ $chapter->book_name }} {{ $chapter->chapter_number }}</span>
                                </a>
                            @else
                                <span class="bg-blue-50 rounded-md p-2 text-center">
                                    <span class="text-sm font-medium text-blue-700">{{ $chapter->book_name }} {{ $chapter->chapter_number }}</span>
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
            <div class="flex justify-center mt-4">
                @if($viewingDay == $userPlan->pivot->current_day)
                    @if($completedToday)
                        <button class="bg-green-200 text-green-800 font-bold py-2 px-6 rounded-lg cursor-default">
                            <i class="fas fa-check mr-2"></i> Completed
                        </button>
                    @elseif($viewingReading && !$viewingReading->is_break_day) 
                        <button wire:click="markAsComplete" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg shadow transition">
                            Mark as Complete
                        </button>
                    @endif
                @else
                    @if($viewingCompleted)
                        <button class="bg-green-200 text-green-800 font-bold py-2 px-6 rounded-lg cursor-default">
                            <i class="fas fa-check mr-2"></i> Completed
                        </button>
                    @elseif($viewingReading && !$viewingReading->is_break_day && $viewingDay < $userPlan->pivot->current_day)
                        <button wire:click="markPreviousAsComplete" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg shadow transition">
                            Mark as Complete (Catch Up)
                        </button>
                    @endif
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
                    <div class="mt-4">
                        <button wire:click="showCatchUpOptions" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow transition">
                            Catch Up on Missed Days
                        </button>
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
                            {{ $day['is_missed'] ? 'bg-red-100 text-red-800' : '' }}
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
                        <div class="h-4 w-4 rounded-full bg-red-100 mr-2"></div>
                        <span class="text-xs text-gray-600">Missed</span>
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
                    <a href="{{ route('reading-history') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View Full History</a>
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

    <!-- Catch Up Modal -->
    @if($showCatchUpModal)
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Catch Up on Missed Readings</h3>
            </div>
            <div class="px-6 py-4">
                @if(count($missedReadings) > 0)
                    <p class="text-gray-700 mb-4">Select a day to mark as complete:</p>
                    <div class="space-y-2 max-h-60 overflow-y-auto">
                        @foreach($missedReadings as $reading)
                            <div wire:click="selectReading({{ $reading->id }})" 
                                class="p-3 border rounded-lg cursor-pointer hover:bg-blue-50 transition
                                {{ $selectedReading && $selectedReading->id == $reading->id ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <span class="font-medium">Day {{ $reading->day_number }}</span>
                                        <p class="text-gray-600 text-sm">{{ $reading->reading_range }}</p>
                                    </div>
                                    @if($selectedReading && $selectedReading->id == $reading->id)
                                        <span class="text-blue-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-700 text-center py-4">You have no missed readings to catch up on!</p>
                @endif
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                <button wire:click="$set('showCatchUpModal', false)" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded">
                    Cancel
                </button>
                @if(count($missedReadings) > 0)
                    <button wire:click="markPreviousAsComplete" 
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded {{ $selectedReading ? '' : 'opacity-50 cursor-not-allowed' }}"
                        {{ $selectedReading ? '' : 'disabled' }}>
                        Mark as Complete
                    </button>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Flash Message -->
    @if(session()->has('message'))
    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show"
        class="fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg">
        {{ session('message') }}
    </div>
    @endif
</div>