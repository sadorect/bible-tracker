<x-app-layout>
  <x-slot name="slot">
      <!-- Stats Overview -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <div class="bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl p-6 text-white shadow-lg">
              <div class="flex items-center">
                  <div class="rounded-full bg-white/20 p-3">
                      <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                          <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/>
                      </svg>
                  </div>
                  <div class="ml-4">
                      <h3 class="text-lg font-semibold">Current Progress</h3>
                      <p class="text-3xl font-bold mt-2">{{ number_format($newTestamentProgress['completion_rate'], 1) }}%</p>
                      <p class="text-sm opacity-80">New Testament</p>
                  </div>
              </div>
          </div>

          <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl p-6 text-white shadow-lg">
              <div class="flex items-center">
                  <div class="rounded-full bg-white/20 p-3">
                      <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                          <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"/>
                          <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"/>
                      </svg>
                  </div>
                  <div class="ml-4">
                      <h3 class="text-lg font-semibold">Days Completed</h3>
                      <p class="text-3xl font-bold mt-2">{{ $newTestamentProgress['current_day'] - 1 }}</p>
                      <p class="text-sm opacity-80">of {{ $newTestamentProgress['total_days'] }} Days</p>
                  </div>
              </div>
          </div>

          <div class="col-span-2 bg-white rounded-xl shadow-lg p-6">
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Today's Reading Plan</h3>
              <div class="space-y-4">
                  <div class="flex items-center justify-between p-4 bg-primary-50 rounded-lg">
                      <div>
                          <p class="text-sm text-primary-600 font-medium">New Testament</p>
                          <p class="text-lg font-semibold text-gray-800 mt-1">{{ $newTestamentProgress['today_chapters'] }}</p>
                      </div>
                      <button class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                          Mark Complete
                      </button>
                  </div>
                  <div class="flex items-center justify-between p-4 bg-amber-50 rounded-lg">
                      <div>
                          <p class="text-sm text-amber-600 font-medium">Old Testament</p>
                          <p class="text-lg font-semibold text-gray-800 mt-1">{{ $oldTestamentProgress['today_chapters'] }}</p>
                      </div>
                      <button class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition">
                          Mark Complete
                      </button>
                  </div>
              </div>
          </div>
      </div>

      <!-- Reading History -->
      <div class="bg-white rounded-xl shadow-lg p-6">
          <h3 class="text-lg font-semibold text-gray-800 mb-6">Recent Activity</h3>
          <div class="space-y-4">
              @foreach(range(1, 5) as $day)
                  <div class="flex items-center justify-between border-b pb-4">
                      <div class="flex items-center">
                          <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center">
                              <span class="text-primary-600 font-semibold">D{{ $day }}</span>
                          </div>
                          <div class="ml-4">
                              <p class="text-sm font-medium text-gray-900">Day {{ $day }}</p>
                              <p class="text-sm text-gray-500">Completed on {{ now()->subDays($day)->format('M d, Y') }}</p>
                          </div>
                      </div>
                      <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">Completed</span>
                  </div>
              @endforeach
          </div>
      </div>

      <!-- Hierarchy Tree View -->
      <div class="bg-white rounded-xl shadow-lg p-6 mt-8">
          <h3 class="text-lg font-semibold text-gray-800 mb-6">Team Structure</h3>
          
          @if(auth()->user()->role === 'platoon_leader')
              <div class="space-y-6">
                  @foreach($hierarchyData['squads'] as $squad)
                      <div class="border-l-4 border-primary-500 pl-4">
                          <div class="flex items-center justify-between bg-gray-50 p-4 rounded-lg">
                              <div>
                                  <h4 class="font-medium text-gray-900">{{ $squad->name }}</h4>
                                  <p class="text-sm text-gray-600">Led by {{ $squad->leader->name }}</p>
                              </div>
                              <span class="px-3 py-1 bg-primary-100 text-primary-800 rounded-full text-sm">
                                  {{ $squad->members->count() }} members
                              </span>
                          </div>
                          
                          <div class="ml-6 mt-4 space-y-4">
                              @foreach($squad->batches as $batch)
                                  <div class="border-l-4 border-amber-500 pl-4">
                                      <div class="flex items-center justify-between bg-gray-50 p-4 rounded-lg">
                                          <div>
                                              <h5 class="font-medium text-gray-900">{{ $batch->name }}</h5>
                                              <p class="text-sm text-gray-600">Led by {{ $batch->leader->name }}</p>
                                          </div>
                                          <span class="px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-sm">
                                              {{ $batch->members->count() }} members
                                          </span>
                                      </div>
                                      
                                      <div class="ml-6 mt-4 space-y-4">
                                          @foreach($batch->teams as $team)
                                              <div class="border-l-4 border-green-500 pl-4">
                                                  <div class="flex items-center justify-between bg-gray-50 p-4 rounded-lg">
                                                      <div>
                                                          <h6 class="font-medium text-gray-900">{{ $team->name }}</h6>
                                                          <p class="text-sm text-gray-600">Led by {{ $team->leader->name }}</p>
                                                      </div>
                                                      <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">
                                                          {{ $team->members->count() }} members
                                                      </span>
                                                  </div>
                                              </div>
                                          @endforeach
                                      </div>
                                  </div>
                              @endforeach
                          </div>
                      </div>
                  @endforeach
              </div>
          @endif
      </div>
  </x-slot>
</x-app-layout>
