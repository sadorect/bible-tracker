<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach($platoons as $platoon)
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">{{ $platoon->name }}</h3>
                <span class="px-3 py-1 text-sm text-primary-700 bg-primary-100 rounded-full">
                    {{ $platoon->members_count }} members
                </span>
            </div>
            <div class="mt-4">
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Reading Progress</span>
                    <span>{{ $platoon->completion_rate }}%</span>
                </div>
                <div class="mt-2 h-2 bg-gray-200 rounded-full">
                    <div class="h-2 bg-primary-500 rounded-full" 
                         style="width: {{ $platoon->completion_rate }}%"></div>
                </div>
            </div>
        </div>
    @endforeach
</div>
