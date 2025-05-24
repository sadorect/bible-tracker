<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReadingPlan;
use App\Models\ReadingProgress;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserProgressController extends Controller
{
    /**
     * Display the user progress dashboard.
     */
    public function index(Request $request)
    {
        // Get filter parameters
        $userId = $request->input('user_id');
        $planId = $request->input('plan_id');
        $dateRange = $request->input('date_range', 'all');
        
        // Get all users for the filter dropdown
        $users = User::orderBy('name')->get();
        
        // Get all reading plans for the filter dropdown
        $readingPlans = ReadingPlan::orderBy('name')->get();
        
        // Build the base query for reading progress
        $progressQuery = ReadingProgress::with(['user', 'readingPlan', 'dailyReading'])
            ->select('reading_progress.*')
            ->join('users', 'users.id', '=', 'reading_progress.user_id')
            ->join('reading_plans', 'reading_plans.id', '=', 'reading_progress.reading_plan_id');
        
        // Apply filters
        if ($userId) {
            $progressQuery->where('user_id', $userId);
        }
        
        if ($planId) {
            $progressQuery->where('reading_plan_id', $planId);
        }
        
        // Apply date range filter
        if ($dateRange !== 'all') {
            $startDate = null;
            $endDate = Carbon::today();
            
            switch ($dateRange) {
                case 'today':
                    $startDate = Carbon::today();
                    break;
                case 'yesterday':
                    $startDate = Carbon::yesterday();
                    $endDate = Carbon::yesterday();
                    break;
                case 'this_week':
                    $startDate = Carbon::now()->startOfWeek();
                    break;
                case 'last_week':
                    $startDate = Carbon::now()->subWeek()->startOfWeek();
                    $endDate = Carbon::now()->subWeek()->endOfWeek();
                    break;
                case 'this_month':
                    $startDate = Carbon::now()->startOfMonth();
                    break;
                case 'last_month':
                    $startDate = Carbon::now()->subMonth()->startOfMonth();
                    $endDate = Carbon::now()->subMonth()->endOfMonth();
                    break;
                case 'custom':
                    $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
                    $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::today();
                    break;
            }
            
            if ($startDate) {
                $progressQuery->whereBetween('reading_progress.completed_date', [$startDate, $endDate]);
            }
        }
        
        // Get paginated results
        $progress = $progressQuery->orderBy('reading_progress.completed_date', 'desc')
            ->paginate(15)
            ->withQueryString();
        
        // Get summary statistics
        $stats = $this->getProgressStats($userId, $planId, $dateRange, $request);
        
        return view('admin.progress.index', compact(
            'users', 
            'readingPlans', 
            'progress', 
            'stats',
            'userId',
            'planId',
            'dateRange'
        ));
    }
    
    /**
     * Get progress statistics for the dashboard
     */
    private function getProgressStats($userId, $planId, $dateRange, Request $request)
    {
        // Build base query
        $query = ReadingProgress::query();
        
        // Apply filters
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        if ($planId) {
            $query->where('reading_plan_id', $planId);
        }
        
        // Apply date range filter
        if ($dateRange !== 'all') {
            $startDate = null;
            $endDate = Carbon::today();
            
            switch ($dateRange) {
                case 'today':
                    $startDate = Carbon::today();
                    break;
                case 'yesterday':
                    $startDate = Carbon::yesterday();
                    $endDate = Carbon::yesterday();
                    break;
                case 'this_week':
                    $startDate = Carbon::now()->startOfWeek();
                    break;
                case 'last_week':
                    $startDate = Carbon::now()->subWeek()->startOfWeek();
                    $endDate = Carbon::now()->subWeek()->endOfWeek();
                    break;
                case 'this_month':
                    $startDate = Carbon::now()->startOfMonth();
                    break;
                case 'last_month':
                    $startDate = Carbon::now()->subMonth()->startOfMonth();
                    $endDate = Carbon::now()->subMonth()->endOfMonth();
                    break;
                case 'custom':
                    $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
                    $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::today();
                    break;
            }
            
            if ($startDate) {
                $query->whereBetween('completed_date', [$startDate, $endDate]);
            }
        }
        
        // Total completions
        $totalCompletions = $query->count();
        
        // Completions by user
        $completionsByUser = ReadingProgress::select('users.id','users.name', DB::raw('count(*) as count'))
            ->join('users', 'users.id', '=', 'reading_progress.user_id')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('count')
            ->limit(5);
            
        // Apply filters to the completions by user query
        if ($planId) {
            $completionsByUser->where('reading_plan_id', $planId);
        }
        
        if ($dateRange !== 'all' && isset($startDate)) {
            $completionsByUser->whereBetween('completed_date', [$startDate, $endDate]);
        }
        
        $completionsByUser = $completionsByUser->get();
        
        // Completions by plan
        $completionsByPlan = ReadingProgress::select('reading_plans.id','reading_plans.name', DB::raw('count(*) as count'))
            ->join('reading_plans', 'reading_plans.id', '=', 'reading_progress.reading_plan_id')
            ->groupBy('reading_plans.id', 'reading_plans.name')
            ->orderByDesc('count')
            ->limit(5);
            
        // Apply filters to the completions by plan query
        if ($userId) {
            $completionsByPlan->where('user_id', $userId);
        }
        
        if ($dateRange !== 'all' && isset($startDate)) {
            $completionsByPlan->whereBetween('completed_date', [$startDate, $endDate]);
        }
        
        $completionsByPlan = $completionsByPlan->get();
        
        // Completions by day (for chart)
        $completionsByDay = ReadingProgress::select(DB::raw('DATE(completed_date) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date');
            
        // Apply filters to the completions by day query
        if ($userId) {
            $completionsByDay->where('user_id', $userId);
        }
        
        if ($planId) {
            $completionsByDay->where('reading_plan_id', $planId);
        }
        
        // Limit to last 30 days if no date range specified
        if ($dateRange === 'all') {
            $completionsByDay->where('completed_date', '>=', Carbon::now()->subDays(30));
        } elseif (isset($startDate)) {
            $completionsByDay->whereBetween('completed_date', [$startDate, $endDate]);
        }
        
        $completionsByDay = $completionsByDay->get();
        
        // Format for chart
        $chartLabels = $completionsByDay->pluck('date')->toJson();
        $chartData = $completionsByDay->pluck('count')->toJson();
        
        // Active users count
        $activeUsers = User::whereHas('readingPlans', function ($query) {
            $query->where('user_reading_plans.is_active', true);
        })->count();
        
        // Active plans count
        $activePlans = ReadingPlan::where('is_active', true)->count();
        
        return [
            'total_completions' => $totalCompletions,
            'completions_by_user' => $completionsByUser,
            'completions_by_plan' => $completionsByPlan,
            'chart_labels' => $chartLabels,
            'chart_data' => $chartData,
            'active_users' => $activeUsers,
            'active_plans' => $activePlans,
        ];
    }
    
    /**
     * View detailed progress for a specific user
     */
    public function userDetail(User $user)
    {
        // Get all reading plans the user is part of
        $userPlans = $user->readingPlans;
        
        // Get overall statistics
        $totalCompletions = $user->readingProgress()->count();
        $currentStreak = $user->readingPlans()->wherePivot('is_active', true)->first()?->pivot->current_streak ?? 0;
        
        // Get completion rate for each plan
        $planStats = [];
        foreach ($userPlans as $plan) {
            $totalDays = $plan->dailyReadings()->where('is_break_day', false)->count();
            $completedDays = $user->readingProgress()->where('reading_plan_id', $plan->id)->count();
            $completionRate = $totalDays > 0 ? ($completedDays / $totalDays) * 100 : 0;
            
            $planStats[] = [
                'plan' => $plan,
                'total_days' => $totalDays,
                'completed_days' => $completedDays,
                'completion_rate' => $completionRate,
                'is_active' => $plan->pivot->is_active,
                'current_day' => $plan->pivot->current_day,
            ];
        }
        
        // Get recent activity
        $recentActivity = $user->readingProgress()
            ->with(['readingPlan', 'dailyReading'])
            ->orderByDesc('completed_date')
            ->limit(10)
            ->get();
        
        // Get completion trend (last 30 days)
        $completionTrend = ReadingProgress::select(DB::raw('DATE(completed_date) as date'), DB::raw('count(*) as count'))
            ->where('user_id', $user->id)
            ->where('completed_date', '>=', Carbon::now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        // Format for chart
        $chartLabels = $completionTrend->pluck('date')->toJson();
        $chartData = $completionTrend->pluck('count')->toJson();
        
        return view('admin.progress.user-detail', compact(
            'user',
            'userPlans',
            'totalCompletions',
            'currentStreak',
            'planStats',
            'recentActivity',
            'chartLabels',
            'chartData'
        ));
    }
    
    /**
     * View detailed progress for a specific plan
     */
    public function planDetail(ReadingPlan $readingPlan)
    {
        // Get all users in this plan
        $planUsers = $readingPlan->users;
        
        // Get overall statistics
        $totalCompletions = ReadingProgress::where('reading_plan_id', $readingPlan->id)->count();
        $totalUsers = $planUsers->count();
        $activeUsers = $planUsers->where('pivot.is_active', true)->count();
        
        // Get completion rate for each user
        $userStats = [];
        foreach ($planUsers as $user) {
            $totalDays = $readingPlan->dailyReadings()->where('is_break_day', false)->count();
            $completedDays = $user->readingProgress()->where('reading_plan_id', $readingPlan->id)->count();
            $completionRate = $totalDays > 0 ? ($completedDays / $totalDays) * 100 : 0;
            
            $userStats[] = [
                'user' => $user,
                'total_days' => $totalDays,
                'completed_days' => $completedDays,
                'completion_rate' => $completionRate,
                'is_active' => $user->pivot->is_active,
                'current_day' => $user->pivot->current_day,
                'current_streak' => $user->pivot->current_streak,
            ];
        }
        
        // Sort by completion rate (descending)
        usort($userStats, function ($a, $b) {
            return $b['completion_rate'] <=> $a['completion_rate'];
        });
        
        // Get recent activity
        $recentActivity = ReadingProgress::with(['user', 'dailyReading'])
            ->where('reading_plan_id', $readingPlan->id)
            ->orderByDesc('completed_date')
            ->limit(10)
            ->get();
        
        // Get completion trend (last 30 days)
        $completionTrend = ReadingProgress::select(DB::raw('DATE(completed_date) as date'), DB::raw('count(*) as count'))
            ->where('reading_plan_id', $readingPlan->id)
            ->where('completed_date', '>=', Carbon::now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        // Format for chart
        $chartLabels = $completionTrend->pluck('date')->toJson();
        $chartData = $completionTrend->pluck('count')->toJson();
        
        return view('admin.progress.plan-detail', compact(
            'readingPlan',
            'planUsers',
            'totalCompletions',
            'totalUsers',
            'activeUsers',
            'userStats',
            'recentActivity',
            'chartLabels',
            'chartData'
        ));
    }
    
        /**
     * Export progress data as CSV
     */
    public function export(Request $request)
    {
        // Get filter parameters
        $userId = $request->input('user_id');
        $planId = $request->input('plan_id');
        $dateRange = $request->input('date_range', 'all');
        
        // Build the base query for reading progress
        $progressQuery = ReadingProgress::with(['user', 'readingPlan', 'dailyReading'])
            ->select('reading_progress.*', 'users.name as user_name', 'reading_plans.name as plan_name', 'daily_readings.reading_range')
            ->join('users', 'users.id', '=', 'reading_progress.user_id')
            ->join('reading_plans', 'reading_plans.id', '=', 'reading_progress.reading_plan_id')
            ->join('daily_readings', 'daily_readings.id', '=', 'reading_progress.daily_reading_id');
        
        // Apply filters
        if ($userId) {
            $progressQuery->where('user_id', $userId);
        }
        
        if ($planId) {
            $progressQuery->where('reading_plan_id', $planId);
        }
        
        // Apply date range filter
        if ($dateRange !== 'all') {
            $startDate = null;
            $endDate = Carbon::today();
            
            switch ($dateRange) {
                case 'today':
                    $startDate = Carbon::today();
                    break;
                case 'yesterday':
                    $startDate = Carbon::yesterday();
                    $endDate = Carbon::yesterday();
                    break;
                case 'this_week':
                    $startDate = Carbon::now()->startOfWeek();
                    break;
                case 'last_week':
                    $startDate = Carbon::now()->subWeek()->startOfWeek();
                    $endDate = Carbon::now()->subWeek()->endOfWeek();
                    break;
                case 'this_month':
                    $startDate = Carbon::now()->startOfMonth();
                    break;
                case 'last_month':
                    $startDate = Carbon::now()->subMonth()->startOfMonth();
                    $endDate = Carbon::now()->subMonth()->endOfMonth();
                    break;
                case 'custom':
                    $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
                    $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : Carbon::today();
                    break;
            }
            
            if ($startDate) {
                $progressQuery->whereBetween('reading_progress.completed_date', [$startDate, $endDate]);
            }
        }
        
        // Get all results for export
        $progress = $progressQuery->orderBy('reading_progress.completed_date', 'desc')->get();
        
        // Generate CSV filename
        $filename = 'reading_progress_' . Carbon::now()->format('Y-m-d') . '.csv';
        
        // Create CSV response
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];
        
        $callback = function() use ($progress) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'User',
                'Reading Plan',
                'Reading',
                'Completed Date',
                'Completed Time',
            ]);
            
            // Add data rows
            foreach ($progress as $record) {
                $completedDate = Carbon::parse($record->completed_date);
                
                fputcsv($file, [
                    $record->user_name,
                    $record->plan_name,
                    $record->reading_range,
                    $completedDate->format('Y-m-d'),
                    $completedDate->format('H:i:s'),
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
