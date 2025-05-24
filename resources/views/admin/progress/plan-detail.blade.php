<x-admin-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900">Plan Progress: {{ $readingPlan->name }}</h1>
                
                <div>
                    <a href="{{ route('admin.progress.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded">
                        Back to Progress Dashboard
                    </a>
                </div>
            </div>
            
            <!-- Plan Info Card -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex flex-col md:flex-row md:justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">{{ $readingPlan->name }}</h2>
                        <p class="text-gray-600 mt-1">{{ $readingPlan->description }}</p>
                        <p class="text-gray-600 mt-1">
                            <span class="font-medium">Type:</span> {{ ucfirst(str_replace('_', ' ', $readingPlan->type)) }}
                        </p>
                    </div>
                    <div class="mt-4 md:mt-0 md:text-right">
                        <p class="text-gray-600">
                            <span class="font-medium">Started:</span> {{ Carbon\Carbon::parse($readingPlan->start_date)->format('M d, Y') }}
                        </p>
                        <p class="text-gray-600">
                            <span class="font-medium">Reading Pattern:</span> {{ $readingPlan->streak_days }} days on, {{ $readingPlan->break_days }} days off
                        </p>
                        <p class="text-gray-600">
                            <span class="font-medium">Status:</span> 
                            @if($readingPlan->is_active)
                                <span class="text-green-600">Active</span>
                            @else
                                <span class="text-red-600">Inactive</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Total Completions</h3>
                    <p class="text-3xl font-bold text-gray-900">{{ number_format($totalCompletions) }}</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Total Users</h3>
                    <p class="text-3xl font-bold text-gray-900">{{ number_format($totalUsers) }}</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Active Users</h3>
                    <p class="text-3xl font-bold text-gray-900">{{ number_format($activeUsers) }}</p>
                </div>
            </div>
            
            <!-- Chart -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Completion Trend (Last 30 Days)</h2>
                <div style="height: 300px;">
                    <canvas id="planCompletionChart"></canvas>
                </div>
            </div>
            
            <!-- User Progress Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">User Progress</h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    User
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Current Day
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Current Streak
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Completion Rate
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($userStats as $stat)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="{{ route('admin.progress.user', $stat['user']) }}" class="text-blue-600 hover:text-blue-800">
                                            {{ $stat['user']->name }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($stat['is_active'])
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Active
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                Inactive
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        Day {{ $stat['current_day'] }} of {{ $stat['total_days'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ $stat['current_streak'] }} days
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ min(100, $stat['completion_rate']) }}%"></div>
                                            </div>
                                            <span class="ml-2 text-sm font-medium text-gray-900">{{ number_format($stat['completion_rate'], 1) }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('admin.progress.user', $stat['user']) }}" class="text-blue-600 hover:text-blue-800">
                                            View User
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        No users are enrolled in this reading plan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Recent Activity</h2>
                </div>
                
                <div class="divide-y divide-gray-200">
                    @forelse($recentActivity as $activity)
                        <div class="px-6 py-4">
                            <div class="flex justify-between">
                                <div>
                                    <p class="text-gray-900">
                                        <a href="{{ route('admin.progress.user', $activity->user) }}" class="font-medium text-blue-600 hover:text-blue-800">
                                            {{ $activity->user->name }}
                                        </a> 
                                        completed <span class="font-medium">{{ $activity->dailyReading->reading_range }}</span>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-gray-900">
                                        {{ Carbon\Carbon::parse($activity->completed_date)->format('M d, Y') }}
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        {{ Carbon\Carbon::parse($activity->completed_date)->format('g:i A') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-4 text-center text-gray-500">
                            No recent activity found.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('planCompletionChart').getContext('2d');
            
            const labels = {!! $chartLabels !!};
            const data = {!! $chartData !!};
            
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