<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReadingPlan;
use App\Models\ReadingProgress;
use App\Models\User;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Admin dashboard statistics
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::whereHas('readingPlans', function($query) {
                $query->where('user_reading_plans.is_active', true);
            })->count(),
            'total_plans' => ReadingPlan::count(),
            'active_plans' => ReadingPlan::where('is_active', true)->count(),
            'total_completions' => ReadingProgress::count(),
            'today_completions' => ReadingProgress::whereDate('completed_date', Carbon::today())->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
