<x-app-layout>
  <x-slot name="header">
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          {{ __('Reading Progress') }}
      </h2>
  </x-slot>

  <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6 bg-white border-b border-gray-200">
                  <h3 class="text-lg font-medium text-gray-900 mb-4">Today's Reading</h3>
                  
                  @if($todayReading->is_break_day)
                      <div class="text-center py-8">
                          <h1 class="text-4xl font-bold text-gray-800 mb-2">Break Day</h1>
                          <p class="text-gray-600">Take a day to reflect on your readings so far</p>
                      </div>
                  @else
                      <div class="text-center py-8">
                          <h1 class="text-4xl font-bold text-gray-800 mb-2">{{ $todayReading->reading_range }}</h1>
                          <p class="text-gray-600">{{ $readingPlan->type == 'old_testament' ? 'Old Testament' : 'New Testament' }} Reading Plan</p>
                      </div>
                  @endif
              </div>

              <!-- Your Reading Plans -->

<div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Your Reading Plans</h3>
                    
                    @if($readingPlans->isEmpty())
                        <div class="text-center py-8">
                            <p class="text-gray-600 mb-4">You haven't joined any reading plans yet.</p>
                            <a href="{{ route('reading-plans.index') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm">
                                Browse Reading Plans
                            </a>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach($readingPlans as $plan)
                                <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-200">
                                    <div class="p-6">
                                        <div class="flex justify-between items-start mb-4">
                                            <h4 class="text-xl font-bold">{{ $plan->name }}</h4>
                                            @if($plan->pivot->is_active)
                                                <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Active</span>
                                            @endif
                                        </div>
                                        
                                        <div class="mb-4">
                                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                                <span>Progress</span>
                                                <span>{{ number_format($plan->pivot->completion_rate, 0) }}%</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $plan->pivot->completion_rate }}%"></div>
                                            </div>
                                        </div>
                                        
                                        <div class="grid grid-cols-2 gap-4 mb-4">
                                            <div>
                                                <p class="text-sm text-gray-600">Current Day</p>
                                                <p class="font-bold">{{ $plan->pivot->current_day }} / {{ $plan->duration_days }}</p>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-600">Current Streak</p>
                                                <p class="font-bold">{{ $plan->pivot->current_streak }} days</p>
                                            </div>
                                        </div>
                                        
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-500">Joined {{ \Carbon\Carbon::parse($plan->pivot->joined_date)->format('M d, Y') }}</span>
                                            <a href="{{ route('progress.view', ['plan_id' => $plan->id]) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                View Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>