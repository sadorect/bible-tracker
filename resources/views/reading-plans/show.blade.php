<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $readingPlan->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">{{ $readingPlan->name }}</h3>
                            <p class="text-gray-600 mt-2">{{ $readingPlan->description }}</p>
                        </div>
                     <!-- Add this near the top of the content section -->
@if (session('success'))
<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
    <p>{{ session('success') }}</p>
</div>
@endif

@if (session('info'))
<div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6" role="alert">
    <p>{{ session('info') }}</p>
</div>
@endif

                        @php
                            $userPlan = Auth::user()->readingPlans()->where('reading_plan_id', $readingPlan->id)->first();
                            $isActive = $userPlan && $userPlan->pivot->is_active;
                            $isJoined = $userPlan !== null;
                        @endphp
                        
                        <div>
                            @if($isActive)
                                <div class="flex space-x-2">
                                    <a href="{{ route('dashboard') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm">Go to Dashboard</a>
                                    <form action="{{ route('reading-plans.leave', $readingPlan) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm">Leave Plan</button>
                                    </form>
                                </div>
                            @elseif($isJoined)
                                <form action="{{ route('reading-plans.join', $readingPlan) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm">Resume Plan</button>
                                </form>
                            @else
                                <form action="{{ route('reading-plans.join', $readingPlan) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm">Join Plan</button>
                                </form>
                            @endif
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-500 mb-1">Duration</p>
                            <p class="text-xl font-bold text-gray-900">{{ $readingPlan->duration_days }} days</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-500 mb-1">Reading Streak</p>
                            <p class="text-xl font-bold text-gray-900">{{ $readingPlan->streak_days }} days</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-500 mb-1">Break Days</p>
                            <p class="text-xl font-bold text-gray-900">{{ $readingPlan->break_days }} days</p>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Reading Schedule</h4>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white">
                                <thead>
                                    <tr>
                                        <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Day</th>
                                        <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Reading</th>
                                        <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Type</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($readingPlan->dailyReadings()->orderBy('day_number')->take(10)->get() as $reading)
                                        <tr>
                                            <td class="py-4 px-4 text-sm">Day {{ $reading->day_number }}</td>
                                            <td class="py-4 px-4 text-sm">{{ $reading->reading_range }}</td>
                                            <td class="py-4 px-4 text-sm">
                                                @if($reading->is_break_day)
                                                <span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded-full">Break Day</span>
                                                @else
                                                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">Reading Day</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        @if($readingPlan->dailyReadings()->count() > 10)
                            <div class="mt-4 text-center">
                                <p class="text-gray-600 text-sm">Showing first 10 days of the reading plan.</p>
                            </div>
                        @endif
                    </div>
                    
                    <div class="mt-8">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">About This Plan</h4>
                        <div class="prose max-w-none">
                            <p>{{ $readingPlan->description }}</p>
                            
                            @if($readingPlan->additional_info)
                                <div class="mt-4">
                                    {!! $readingPlan->additional_info !!}
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="mt-8">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Community</h4>
                        <div class="bg-gray-50 p-6 rounded-lg">
                            <p class="text-gray-700">
                                <span class="font-medium">{{ $readingPlan->users()->count() }}</span> people are currently following this reading plan.
                            </p>
                            
                            @if($readingPlan->users()->count() > 0)
                                <div class="mt-4">
                                    <p class="text-sm text-gray-600 mb-2">Recent participants:</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($readingPlan->users()->latest('user_reading_plans.created_at')->take(5)->get() as $user)
                                            <div class="bg-white px-3 py-1 rounded-full text-sm border border-gray-200">
                                                {{ $user->name }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex justify-between">
                <a href="{{ route('reading-plans.index') }}" class="text-blue-600 hover:text-blue-800">
                    &larr; Back to Reading Plans
                </a>
                
                @if($isActive)
                    <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-800">
                        Go to Dashboard &rarr;
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
