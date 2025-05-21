<x-admin-layout>
    <x-slot name="header">
        Reading Plan Management
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-lg font-medium text-gray-900">Reading Plans</h2>
                <a href="{{ route('admin.reading-plans.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm">
                    Create New Plan
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead>
                        <tr>
                            <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Name</th>
                            <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Type</th>
                            <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Chapters/Day</th>
                            <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Streak/Break</th>
                            <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Users</th>
                            <th class="py-3 px-4 bg-gray-100 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($readingPlans as $plan)
                            <tr>
                                <td class="py-4 px-4 text-sm">{{ $plan->name }}</td>
                                <td class="py-4 px-4 text-sm">{{ ucfirst(str_replace('_', ' ', $plan->type)) }}</td>
                                <td class="py-4 px-4 text-sm">{{ $plan->chapters_per_day }}</td>
                                <td class="py-4 px-4 text-sm">{{ $plan->streak_days }} / {{ $plan->break_days }}</td>
                                <td class="py-4 px-4 text-sm">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $plan->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $plan->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-sm">{{ $plan->users()->count() }}</td>
                                <td class="py-4 px-4 text-sm">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('admin.reading-plans.edit', $plan) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                                        <form action="{{ route('admin.reading-plans.destroy', $plan) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this reading plan? This will remove all associated readings and progress.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                        @if(count($readingPlans) === 0)
                            <tr>
                                <td colspan="7" class="py-4 px-4 text-sm text-center text-gray-500">No reading plans found.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>