<div>
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">{{ $readingPlan->name }} - Progress</h3>
                    <p class="text-gray-600 mt-1">Tracking your reading journey</p>
                </div>
                
                <div>
                    <a href="{{ route('dashboard') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm">
                        Back to Dashboard
                    </a>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                    <p class="text-sm text-gray-500 mb-1">Completion Rate</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $statistics['completion_rate'] }}%</p>
                </div>
                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                    <p class="text-sm text-gray-500 mb-1">Current Streak</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $statistics['current_streak'] }} days</p>
                </div>
                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                    <p class="text-sm text-gray-500 mb-1">Completed</p>
                    <p class="text-2xl font-bold text-green-600">{{ $statistics['completed_days'] }} days</p>
                </div>
                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                    <p class="text-sm text-gray-500 mb-1">Missed</p>
                    <p class="text-2xl font-bold text-red-600">{{ $statistics['missed_days'] }} days</p>
                </div>
            </div>
            
            <!-- Progress Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr>
                            <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Day</th>
                            <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Date</th>
                            <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Reading</th>
                            <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Completed On</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($readingProgress as $progress)
                            <tr>
                                <td class="py-4 px-4 text-sm">Day {{ $progress['day'] }}</td>
                                <td class="py-4 px-4 text-sm">{{ $progress['date'] }}</td>
                                <td class="py-4 px-4 text-sm">
                                    @if($progress['is_break_day'])
                                        <span class="italic text-gray-500">Break Day</span>
                                    @else
                                        {{ $progress['reading'] }}
                                    @endif
                                </td>
                                <td class="py-4 px-4 text-sm">
                                    @if($progress['is_future'])
                                        <span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded-full">Upcoming</span>
                                    @elseif($progress['is_break_day'])
                                        <span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded-full">Break Day</span>
                                    @elseif($progress['completed'])
                                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Completed</span>
                                    @else
                                        <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">Missed</span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 text-sm">
                                    @if($progress['completed'])
                                        {{ Carbon\Carbon::parse($progress['completed_date'])->format('M d, Y') }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>