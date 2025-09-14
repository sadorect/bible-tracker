<x-admin-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h1 class="text-2xl font-semibold text-gray-900 mb-6">Admin Dashboard</h1>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-users text-2xl text-blue-500"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Total Users</h3>
                            <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_users']) }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-user-check text-2xl text-green-500"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Active Users</h3>
                            <p class="text-2xl font-bold text-green-600">{{ number_format($stats['active_users']) }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-book text-2xl text-purple-500"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Reading Plans</h3>
                            <p class="text-2xl font-bold text-purple-600">{{ number_format($stats['total_plans']) }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-orange-500">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-2xl text-orange-500"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Today's Completions</h3>
                            <p class="text-2xl font-bold text-orange-600">{{ number_format($stats['today_completions']) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Secondary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Week Completions</h3>
                    <p class="text-2xl font-bold text-indigo-600">{{ number_format($stats['this_week_completions']) }}</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Month Completions</h3>
                    <p class="text-2xl font-bold text-teal-600">{{ number_format($stats['this_month_completions']) }}</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Inactive Users</h3>
                    <p class="text-2xl font-bold text-red-600">{{ number_format($stats['inactive_users']) }}</p>
                </div>
            </div>

            <!-- Dashboard Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Recent Activity -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-clock mr-2 text-blue-500"></i>
                        Recent Activity
                    </h2>
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        @forelse($recentActivity as $activity)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center">
                                    <img 
                                        class="w-8 h-8 rounded-full mr-3" 
                                        src="https://ui-avatars.com/api/?name={{ urlencode($activity->user->name) }}&background=3B82F6&color=fff" 
                                        alt="{{ $activity->user->name }}"
                                    >
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $activity->user->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $activity->dailyReading->readingPlan->name ?? 'Unknown Plan' }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-green-600 font-medium">Completed</p>
                                    <p class="text-xs text-gray-500">{{ $activity->completed_date->diffForHumans() }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500 text-center py-4">No recent activity</p>
                        @endforelse
                    </div>
                </div>

                <!-- Top Performers This Week -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-trophy mr-2 text-yellow-500"></i>
                        Top Performers This Week
                    </h2>
                    <div class="space-y-3">
                        @forelse($topPerformers as $index => $performer)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-r from-yellow-400 to-yellow-600 flex items-center justify-center text-white font-bold text-sm mr-3">
                                        {{ $index + 1 }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $performer->name }}</p>
                                        <p class="text-xs text-gray-500 capitalize">{{ $performer->role }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-green-600">{{ $performer->reading_progress_count }}</p>
                                    <p class="text-xs text-gray-500">completions</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500 text-center py-4">No activity this week</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Popular Reading Plans -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-chart-bar mr-2 text-purple-500"></i>
                    Most Engaging Plans (Last 30 Days)
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($popularPlans as $plan)
                        <div class="p-4 border border-gray-200 rounded-lg hover:shadow-md transition">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-medium text-gray-900 text-sm">{{ $plan->name }}</h3>
                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                                    {{ $plan->type }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-lg font-bold text-purple-600">{{ $plan->reading_progress_count }}</span>
                                <span class="text-xs text-gray-500">completions</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 col-span-3 text-center py-4">No activity in the last 30 days</p>
                    @endforelse
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <a href="{{ route('admin.reading-plans.index') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg text-center transition">
                        Manage Reading Plans
                    </a>
                    <a href="{{ route('admin.progress.index') }}" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg text-center transition">
                        View Progress Reports
                    </a>
                    <a href="{{route('admin.users.index')}}" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-4 rounded-lg text-center transition">
                        Manage Users
                    </a>
                    <a href="#" class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-3 px-4 rounded-lg text-center transition">
                        Send Messages
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
