<?php

namespace App\Console\Commands;

use App\Models\BibleChapter;
use App\Models\DailyReading;
use App\Models\ReadingPlan;
use Illuminate\Console\Command;

class LinkReadingPlansToBibleChapters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reading:link-chapters';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Link existing reading plans to Bible chapters';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $readingPlans = ReadingPlan::all();
        
        if ($readingPlans->isEmpty()) {
            $this->info('No reading plans found.');
            return 0;
        }
        
        $this->info('Linking reading plans to Bible chapters...');
        
        foreach ($readingPlans as $readingPlan) {
            $this->info("Processing plan: {$readingPlan->name}");
            
            $testament = $readingPlan->type === 'old_testament' ? 'old' : 'new';
            $dailyReadings = $readingPlan->dailyReadings()->where('is_break_day', false)->get();
            
            $bar = $this->output->createProgressBar(count($dailyReadings));
            
            foreach ($dailyReadings as $dailyReading) {
                // Skip if already has chapters
                if ($dailyReading->bibleChapters()->exists()) {
                    $bar->advance();
                    continue;
                }
                
                // Find chapters that match the reading range
                $startBook = $dailyReading->book_start;
                $startChapter = $dailyReading->chapter_start;
                $endBook = $dailyReading->book_end;
                $endChapter = $dailyReading->chapter_end;
                
                // Get all chapters in the range
                $chaptersToAttach = $this->getChaptersInRange(
                    $testament,
                    $startBook,
                    $startChapter,
                    $endBook,
                    $endChapter
                );
                
                if (!empty($chaptersToAttach)) {
                    $dailyReading->bibleChapters()->attach($chaptersToAttach);
                }
                
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine();
        }
        
        $this->info('All reading plans have been linked to Bible chapters.');
        return 0;
    }
    
    /**
     * Get all chapter IDs in a range
     */
    protected function getChaptersInRange($testament, $startBook, $startChapter, $endBook, $endChapter)
    {
        // If it's a break day
        if ($startBook === 'Break' || $endBook === 'Break') {
            return [];
        }
        
        // If it's a single book
        if ($startBook === $endBook) {
            return BibleChapter::where('testament', $testament)
                ->where('book_name', $startBook)
                ->whereBetween('chapter_number', [$startChapter, $endChapter])
                ->pluck('id')
                ->toArray();
        }
        
        // If it spans multiple books
        $chapters = BibleChapter::where('testament', $testament)
            ->where(function ($query) use ($startBook, $startChapter, $endBook, $endChapter) {
                // First book (from start chapter to end)
                $query->where(function ($q) use ($startBook, $startChapter) {
                    $q->where('book_name', $startBook)
                      ->where('chapter_number', '>=', $startChapter);
                })
                // Middle books (all chapters)
                ->orWhere(function ($q) use ($startBook, $endBook) {
                    $q->whereNotIn('book_name', [$startBook, $endBook])
                      ->whereRaw("FIELD(book_name, ?) < FIELD(book_name, ?)", [$startBook, $endBook]);
                })
                // Last book (from start to end chapter)
                ->orWhere(function ($q) use ($endBook, $endChapter) {
                    $q->where('book_name', $endBook)
                      ->where('chapter_number', '<=', $endChapter);
                });
            })
            ->orderBy('id')
            ->pluck('id')
            ->toArray();
            
        return $chapters;
    }
}