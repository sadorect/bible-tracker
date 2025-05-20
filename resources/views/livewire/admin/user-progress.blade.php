<div>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="mb-6">
          <h1 class="text-2xl font-bold text-gray-800">Manage User Progress</h1>
          <p class="text-gray-600">Monitor and manage user reading progress</p>
      </div>
      
      <div class="bg-white rounded-lg shadow-md p-6 mb-6">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
              <!-- Reading Plan Selector -->
              <div class="w-full md:w-1/3">
                  <label for="plan-selector" class="block text-sm font-medium text-gray-700 mb-1">Select Reading Plan</label>
                  <select
                      id="plan-selector"
                      wire:model.live="selectedPlan"
                      class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                  >
                      @foreach($readingPlans as $plan)
                          <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                      @endforeach
                  </select>
              </div>
              
              <!-- Search Box -->
              <div class="w-full md:w-1/3">
                  <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search Users</label>
                  <input
                      type="text"
                      id="search"
                      wire:model.live.debounce.300ms="searchTerm"
                      placeholder="Search by name or email"
                      class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                  >
              </div>
              
              <!-- Action Button -->
              <div class="w-full md:w-1/3 flex items-end justify-end">
                  <button
                      wire:click="sendReminderToAll"
                      class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md shadow-sm"
                  >
                      Send Reminder to All
                  </button>
              </div>
          </div>
          
          <!-- Selected Plan Info -->
          @if($selectedPlanDetails)
              <div class="bg-blue-50 rounded-md p-4 mb-6">
                  <h2 class="font-medium text-blue-800">{{ $selectedPlanDetails->name }}</h2>
                  <div class="mt-2 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                      <div>
                          <span class="text-gray-600">Started:</span>
                          <span class="font-medium">{{ $selectedPlanDetails->start_date->format('M d, Y') }}</span>
                      </div>
                      <div>
                          <span class="text-gray-600">Type:</span>
                          <span class="font-medium">{{ ucfirst($selectedPlanDetails->type) }}</span>
                      </div>
                      <div>
                          <span class="text-gray-600">Chapters/Day:</span>
                          <span class="font-medium">{{ $selectedPlanDetails->chapters_per_day }}</span>
                      </div>
                      <div>
                          <span class="text-gray-600">Schedule:</span>
                          <span class="font-medium">{{ $selectedPlanDetails->streak_days }} days on, {{ $selectedPlanDetails->break_days }} day off</span>
                      </div>
                  </div>
              </div>
          @endif
          
          <!-- User List Table -->
          <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                      <tr>
                          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                              User
                          </th>
                          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                              Current Day
                          </th>
                          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                              Streak
                          </th>
                          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                              Completion Rate
                          </th>
                          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                              Actions
                          </th>
                      </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                      @forelse($users as $user)
                          <tr>
                              <td class="px-6 py-4 whitespace-nowrap">
                                  <div class="flex items-center">
                                      <div class="flex-shrink-0 h-10 w-10">
                                          <img class="h-10 w-10 rounded-full" src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}" alt="">
                                      </div>
                                      <div class="ml-4">
                                          <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                          <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                      </div>
                                  </div>
                              </td>
                              <td class="px-6 py-4 whitespace-nowrap">
                                  <div class="text-sm text-gray-900">Day {{ $user->readingPlans->first()->pivot->current_day }}</div>
                              </td>
                              <td class="px-6 py-4 whitespace-nowrap">
                                  <div class="text-sm text-gray-900">{{ $user->readingPlans->first()->pivot->current_streak }} days</div>
                              </td>
                              <td class="px-6 py-4 whitespace-nowrap">
                                  <div class="flex items-center">
                                      <div class="w-16 bg-gray-200 rounded-full h-2.5">
                                          <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $user->readingPlans->first()->pivot->completion_rate }}%"></div>
                                      </div>
                                      <span class="ml-2 text-sm text-gray-900">{{ number_format($user->readingPlans->first()->pivot->completion_rate, 0) }}%</span>
                                  </div>
                              </td>
                              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                  <button
                                      wire:click="sendReminderToUser({{ $user->id }})"
                                      class="text-blue-600 hover:text-blue-900"
                                  >
                                      Send Reminder
                                  </button>
                                  <a href="#" class="ml-3 text-gray-600 hover:text-gray-900">
                                      View Details
                                  </a>
                              </td>
                          </tr>
                      @empty
                          <tr>
                              <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                  No users found for this reading plan.
                              </td>
                          </tr>
                      @endforelse
                  </tbody>
              </table>
          </div>
          
          <!-- Pagination -->
          <div class="mt-4">
              {{ $users->links() }}
          </div>
      </div>
  </div>
  
  <!-- Notification -->
  <div
      x-data="{ show: false, message: '', type: 'success' }"
      x-show="show"
      x-on:notify.window="show = true; message = $event.detail.message; type = $event.detail.type; setTimeout(() => { show = false }, 3000)"
      class="fixed bottom-4 right-4 px-4 py-2 rounded-md text-white"
      x-bind:class="{ 'bg-green-500': type === 'success', 'bg-red-500': type === 'error' }"
      style="display: none;"
  >
      <p x-text="message"></p>
  </div>
</div>