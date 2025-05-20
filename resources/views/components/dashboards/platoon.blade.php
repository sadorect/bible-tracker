<div class="space-y-6">
    <!-- Overview Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900">Total Members</h3>
            <p class="text-3xl font-bold text-primary-600">{{ $progressStats['totalMembers'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900">Active Today</h3>
            <p class="text-3xl font-bold text-primary-600">{{ $progressStats['activeToday'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900">Overall Progress</h3>
            <p class="text-3xl font-bold text-primary-600">{{ $progressStats['overallProgress'] }}%</p>
        </div>
    </div>

    <!-- Squads List -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-900">Squads Overview</h2>
            <div class="mt-6 space-y-4">
                @foreach($squads as $squad)
                    <div class="border rounded-lg p-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-medium">{{ $squad->name }}</h3>
                                <p class="text-sm text-gray-600">Led by: {{ $squad->leader->name }}</p>
                            </div>
                            <button class="text-primary-600 hover:text-primary-800">
                                View Details
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
