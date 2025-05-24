<?php

namespace App\Console\Commands;

use App\Models\BibleChapter;
use App\Models\DailyReading;
use App\Models\ReadingPlan;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateReadingPlan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reading:generate {plan_id : The ID of the reading plan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate daily readings for a reading plan and link them to Bible chapters';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $planId = $this->argument('plan_id');
        $readingPlan = ReadingPlan::find($planId);
        
        if (!$readingPlan) {
            $this->error("Reading plan with ID {$planId} not found.");
            return 1;
        }
        
        $this->info("Generating daily readings for plan: {$readingPlan->name}");
        
        // Determine which testament to use
        $testament = $readingPlan->type === 'old_testament' ? 'old' : 'new';
        
        // Get all chapters for this testament
        $chapters = BibleChapter::where('testament', $testament)->orderBy('id')->get();
        $totalChapters = $chapters->count();
        
        $this->info("Found {$totalChapters} chapters in the {$testament} testament");
        
        // Calculate how many reading days we need
        $chaptersPerDay = $readingPlan->chapters_per_day;
        $totalReadingDays = ceil($totalChapters / $chaptersPerDay);
        
        // Calculate total days including break days
        $streakDays = $readingPlan->streak_days;
        $breakDays = $readingPlan->break_days;
        $cycleLength = $streakDays + $breakDays;
        $totalCycles = ceil($totalReadingDays / $streakDays);
        $totalDays = $totalCycles * $cycleLength;
        
        $this->info("Generating {$totalDays} days of readings (including breaks)");
        
        $chapterIndex = 0;
        $bar = $this->output->createProgressBar($totalDays);
        
        for ($day = 1; $day <= $totalDays; $day++) {
            // Determine if this is a reading day or break day
            $cyclePosition = ($day - 1) % $cycleLength + 1;
            $isBreakDay = $cyclePosition > $streakDays;
            
            if ($isBreakDay) {
                // Create a break day entry
                DailyReading::create([
                    'reading_plan_id' => $readingPlan->id,
                    'day_number' => $day,
                    'book_start' => 'Break',
                    'chapter_start' => 0,
                    'book_end' => 'Break',
                    'chapter_end' => 0,
                    'is_break_day' => true,
                ]);
            } else {
                // This is a reading day
                $startChapterIndex = $chapterIndex;
                $endChapterIndex = min($startChapterIndex + $chaptersPerDay - 1, $totalChapters - 1);
                
                $startChapter = $chapters[$startChapterIndex];
                $endChapter = $chapters[$endChapterIndex];
                
                $dailyReading = DailyReading::create([
                    'reading_plan_id' => $readingPlan->id,
                    'day_number' => $day,
                    'book_start' => $startChapter->book_name,
                    'chapter_start' => $startChapter->chapter_number,
                    'book_end' => $endChapter->book_name,
                    'chapter_end' => $endChapter->chapter_number,
                    'is_break_day' => false,
                ]);
                
                // Associate chapters with this daily reading
                $chaptersToAttach = $chapters->slice($startChapterIndex, $endChapterIndex - $startChapterIndex + 1)
                    ->pluck('id')
                    ->toArray();
                $dailyReading->bibleChapters()->attach($chaptersToAttach);
                
                // Update chapter index for next reading
                $chapterIndex = $endChapterIndex + 1;
                
                // If we've used all chapters, we're done with reading days
                if ($chapterIndex >= $totalChapters) {
                    break;
                }
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("Successfully generated daily readings for plan: {$readingPlan->name}");
        
        return 0;
    }
}