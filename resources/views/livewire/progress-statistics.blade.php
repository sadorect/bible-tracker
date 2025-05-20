<div class="grid grid-cols-1 md:grid-cols-4 gap-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-medium text-gray-900">Completed Days</h3>
        <p class="text-3xl font-bold text-primary-600">{{ $stats['completedDays'] }}</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-medium text-gray-900">Current Streak</h3>
        <p class="text-3xl font-bold text-primary-600">{{ $stats['currentStreak'] }} days</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-medium text-gray-900">Total Progress</h3>
        <div class="mt-2">
            <div class="flex justify-between text-sm">
                <span>{{ number_format($stats['totalProgress'], 1) }}%</span>
                <span>100%</span>
            </div>
            <div class="mt-1 h-2 bg-gray-200 rounded-full">
                <div class="h-2 bg-primary-500 rounded-full" 
                     style="width: {{ $stats['totalProgress'] }}%"></div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-medium text-gray-900">This Week</h3>
        <p class="text-3xl font-bold text-primary-600">{{ $stats['weeklyProgress'] }}/7</p>
    </div>
</div>
