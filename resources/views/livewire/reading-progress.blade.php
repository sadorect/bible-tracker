<div>
    <div class="flex flex-col space-y-4">
        <!-- Today's Reading Toggle -->
        <div class="flex items-center justify-between p-4 bg-white rounded-lg shadow">
            <div>
                <h3 class="text-lg font-medium">Today's Reading</h3>
                <p class="text-sm text-gray-600">Day {{ $dayNumber }}</p>
            </div>
            <button 
                wire:click="markAsComplete"
                class="px-4 py-2 rounded-md {{ $isCompleted 
                    ? 'bg-green-500 hover:bg-green-600' 
                    : 'bg-primary-500 hover:bg-primary-600' }} text-white transition-colors">
                {{ $isCompleted ? 'Completed!' : 'Mark Complete' }}
            </button>
        </div>

        <!-- Progress Calendar -->
        <div class="grid grid-cols-7 gap-2">
            @foreach($progress as $day)
                <div 
                    wire:click="toggleDay({{ $day->day_number }})"
                    class="aspect-square rounded-lg {{ $day->is_completed 
                        ? 'bg-primary-500 text-white' 
                        : 'bg-gray-100 text-gray-600' }} 
                        flex items-center justify-center cursor-pointer hover:opacity-75 transition-opacity">
                    {{ $day->day_number }}
                </div>
            @endforeach
        </div>
    </div>
</div>
