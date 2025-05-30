<x-admin-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-semibold text-gray-900">Edit User: {{ $user->name }}</h1>
                <div class="flex space-x-3">
                    <a href="{{ route('admin.users.show', $user) }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg">
                        View User
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg">
                        Back to Users
                    </a>
                </div>
            </div>

            <!-- Form -->
            <div class="bg-white rounded-lg shadow-md">
                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="p-6 space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Basic Information -->
                    <div>
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                                <select name="role" id="role" required
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="member" {{ old('role', $user->role) === 'member' ? 'selected' : '' }}>Member</option>
                                    <option value="leader" {{ old('role', $user->role) === 'leader' ? 'selected' : '' }}>Leader</option>
                                    <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                                </select>
                                @error('role')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Password Change -->
                    <div>
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Change Password</h2>
                        <p class="text-sm text-gray-600 mb-4">Leave blank to keep current password.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                <input type="password" name="password" id="password"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('password')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                                <input type="password" name="password_confirmation" id="password_confirmation"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Reading Plans -->
                    <div>
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Reading Plans</h2>
                        <p class="text-sm text-gray-600 mb-4">Select reading plans for this user.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($readingPlans as $plan)
                                <div class="border rounded-lg p-4">
                                    <label class="flex items-start space-x-3 cursor-pointer">
                                        <input type="checkbox" name="reading_plans[]" value="{{ $plan->id }}"
                                               {{ in_array($plan->id, old('reading_plans', $userPlanIds)) ? 'checked' : '' }}
                                               class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900">{{ $plan->name }}</div>
                                            <div class="text-sm text-gray-600">{{ $plan->description }}</div>
                                            <div class="text-xs text-gray-500 mt-1">{{ $plan->duration_days }} days</div>
                                            @if(in_array($plan->id, $userPlanIds))
                                                @php
                                                    $userPlan = $user->readingPlans->where('id', $plan->id)->first();
                                                @endphp
                                                <div class="text-xs text-blue-600 mt-1">
                                                    Joined: {{ $userPlan->pivot->joined_at ? $userPlan->pivot->joined_at->format('M d, Y') : 'N/A' }} |  Current Day: {{ $userPlan->pivot->current_day }}
                                                </div>
                                            @endif
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        
                        @if($readingPlans->isEmpty())
                            <p class="text-gray-500 text-center py-8">No reading plans available.</p>
                        @endif
                        
                        @error('reading_plans')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                        <a href="{{ route('admin.users.show', $user) }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-lg">
                            Cancel
                        </a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg">
                            Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
