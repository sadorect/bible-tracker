<div>
  @if($userPlan && $userPlan->pivot)
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <!-- Navigation indicator (only show if viewing a different day) -->
          @if($viewingDay && $viewingDay != $userPlan->pivot->current_day)
              <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                  <div class="flex items-center justify-between">
                      <div class="flex items-center">
                          <svg class="h-5 w-5 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                          </svg>
                          <span class="text-blue-800 font-medium">
                              You're viewing Day {{ $viewingDay }} 
                              @if($viewingDay < $userPlan->pivot->current_day)
                                  (Previous Day)
                              @else
                                  (Future Day)
                              @endif
                          </span>
                      </div>
                      <button wire:click="resetToToday" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                          Return to Today (Day {{ $userPlan->pivot->current_day }})
                      </button>
                  </div>
              </div>
          @endif

          <!-- Today's Reading Card (Featured) -->
          <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-blue-600 mb-8">
              <div class="flex justify-between items-center mb-4">
                  <h2 class="text-lg text-blue-800 font-bold">
                      @if($viewingDay && $viewingDay != $userPlan->pivot->current_day)
                          DAY {{ $viewingDay }} READING
                      @else
                          TODAY'S READING
                      @endif
                  </h2>
                  <span class="bg-blue-100 text-blue-800 font-medium py-1 px-3 rounded-full text-sm">
                      DAY {{ $viewingDay ?? $userPlan->pivot->current_day }}
                  </span>
              </div>
              <div class="text-center py-4">
                  @if($viewingReading && $viewingReading->is_break_day)
                      <h1 class="text-4xl font-bold text-gray-800 mb-2">Break Day</h1>
                      <p class="text-gray-600">Take a day to reflect on your readings so far</p>
                                      @elseif($viewingReading)
                      <h1 class="text-4xl font-bold text-gray-800 mb-2">{{ $viewingReading->reading_range }}</h1>
                      <p class="text-gray-600">{{ $readingPlan->type == 'old_testament' ? 'Old Testament' : 'New Testament' }} Reading Plan</p>
                  @else
                      <h1 class="text-4xl font-bold text-gray-800 mb-2">No reading assigned</h1>
                      <p class="text-gray-600">Please check back later</p>
                  @endif
              </div>
              
              @if($viewingReading && !$viewingReading->is_break_day && $viewingChapters && count($viewingChapters) > 0)
                  <div class="mt-4 text-left">
                      <h3 class="text-sm font-medium text-gray-700 mb-2">Chapters to Read:</h3>
                      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                          @foreach($viewingChapters as $chapter)
                              @if(Route::has('bible.chapter'))
                                  <a href="{{ route('bible.chapter', [$chapter->book_name, $chapter->chapter_number]) }}"                              
                                     class="bg-blue-50 hover:bg-blue-100 rounded-md p-2 text-center transition-colors">
                                      <span class="text-sm font-medium text-blue-700">{{ $chapter->book_name }} {{ $chapter->chapter_number }}</span>
                                  </a>
                              @else
                                  <div class="bg-blue-50 rounded-md p-2 text-center">
                                      <span class="text-sm font-medium text-blue-700">{{ $chapter->book_name }} {{ $chapter->chapter_number }}</span>
                                  </div>
                              @endif
                          @endforeach
                      </div>
                  </div>
              @endif
              
              <div class="flex justify-center mt-4 space-x-3">
                  @if($viewingDay && $viewingDay == $userPlan->pivot->current_day)
                      <!-- Current day actions -->
                      @if($completedToday)
                          <button class="bg-green-200 text-green-800 font-bold py-2 px-6 rounded-lg cursor-default">
                              <i class="fas fa-check mr-2"></i> Completed
                          </button>
                      @elseif($todayReading && !$todayReading->is_break_day) 
                         <button wire:click="markAsComplete" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg shadow transition">
                              Mark as Complete
                          </button>
                      @endif
                  @elseif($viewingDay && $viewingDay < $userPlan->pivot->current_day)
                      <!-- Previous day actions -->
                      @if($viewingCompleted)
                          <button class="bg-green-200 text-green-800 font-bold py-2 px-6 rounded-lg cursor-default">
                              <i class="fas fa-check mr-2"></i> Completed
                          </button>
                      @elseif($viewingReading && !$viewingReading->is_break_day)
                          <button wire:click="markPreviousAsComplete" class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 px-6 rounded-lg shadow transition">
                              Mark as Complete (Catch Up)
                          </button>
                      @endif
                  @endif
                  
                  <!-- Catch up button (only show on current day if there are missed readings) -->
                  @if($viewingDay && $viewingDay == $userPlan->pivot->current_day)
                      <button wire:click="showCatchUpOptions" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg shadow transition">
                          Catch Up on Missed Days
                      </button>
                  @endif
              </div>
          </div>

          <!-- Day Navigation -->
          <div class="bg-white rounded-lg shadow-lg p-4 mb-4">
            <div class="flex justify-between items-center mb-2">
                <h2 class="text-lg text-blue-800 font-bold">NAVIGATE DAYS</h2>
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
                            class="flex-shrink-0 mx-1 cursor-pointer {{ $day['is_current'] ? 'border-2 border-blue-600 bg-blue-600 text-white' : '' }}">
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

        
         
          <!-- Reading Stats & Calendar Row -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
              <!-- Reading Stats -->
              <div class="bg-white rounded-lg shadow p-6 col-span-1">
                  <h3 class="font-bold text-lg mb-4 text-gray-700">Reading Stats</h3>
                  <div class="space-y-4">
                      <div class="flex justify-between items-center">
                          <span class="text-gray-600">Current Streak</span>
                          <span class="font-bold text-xl">{{ $userPlan->pivot->current_streak ?? 0 }} days</span>
                      </div>
                      <div class="flex justify-between items-center">
                          <span class="text-gray-600">Completion Rate</span>
                          <span class="font-bold text-xl">{{ number_format($userPlan->pivot->completion_rate ?? 0, 0) }}%</span>
                      </div>
                      <div class="flex justify-between items-center">
                          <span class="text-gray-600">Next Break</span>
                          <span class="font-bold text-xl">{{ $nextBreakDays ?? 0 }} days</span>
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
                      @if($calendarDays && count($calendarDays) > 0)
                          @foreach($calendarDays as $day)
                              <div class="h-10 w-10 rounded-full flex items-center justify-center 
                                  {{ !$day['is_current_month'] ? 'text-gray-400' : 'text-gray-600' }}
                                  {{ $day['is_break_day'] && !$day['is_future'] ? 'bg-gray-200' : '' }}
                                  {{ $day['is_completed'] ? 'bg-green-500 text-white' : '' }}
                                  {{ $day['is_missed'] ? 'bg-red-200 text-red-800' : '' }}
                                  {{ $day['is_today'] ? 'font-bold border-2 border-blue-600' : '' }}">
                                  {{ $day['day'] }}
                              </div>
                          @endforeach
                      @endif
                  </div>
                  <div class="mt-4 flex justify-end space-x-4">
                      <div class="flex items-center">
                          <div class="h-4 w-4 rounded-full bg-green-500 mr-2"></div>
                          <span class="text-xs text-gray-600">Completed</span>
                      </div>
                      <div class="flex items-center">
                          <div class="h-4 w-4 rounded-full bg-red-200 mr-2"></div>
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
          <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="closeCatchUpModal">
              <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white" wire:click.stop>
                  <div class="mt-3">
                      <div class="flex items-center justify-between mb-4">
                          <h3 class="text-lg font-medium text-gray-900">Catch Up on Missed Readings</h3>
                          <button wire:click="closeCatchUpModal" class="text-gray-400 hover:text-gray-600">
                              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                              </svg>
                          </button>
                      </div>
                      
                      @if($missedReadings && count($missedReadings) > 0)
                          <div class="space-y-3 max-h-96 overflow-y-auto">
                              @foreach($missedReadings as $reading)
                                  <div class="border rounded-lg p-4 {{ $selectedReading && $selectedReading->id == $reading->id ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                                      <div class="flex items-center justify-between">
                                                                                      <div class="flex-1">
                                              <h4 class="font-medium text-gray-900">Day {{ $reading->day_number }}</h4>
                                              <p class="text-sm text-gray-600">{{ $reading->reading_range }}</p>
                                              <p class="text-xs text-gray-500 mt-1">
                                                  Assigned: {{ Carbon\Carbon::parse($readingPlan->start_date)->addDays($reading->day_number - 1)->format('M d, Y') }}
                                              </p>
                                          </div>
                                          <div class="flex space-x-2">
                                              <button 
                                                  wire:click="selectReading({{ $reading->id }})"
                                                  class="px-3 py-1 text-sm rounded {{ $selectedReading && $selectedReading->id == $reading->id ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                                  {{ $selectedReading && $selectedReading->id == $reading->id ? 'Selected' : 'Select' }}
                                              </button>
                                          </div>
                                      </div>
                                  </div>
                              @endforeach
                          </div>
                          
                          <div class="mt-6 flex justify-end space-x-3">
                              <button 
                                  wire:click="closeCatchUpModal"
                                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                                  Cancel
                              </button>
                              <button 
                                  wire:click="markPreviousAsComplete"
                                  {{ !$selectedReading ? 'disabled' : '' }}
                                  class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed">
                                  Mark Selected as Complete
                              </button>
                          </div>
                      @else
                          <div class="text-center py-8">
                              <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                              </svg>
                              <h3 class="mt-2 text-sm font-medium text-gray-900">All caught up!</h3>
                              <p class="mt-1 text-sm text-gray-500">You have no missed readings to catch up on.</p>
                          </div>
                      @endif
                  </div>
              </div>
          </div>
      @endif

      <!-- Flash Messages -->
      @if (session()->has('message'))
          <div class="fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded z-50" role="alert">
              <span class="block sm:inline">{{ session('message') }}</span>
          </div>
      @endif

      @if (session()->has('error'))
          <div class="fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded z-50" role="alert">
              <span class="block sm:inline">{{ session('error') }}</span>
          </div>
      @endif

  @else
      <!-- Fallback when userPlan or pivot is null -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
              <div class="flex">
                  <div class="flex-shrink-0">
                      <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                      </svg>
                  </div>
                  <div class="ml-3">
                      <h3 class="text-sm font-medium text-yellow-800">
                          Reading Plan Not Found
                      </h3>
                      <div class="mt-2 text-sm text-yellow-700">
                          <p>It looks like you don't have an active reading plan or there's an issue with your plan data.</p>
                      </div>
                      <div class="mt-4">
                          <div class="-mx-2 -my-1.5 flex">
                              <a href="{{ route('reading-plans.index') }}" class="bg-yellow-50 px-2 py-1.5 rounded-md text-sm font-medium text-yellow-800 hover:bg-yellow-100">
                                  Browse Reading Plans
                              </a>
                              <button wire:click="$refresh" class="ml-3 bg-yellow-50 px-2 py-1.5 rounded-md text-sm font-medium text-yellow-800 hover:bg-yellow-100">
                                  Refresh Page
                              </button>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      </div>
  @endif
</div>

<script>
  // Auto-hide flash messages after 5 seconds
  document.addEventListener('DOMContentLoaded', function() {
      setTimeout(function() {
          const alerts = document.querySelectorAll('[role="alert"]');
          alerts.forEach(function(alert) {
              alert.style.transition = 'opacity 0.5s';
              alert.style.opacity = '0';
              setTimeout(function() {
                  alert.remove();
              }, 500);
          });
      }, 5000);
  });
</script>

