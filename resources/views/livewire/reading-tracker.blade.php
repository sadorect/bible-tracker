<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-2xl font-bold text-gray-900 mb-4">
        {{ $testament === 'new' ? 'New Testament (9 chapters daily)' : 'Old Testament (8 chapters daily)' }}
    </h2>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Progress Stats -->
        <div class="bg-primary-50 p-4 rounded-lg">
            <p class="text-sm text-gray-600">Current Day</p>
            <p class="text-2xl font-bold text-primary-600">{{ $currentDay }}/{{ $totalDays }}</p>
            <div class="mt-2 h-2 bg-gray-200 rounded-full">
                <div class="h-2 bg-primary-500 rounded-full transition-all duration-500" 
                     style="width: {{ ($currentDay / $totalDays) * 100 }}%"></div>
            </div>
        </div>

        <!-- Completion Rate -->
        <div class="bg-primary-50 p-4 rounded-lg">
            <p class="text-sm text-gray-600">Completion Rate</p>
            <p class="text-2xl font-bold text-primary-600">{{ number_format($completionRate, 1) }}%</p>
            <p class="text-sm text-gray-600 mt-2">
                {{ $currentDay > 1 ? ($currentDay - 1) . ' days completed' : 'Starting journey' }}
            </p>
        </div>

        <!-- Today's Reading -->
        <div class="bg-primary-50 p-4 rounded-lg">
            <p class="text-sm text-gray-600">Today's Chapters</p>
            <div class="space-y-2">
                @foreach($todayChapters->groupBy('book_name') as $book => $chapters)
                    <p class="text-primary-600">
                        <span class="font-medium">{{ $book }}</span>
                        <span class="text-sm">
                            {{ $chapters->pluck('chapter_number')->implode(', ') }}
                        </span>
                    </p>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Action Button -->
    <button 
        wire:click="markAsComplete"
        wire:loading.attr="disabled"
        class="w-full btn-primary transition-all duration-300 transform hover:scale-[1.02]">
        <span wire:loading.remove>Mark Today's Reading as Complete</span>
        <span wire:loading>Recording Progress...</span>
    </button>

    <!-- Success Message -->
    <div x-data="{ show: false }" 
         x-show="show" 
         x-init="@this.on('readingCompleted', () => { show = true; setTimeout(() => show = false, 3000) })"
         class="mt-4 p-3 bg-green-50 text-green-700 rounded-md text-center"
         x-transition>
        Reading progress recorded successfully!
    </div>
</div>
