<?php

namespace App\Console\Commands;

use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckReadingPlanProgress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reading:check-progress';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and update reading plan progress for all users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking reading plan progress...');
        
        $users = User::whereHas('readingPlans', function ($query) {
            $query->where('is_active', true);
        })->get();
        
        $this->info("Found {$users->count()} users with active reading plans.");
        
        foreach ($users as $user) {
            $this->processUserPlans($user);
        }
        
        $this->info('Reading plan progress check completed.');
        return 0;
    }
    
    /**
     * Process all active reading plans for a user
     */
    protected function processUserPlans(User $user)
    {
        $activePlans = $user->readingPlans()->wherePivot('is_active', true)->get();
        
        foreach ($activePlans as $plan) {
            $this->info("Processing plan '{$plan->name}' for user {$user->name}");
            
            $pivotData = $plan->pivot;
            $currentDay = $pivotData->current_day;
            $currentStreak = $pivotData->current_streak;
            
            // Get the current day's reading
            $todayReading = $plan->dailyReadings()
                ->where('day_number', $currentDay)
                ->first();
                
            if (!$todayReading) {
                $this->warn("No reading found for day {$currentDay}. Skipping.");
                continue;
            }
            
            // Check if today's reading is completed
            $completedToday = $user->readingProgress()
                ->where('daily_reading_id', $todayReading->id)
                ->where('completed_date', Carbon::today())
                ->exists();
                
            // If it's a break day, automatically advance to the next day
            if ($todayReading->is_break_day) {
                $this->info("Day {$currentDay} is a break day. Advancing to next day.");
                
                // Advance to the next day
                $nextDay = $currentDay + 1;
                
                // Check if we've reached the end of the plan
                $maxDay = $plan->dailyReadings()->max('day_number');
                if ($nextDay > $maxDay) {
                    $this->info("User has completed the reading plan!");
                    
                    // Mark the plan as inactive
                    $user->readingPlans()->updateExistingPivot($plan->id, [
                        'is_active' => false,
                        'completion_rate' => 100,
                    ]);
                } else {
                    // Update to the next day
                    $user->readingPlans()->updateExistingPivot($plan->id, [
                        'current_day' => $nextDay,
                    ]);
                }
                
                continue;
            }
            
            // If today's reading is not completed and it's past midnight,
            // check if we need to reset the streak
            if (!$completedToday) {
                // Get the last completed reading
                $lastCompleted = $user->readingProgress()
                    ->where('reading_plan_id', $plan->id)
                    ->orderByDesc('completed_date')
                    ->first();
                    
                if ($lastCompleted) {
                    $lastCompletedDate = Carbon::parse($lastCompleted->completed_date);
                    $daysSinceLastCompleted = $lastCompletedDate->diffInDays(Carbon::today());
                    
                    // If it's been more than 1 day since the last completed reading,
                    // reset the streak
                    if ($daysSinceLastCompleted > 1) {
                        $this->warn("User has missed {$daysSinceLastCompleted} days. Resetting streak.");
                        
                        $user->readingPlans()->updateExistingPivot($plan->id, [
                            'current_streak' => 0,
                        ]);
                    }
                }
            }
            
            // Calculate completion rate
            $completedReadings = $user->readingProgress()
                ->where('reading_plan_id', $plan->id)
                ->count();
                
            $totalReadingDays = $plan->dailyReadings()
                ->where('is_break_day', false)
                ->where('day_number', '<=', $currentDay)
                ->count();
                
            if ($totalReadingDays > 0) {
                $completionRate = ($completedReadings / $totalReadingDays) * 100;
                
                $user->readingPlans()->updateExistingPivot($plan->id, [
                    'completion_rate' => $completionRate,
                ]);
                
                $this->info("Updated completion rate to {$completionRate}%");
            }
        }
    }
}
