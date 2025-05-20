<div class="space-y-8">
    <!-- Team Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900">Today's Chapter</h3>
            <p class="text-3xl font-bold text-primary-600">Day {{ $currentDay }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900">Team Members</h3>
            <p class="text-3xl font-bold text-primary-600">{{ $teamStats['memberCount'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900">Completed Today</h3>
            <p class="text-3xl font-bold text-primary-600">{{ $teamStats['completedToday'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900">Team Progress</h3>
            <p class="text-3xl font-bold text-primary-600">{{ $teamStats['overallProgress'] }}%</p>
        </div>
    </div>

    <!-- Members Reading Progress -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Members Progress</h2>
            
            <div class="space-y-4">
                @foreach($members as $member)
                    <div class="border rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                                    <span class="text-primary-600 font-medium">
                                        {{ substr($member->name, 0, 2) }}
                                    </span>
                                </div>
                                <div>
                                    <h4 class="font-medium">{{ $member->name }}</h4>
                                    <p class="text-sm text-gray-600">{{ $member->phone_number }}</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-8">
                                <div>
                                    <p class="text-sm text-gray-600">Today's Status</p>
                                    @if($member->hasCompletedToday)
                                        <span class="text-green-600">Completed</span>
                                    @else
                                        <span class="text-red-600">Pending</span>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Overall Progress</p>
                                    <div class="w-32 h-2 bg-gray-200 rounded-full">
                                        <div class="h-2 bg-primary-500 rounded-full" 
                                             style="width: {{ $member->progress_percentage }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
