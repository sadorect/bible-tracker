<div class="space-y-8">
    <!-- Squad Performance Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900">Total Batches</h3>
            <p class="text-3xl font-bold text-primary-600">{{ $squadStats['batchCount'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900">Total Teams</h3>
            <p class="text-3xl font-bold text-primary-600">{{ $squadStats['teamCount'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900">Total Members</h3>
            <p class="text-3xl font-bold text-primary-600">{{ $squadStats['memberCount'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900">Completion Rate</h3>
            <p class="text-3xl font-bold text-primary-600">{{ $squadStats['completionRate'] }}%</p>
        </div>
    </div>

    <!-- Batch List -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-900">Batches Overview</h2>
                <button class="btn-primary">Add New Batch</button>
            </div>
            
            <div class="space-y-6">
                @foreach($batches as $batch)
                    <div class="border rounded-lg p-6">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="text-lg font-medium">{{ $batch->name }}</h3>
                                <p class="text-sm text-gray-600">Batch Leader: {{ $batch->leader->name }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-600">Teams: {{ $batch->teams_count }}</p>
                                <p class="text-sm text-gray-600">Members: {{ $batch->total_members }}</p>
                            </div>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="mt-4">
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span>Reading Progress</span>
                                <span>{{ $batch->progress_percentage }}%</span>
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full">
                                <div class="h-2 bg-primary-500 rounded-full" 
                                     style="width: {{ $batch->progress_percentage }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
