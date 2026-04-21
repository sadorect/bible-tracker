<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReadingPlan;
use App\Models\ReadingProgress;
use App\Models\TrainingResource;
use App\Models\User;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        // Admin dashboard statistics - comprehensive overview
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::whereHas('readingPlans', function ($query) {
                $query->where('user_reading_plans.is_active', true);
            })->count(),
            'inactive_users' => User::whereDoesntHave('readingPlans', function ($query) {
                $query->where('user_reading_plans.is_active', true);
            })->count(),
            'total_plans' => ReadingPlan::count(),
            'active_plans' => ReadingPlan::where('is_active', true)->count(),
            'upcoming_plans' => ReadingPlan::whereDate('start_date', '>', $today)->count(),
            'training_resources' => TrainingResource::count(),
            'total_completions' => ReadingProgress::count(),
            'today_completions' => ReadingProgress::whereDate('completed_date', $today)->count(),
            'this_week_completions' => ReadingProgress::whereBetween('completed_date', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ])->count(),
            'this_month_completions' => ReadingProgress::whereMonth('completed_date', Carbon::now()->month)
                ->whereYear('completed_date', Carbon::now()->year)->count(),
        ];

        // Recent activity - latest completions across all users
        $recentActivity = ReadingProgress::with(['user', 'dailyReading.readingPlan'])
            ->orderBy('completed_date', 'desc')
            ->limit(10)
            ->get();

        // Top performers this week
        $topPerformers = User::withCount(['readingProgress' => function ($query) {
            $query->whereBetween('completed_date', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ]);
        }])
            ->having('reading_progress_count', '>', 0)
            ->orderBy('reading_progress_count', 'desc')
            ->limit(5)
            ->get();

        // Plans with most engagement
        $popularPlans = ReadingPlan::withCount(['readingProgress' => function ($query) {
            $query->whereBetween('completed_date', [
                Carbon::now()->subDays(30),
                Carbon::now(),
            ]);
        }])
            ->having('reading_progress_count', '>', 0)
            ->orderBy('reading_progress_count', 'desc')
            ->limit(5)
            ->get();

        $planSnapshots = ReadingPlan::withCount([
            'trainingResources',
            'dailyReadings',
            'readingProgress',
            'users as active_participants_count' => function ($query) {
                $query->where('user_reading_plans.is_active', true);
            },
        ])
            ->orderBy('start_date')
            ->get()
            ->map(function (ReadingPlan $plan) use ($today) {
                if ($plan->start_date && $today->lt($plan->start_date)) {
                    $statusLabel = 'Upcoming';
                    $statusTone = 'sky';
                } elseif ($plan->end_date && $today->gt($plan->end_date)) {
                    $statusLabel = 'Completed';
                    $statusTone = 'slate';
                } else {
                    $statusLabel = 'Active';
                    $statusTone = 'emerald';
                }

                return [
                    'plan' => $plan,
                    'status_label' => $statusLabel,
                    'status_tone' => $statusTone,
                ];
            });

        return view('admin.dashboard', compact('stats', 'recentActivity', 'topPerformers', 'popularPlans', 'planSnapshots'));
    }
}
