@extends('layouts.app-improved')

@section('content')
    <div class="space-y-6">
        @php
            $user = auth()->user();
            $activePlan = $user?->readingPlans()->where('user_reading_plans.is_active', true)->first();
            $currentStreak = $activePlan?->pivot->current_streak ?? 0;
        @endphp
        <!-- Page Header -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                    <p class="text-gray-600 mt-1">Welcome back, {{ auth()->user()->name }}!</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="bg-blue-50 rounded-lg p-3 text-center">
                        <div class="text-sm text-blue-600 font-medium">Current Streak</div>
                        <div class="text-2xl font-bold text-blue-700">🔥 {{ $currentStreak }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Livewire Dashboard Component -->
        @livewire('dashboard')
    </div>
@endsection
