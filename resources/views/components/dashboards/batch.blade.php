<div class="space-y-8">
    <!-- Batch Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900">Teams</h3>
            <div class="mt-2 flex justify-between items-end">
                <p class="text-3xl font-bold text-primary-600">{{ $batchStats['teamCount'] }}</p>
                <button class="text-sm text-primary-600 hover:text-primary-800">Manage Teams</button>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900">Active Members</h3>
            <p class="text-3xl font-bold text-primary-600">{{ $batchStats['activeMembers'] }}/{{ $batchStats['totalMembers'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900">Today's Progress</h3>
            <p class="text-3xl font-bold text-primary-600">{{ $batchStats['todayProgress'] }}%</p>
        </div>
    </div>

    <!-- Teams Overview -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-900">Teams Performance</h2>
                <div class="flex space-x-4">
                    <button class="btn-primary">Add Team</button>
                    <button class="btn-secondary">Export Report</button>
                </div>
            </div>

            <div class="space-y-6">
                @foreach($teams as $team)
                    <div class="border rounded-lg p-6 hover:border-primary-500 transition-colors">
                        <div class="grid grid-cols-4 gap-4">
                            <div>
                                <h3 class="text-lg font-medium">{{ $team->name }}</h3>
                                <p class="text-sm text-gray-600">Led by: {{ $team->leader->name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Members</p>
                                <p class="text-lg font-medium">{{ $team->members_count }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Today's Active</p>
                                <p class="text-lg font-medium">{{ $team->active_today_count }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Overall Progress</p>
                                <div class="mt-2 h-2 bg-gray-200 rounded-full">
                                    <div class="h-2 bg-primary-500 rounded-full" 
                                         style="width: {{ $team->progress_percentage }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
