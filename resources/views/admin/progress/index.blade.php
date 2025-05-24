<x-admin-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h1 class="text-2xl font-semibold text-gray-900 mb-6">User Reading Progress</h1>
            
            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Filters</h2>
                
                <form action="{{ route('admin.progress.index') }}" method="GET" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">User</label>
                            <select name="user_id" id="user_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                <option value="">All Users</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ $userId == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label for="plan_id" class="block text-sm font-medium text-gray-700 mb-1">Reading Plan</label>
                            <select name="plan_id" id="plan_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                <option value="">All Plans</option>
                                @foreach($readingPlans as $plan)
                                    <option value="{{ $plan->id }}" {{ $planId == $plan->id ? 'selected' : '' }}>
                                        {{ $plan->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label for="date_range" class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                            <select name="date_range" id="date_range" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                                <option value="all" {{ $dateRange == 'all' ? 'selected' : '' }}>All Time</option>
                                <option value="today" {{ $dateRange == 'today' ? 'selected' : '' }}>Today</option>
                                <option value="yesterday" {{ $dateRange == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                                <option value="this_week" {{ $dateRange == 'this_week' ? 'selected' : '' }}>This Week</option>
                                <option value="last_week" {{ $dateRange == 'last_week' ? 'selected' : '' }}>Last Week</option>
                                <option value="this_month" {{ $dateRange == 'this_month' ? 'selected' : '' }}>This Month</option>
                                <option value="last_month" {{ $dateRange == 'last_month' ? 'selected' : '' }}>Last Month</option>
                                <option value="custom" {{ $dateRange == 'custom' ? 'selected' : '' }}>Custom Range</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="custom_date_range" class="grid grid-cols-1 md:grid-cols-2 gap-4 {{ $dateRange == 'custom' ? '' : 'hidden' }}">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                            <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        </div>
                        
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        </div>
                    </div>
                    
                    <div class="flex justify-between">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Apply Filters
                        </button>
                        
                        <a href="{{ route('admin.progress.export', request()->query()) }}" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                            Export CSV
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Total Completions</h3>
                    <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_completions']) }}</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Active Users</h3>
                    <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['active_users']) }}</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Active Plans</h3>
                    <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['active_plans']) }}</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Avg. Completions/User</h3>
                    <p class="text-3xl font-bold text-gray-900">
                        {{ $stats['active_users'] > 0 ? number_format($stats['total_completions'] / $stats['active_users'], 1) : '0' }}
                    </p>
                </div>
            </div>
            
            <!-- Chart -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Completion Trend</h2>
                <div style="height: 300px;">
                    <canvas id="completionChart"></canvas>
                </div>
            </div>
            
            <!-- Top Users and Plans -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Top Users</h2>
                    
                    @if($stats['completions_by_user']->isNotEmpty())
                        <div class="space-y-4">
                            @foreach($stats['completions_by_user'] as $userStat)
                                <div class="flex justify-between items-center">
                                    <a href="{{ route('admin.progress.user', ['user' => $userStat->id]) }}" class="text-blue-600 hover:text-blue-800">
                                        {{ $userStat->name }}
                                    </a>
                                    <span class="font-medium">{{ number_format($userStat->count) }} completions</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-4">No data available</p>
                    @endif
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Top Reading Plans</h2>
                    
                    @if($stats['completions_by_plan']->isNotEmpty())
                        <div class="space-y-4">
                            @foreach($stats['completions_by_plan'] as $planStat)
                                <div class="flex justify-between items-center">
                                    <a href="{{ route('admin.progress.plan', ['readingPlan' => $planStat->id]) }}" class="text-blue-600 hover:text-blue-800">
                                        {{ $planStat->name }}
                                    </a>
                                    <span class="font-medium">{{ number_format($planStat->count) }} completions</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-4">No data available</p>
                    @endif
                </div>
            </div>
            
                       <!-- Progress Table -->
                       <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Recent Reading Progress</h2>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            User
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Reading Plan
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Reading
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Completed Date
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse($progress as $record)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="{{ route('admin.progress.user', $record->user) }}" class="text-blue-600 hover:text-blue-800">
                                                    {{ $record->user->name }}
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="{{ route('admin.progress.plan', $record->readingPlan) }}" class="text-blue-600 hover:text-blue-800">
                                                    {{ $record->readingPlan->name }}
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                {{ $record->dailyReading->reading_range }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                {{ Carbon\Carbon::parse($record->completed_date)->format('M d, Y g:i A') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="{{ route('admin.progress.user', $record->user) }}" class="text-blue-600 hover:text-blue-800 mr-3">
                                                    View User
                                                </a>
                                                <a href="{{ route('admin.progress.plan', $record->readingPlan) }}" class="text-blue-600 hover:text-blue-800">
                                                    View Plan
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                                No reading progress records found.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="px-6 py-4 border-t border-gray-200">
                            {{ $progress->links() }}
                        </div>
                    </div>
                </div>
            </div>
            
            @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                // Toggle custom date range inputs
                document.getElementById('date_range').addEventListener('change', function() {
                    const customDateRange = document.getElementById('custom_date_range');
                    if (this.value === 'custom') {
                        customDateRange.classList.remove('hidden');
                    } else {
                        customDateRange.classList.add('hidden');
                    }
                });
                
                // Initialize chart
                document.addEventListener('DOMContentLoaded', function() {
                    const ctx = document.getElementById('completionChart').getContext('2d');
                    
                    const labels = {!! $stats['chart_labels'] !!};
                    const data = {!! $stats['chart_data'] !!};
                    
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Reading Completions',
                                data: data,
                                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                                borderColor: 'rgba(59, 130, 246, 1)',
                                borderWidth: 2,
                                tension: 0.3,
                                pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0
                                    }
                                }
                            }
                        }
                    });
                });
            </script>
            @endpush
        </x-admin-layout>
        