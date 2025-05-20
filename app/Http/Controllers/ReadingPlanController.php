<?php

namespace App\Http\Controllers;

use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReadingPlanController extends Controller
{
    /**
     * Display a listing of the reading plans.
     */
    public function index()
    {
        $readingPlans = ReadingPlan::all();
        $user = Auth::user();
        
        // Get user's active plan if any
        $activePlan = $user->readingPlans()
            ->where('user_reading_plans.is_active', true)
            ->first();
            
        return view('reading-plans.index', [
            'readingPlans' => $readingPlans,
            'activePlan' => $activePlan
        ]);
    }

    /**
     * Display the details of a specific reading plan.
     */
    public function show(ReadingPlan $readingPlan)
    {
        return view('reading-plans.show', [
            'readingPlan' => $readingPlan
        ]);
    }

    /**
     * Join a reading plan.
     */
    public function join(ReadingPlan $readingPlan)
    {
        $user = Auth::user();
        
        // Check if user is already in this plan
        $existingPlan = $user->readingPlans()
            ->where('reading_plan_id', $readingPlan->id)
            ->first();
            
        if ($existingPlan) {
            // If already joined but not active, make it active
            if (!$existingPlan->pivot->is_active) {
                // First deactivate any currently active plans
                $user->readingPlans()
                    ->where('user_reading_plans.is_active', true)
                    ->update(['user_reading_plans.is_active' => false]);
                    
                // Then activate this plan
                $user->readingPlans()
                    ->updateExistingPivot($readingPlan->id, [
                        'is_active' => true
                    ]);
                    
                    return redirect()->route('reading-plans.show', $readingPlan)
                    ->with('success', 'You have resumed the ' . $readingPlan->name . ' reading plan.');
            }
            
            return redirect()->route('reading-plans.show', $readingPlan)
                ->with('info', 'You are already participating in this reading plan.');
        }
        
        // Deactivate any currently active plans
        $user->readingPlans()
            ->where('user_reading_plans.is_active', true)
            ->update(['user_reading_plans.is_active' => false]);
            
        // Join the new plan
        $user->readingPlans()->attach($readingPlan->id, [
            'joined_date' => Carbon::today(),
            'current_day' => 1,
            'current_streak' => 0,
            'completion_rate' => 0,
            'is_active' => true
        ]);
        
      // Redirect to the reading plan show page instead of directly to dashboard
    return redirect()->route('reading-plans.show', $readingPlan)
    ->with('success', 'You have joined the ' . $readingPlan->name . ' reading plan. You can now start your reading journey!');
    }

    /**
     * Leave a reading plan.
     */
    public function leave(ReadingPlan $readingPlan)
    {
        $user = Auth::user();
        
        // Check if user is in this plan
        $existingPlan = $user->readingPlans()
            ->where('reading_plan_id', $readingPlan->id)
            ->first();
            
        if (!$existingPlan) {
            return redirect()->route('reading-plans.index')
                ->with('error', 'You are not participating in this reading plan.');
        }
        
        // Just mark as inactive instead of detaching to preserve history
        $user->readingPlans()->updateExistingPivot($readingPlan->id, [
            'is_active' => false
        ]);
        
        return redirect()->route('reading-plans.index')
            ->with('success', 'You have left the ' . $readingPlan->name . ' reading plan.');
    }
}