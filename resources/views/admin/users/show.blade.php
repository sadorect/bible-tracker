<x-admin-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">{{ $user->name }}</h1>
                    <p class="text-gray-600">{{ $user->email }}</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('admin.users.edit', $user) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">
                        Edit User
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg">
                        Back to Users
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- User Info -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">User Information</h2>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Name</label>
                                <p class="text-sm text-gray-900">{{ $user->name }}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Email</label>
                                <p class="text-sm text-gray-900">{{ $user->email }}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Role</label>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    @if($user->role === 'admin') bg-red-100 text-red-800
                                    @elseif($user->role === 'leader') bg-blue-100 text-blue-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Status</label>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $user->email_verified_at ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ $user->email_verified_at ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Joined</label>
                                <p class="text-sm text-gray-900">{{ $user->created_at->format('M d, Y') }}</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-500">Last Updated</label>
                                <p class="text-sm text-gray-900">{{ $user->updated_at->format('M d, Y') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Reading Statistics -->
                    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Reading Statistics</h2>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Reading Plans</span>
                                <span class="text-sm font-medium text-gray-900">{{ $readingStats['total_plans'] }}</span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Completed Readings</span>
                                <span class="text-sm font-medium text-gray-900">{{ $readingStats['completed_readings'] }}</span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Completion Rate</span>
                                <span class="text-sm font-medium text-gray-900">{{ $readingStats['completion_rate'] }}%</span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Current Streak</span>
                                <span class="text-sm font-medium text-gray-900">{{ $readingStats['current_streak'] }} days</span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-500">Longest Streak</span>
                                <span class="text-sm font-medium text-gray-900">{{ $readingStats['longest_streak'] }} days</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reading Plans and Activity -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Reading Plans -->
                    <div class="bg-white rounded-lg shadow-md">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Reading Plans</h2>
                        </div>
                        
                        <div class="p-6">
                            @forelse($user->readingPlans as $plan)
                                <div class="border rounded-lg p-4 mb-4 last:mb-0">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="text-lg font-medium text-gray-900">{{ $plan->name }}</h3>
                                            <p class="text-sm text-gray-600 mt-1">{{ $plan->description }}</p>
                                            <div class="mt-2 text-sm text-gray-500">
                                                <span>Joined: {{ $plan->pivot->joined_at ? $plan->pivot->joined_at->format('M d, Y') : 'N/A' }}</span>
                                                <span class="mx-2">•</span>
                                                <span>Current Day: {{ $plan->pivot->current_day }}</span>
                                            </div>
                                        </div>
                                        <span class="px-3 py-1 text-sm text-blue-700 bg-blue-100 rounded-full">
                                            {{ $plan->duration_days }} days
                                        </span>
                                    </div>
                                    
                                    @php
                                        $completedCount = $user->readingProgress()
                                            ->whereHas('dailyReading', function($q) use ($plan) {
                                                $q->where('reading_plan_id', $plan->id);
                                            })->count();
                                        $totalCount = $plan->dailyReadings->count();
                                        $progressPercentage = $totalCount > 0 ? round(($completedCount / $totalCount) * 100, 1) : 0;
                                    @endphp
                                    
                                    <div class="mt-4">
                                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                                            <span>Progress</span>
                                            <span>{{ $completedCount }}/{{ $totalCount }} ({{ $progressPercentage }}%)</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $progressPercentage }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-500 text-center py-8">No reading plans assigned.</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="bg-white rounded-lg shadow-md">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Recent Activity</h2>
                        </div>
                        
                        <div class="divide-y divide-gray-200">
                            @forelse($recentActivity as $activity)
                                <div class="px-6 py-4">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">
                                                Completed: {{ $activity->dailyReading->reading_range }}
                                            </p>
                                            <p class="text-sm text-gray-600">
                                                {{ $activity->dailyReading->readingPlan->name }} - Day {{ $activity->dailyReading->day_number }}
                                            </p>
                                        </div>
                                        <span class="text-sm text-gray-500">
                                            {{ $activity->completed_date->format('M d, Y') }}
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="px-6 py-8 text-center text-gray-500">
                                    No recent activity.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>