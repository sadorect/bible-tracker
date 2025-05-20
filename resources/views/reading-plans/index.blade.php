<x-app-layout>
  <x-slot name="header">
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          {{ __('Reading Plans') }}
      </h2>
  </x-slot>

  <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
              <div class="p-6 bg-white border-b border-gray-200">
                  <h3 class="text-lg font-medium text-gray-900 mb-4">Available Reading Plans</h3>
                  <!-- Add this near the top of the content section -->
@if (session('success'))
<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
    <p>{{ session('success') }}</p>
</div>
@endif

@if (session('info'))
<div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6" role="alert">
    <p>{{ session('info') }}</p>
</div>
@endif

                  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
                      @foreach($readingPlans as $plan)
                          <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-200">
                              <div class="p-6">
                                  <h4 class="text-xl font-bold mb-2">{{ $plan->name }}</h4>
                                  <p class="text-gray-600 mb-4">{{ $plan->description }}</p>
                                  <div class="flex justify-between items-center">
                                      <span class="text-sm text-gray-500">{{ $plan->duration_days }} days</span>
                                      @if(!$plan->users->contains(auth()->user()))
                                          <form action="{{ route('reading-plans.join', $plan->id) }}" method="POST">
                                              @csrf
                                              <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm">Join Plan</button>
                                          </form>
                                      @else
                                          <button disabled class="bg-gray-400 text-white px-4 py-2 rounded-md text-sm">Enrolled</button>
                                          <a href="{{ route('reading-plans.show', $plan->id) }}" class="bg-green-600 hover:bg-green-800 text-white px-4 py-2 rounded-md text-sm ml-2">View Plan</a>
                                      @endif  
                                     </div>
                              </div>
                          </div>
                      @endforeach

                      <!-- If no plans are available, show a message -->
                      @if(count($readingPlans) === 0)
                          <div class="col-span-3 text-center py-8">
                              <p class="text-gray-500">No reading plans available at the moment.</p>
                          </div>
                      @endif
                  </div>
              </div>
          </div>
      </div>
  </div>
</x-app-layout>
