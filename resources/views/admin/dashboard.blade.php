<x-admin-layout>
    <x-slot name="header">
        Admin Dashboard
    </x-slot>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Users Card -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div class="ml-5">
                        <h3 class="text-lg font-medium text-gray-900">Users</h3>
                        <div class="mt-1 text-3xl font-semibold text-gray-700">{{ \App\Models\User::count() }}</div>
                    </div>
                </div>
                <div class="mt-6">
                    <a href="{{ route('admin.users.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        View all users →
                    </a>
                </div>
            </div>
        </div>

        <!-- Reading Plans Card -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div class="ml-5">
                        <h3 class="text-lg font-medium text-gray-900">Reading Plans</h3>
                        <div class="mt-1 text-3xl font-semibold text-gray-700">{{ \App\Models\ReadingPlan::count() }}</div>
                    </div>
                </div>
                <div class="mt-6">
                    <a href="{{ route('admin.reading-plans.index') }}" class="text-green-600 hover:text-green-800 text-sm font-medium">
                        View all reading plans →
                    </a>
                </div>
            </div>
        </div>

        <!-- Reading Progress Card -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div class="ml-5">
                        <h3 class="text-lg font-medium text-gray-900">Reading Progress</h3>
                        <div class="mt-1 text-3xl font-semibold text-gray-700">{{ \App\Models\ReadingProgress::count() }}</div>
                    </div>
                </div>
                <div class="mt-6">
                    <a href="{{ route('reading.progress') }}" class="text-purple-600 hover:text-purple-800 text-sm font-medium">
                        View reading progress →
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Activity</h3>
            
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr>
                            <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">User</th>
                            <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Reading Plan</th>
                            <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Reading</th>
                            <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Completed Date</th>
                          </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-200">
                          @php
                              $recentProgress = \App\Models\ReadingProgress::with(['user', 'readingPlan', 'dailyReading'])
                                  ->orderByDesc('created_at')
                                  ->limit(10)
                                  ->get();
                          @endphp
                          
                          @forelse($recentProgress as $progress)
                              <tr>
                                  <td class="py-4 px-4 text-sm">{{ $progress->user->name }}</td>
                                  <td class="py-4 px-4 text-sm">{{ $progress->readingPlan->name }}</td>
                                  <td class="py-4 px-4 text-sm">{{ $progress->dailyReading->reading_range }}</td>
                                  <td class="py-4 px-4 text-sm">{{ \Carbon\Carbon::parse($progress->completed_date)->format('M d, Y') }}</td>
                              </tr>
                          @empty
                              <tr>
                                  <td colspan="4" class="py-4 px-4 text-sm text-center text-gray-500">No recent reading progress.</td>
                              </tr>
                          @endforelse
                      </tbody>
                  </table>
              </div>
          </div>
      </div>
  </x-admin-layout>
  