<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\ReadingPlan;
use Illuminate\Http\Request;
use App\Models\ReadingProgress;
use App\Http\Controllers\Controller;

class AdminDashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        $userCount = User::count();
        $planCount = ReadingPlan::count();
        $progressCount = ReadingProgress::count();
        
        return view('admin.dashboard', compact('userCount', 'planCount', 'progressCount'));
    }
}
