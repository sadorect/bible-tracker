<x-admin-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 flex justify-between items-center">
                <h1 class="text-2xl font-semibold text-gray-900">User Progress: {{ $user->name }}</h1>
                
                <div>
                    <a href="{{ route('admin.progress.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded">
                        Back to Progress Dashboard
                    </a>
                </div>
            </div>
            
            <!-- User Info Card -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="h-16 w-16 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-6">
                        <h2 class="text-xl font-bold text-gray-900">{{ $user->name }}</h2>
                        <p class="text-gray-600">{{ $user->email }}</p>
                        <p class="text-gray-600 mt-1">Member since {{ $user->created_at->format('M d, Y') }}</p>
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
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Current Streak</h3>
                    <p class="text-3xl font-bold text-gray-900">{{ number_format($currentStreak) }} days</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Active Plans</h3>
                    <p class="text-3xl font-bold text-gray-900">{{ $userPlans->where('pivot.is_active', true)->count() }}</p>
                </div>
            </div>
            
            <!-- Chart -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Completion Trend (Last 30 Days)</h2>
                <div style="height: 300px;">
                    <canvas id="userCompletionChart"></canvas>
                </div>
            </div>
            
            <!-- Reading Plans -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Reading Plans</h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Plan Name
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Current Day
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
                            @forelse($planStats as $stat)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="{{ route('admin.progress.plan', $stat['plan']) }}" class="text-blue-600 hover:text-blue-800">
                                            {{ $stat['plan']->name }}
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
                                        <div class="flex items-center">
                                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ min(100, $stat['completion_rate']) }}%"></div>
                                            </div>
                                            <span class="ml-2 text-sm font-medium text-gray-900">{{ number_format($stat['completion_rate'], 1) }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('admin.progress.plan', $stat['plan']) }}" class="text-blue-600 hover:text-blue-800">
                                            View Plan
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        User is not enrolled in any reading plans.
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
                                        Completed <span class="font-medium">{{ $activity->dailyReading->reading_range }}</span>
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        {{ $activity->readingPlan->name }}
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
            const ctx = document.getElementById('userCompletionChart').getContext('2d');
            
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
