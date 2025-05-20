<?php

namespace Database\Seeders;

use App\Models\DailyReading;
use App\Models\GroupMessage;
use App\Models\ReadingPlan;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BibleReadingSeeder extends Seeder
{
    /**
     * List of Old Testament books with chapter counts
     */
    protected $oldTestamentBooks = [
        'Genesis' => 50,
        'Exodus' => 40,
        'Leviticus' => 27,
        'Numbers' => 36,
        'Deuteronomy' => 34,
        'Joshua' => 24,
        'Judges' => 21,
        'Ruth' => 4,
        '1 Samuel' => 31,
        '2 Samuel' => 24,
        '1 Kings' => 22,
        '2 Kings' => 25,
        '1 Chronicles' => 29,
        '2 Chronicles' => 36,
        'Ezra' => 10,
        'Nehemiah' => 13,
        'Esther' => 10,
        'Job' => 42,
        'Psalms' => 150,
        'Proverbs' => 31,
        'Ecclesiastes' => 12,
        'Song of Solomon' => 8,
        'Isaiah' => 66,
        'Jeremiah' => 52,
        'Lamentations' => 5,
        'Ezekiel' => 48,
        'Daniel' => 12,
        'Hosea' => 14,
        'Joel' => 3,
        'Amos' => 9,
        'Obadiah' => 1,
        'Jonah' => 4,
        'Micah' => 7,
        'Nahum' => 3,
        'Habakkuk' => 3,
        'Zephaniah' => 3,
        'Haggai' => 2,
        'Zechariah' => 14,
        'Malachi' => 4
    ];

    protected $newTestamentBooks = [
      'Matthew' => 28,
      'Mark' => 16,
      'Luke' => 24,
      'John' => 21,
      'Acts' => 28,
      'Romans' => 16,
      '1 Corinthians' => 16,
      '2 Corinthians' => 13,
      'Galatians' => 6,
      'Ephesians' => 6,
      'Philippians' => 4,
      'Colossians' => 4,
      '1 Thessalonians' => 5,
      '2 Thessalonians' => 3,
      '1 Timothy' => 6,
      '2 Timothy' => 4,
      'Titus' => 3,
      'Philemon' => 1,
      'Hebrews' => 13,
      'James' => 5,
      '1 Peter' => 5,
      '2 Peter' => 3,
      '1 John' => 5,
      '2 John' => 1,
      '3 John' => 1,
      'Jude' => 1,
      'Revelation' => 22
  ];
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Old Testament Reading Plan
        $readingPlan = ReadingPlan::create([
            'name' => 'Old Testament Reading Plan',
            'type' => 'old_testament',
            'chapters_per_day' => 8,
            'streak_days' => 10,
            'break_days' => 1,
            'start_date' => Carbon::now()->subDays(4), // Started 4 days ago
            'is_active' => true,
        ]);

        // Generate daily readings for the plan
        $this->generateDailyReadings($readingPlan);
        
        // Add some sample group messages
        $this->createSampleMessages($readingPlan);
    }

    /**
     * Generate daily readings for a reading plan
     */
    protected function generateDailyReadings(ReadingPlan $readingPlan): void
    {
        $chaptersPerDay = $readingPlan->chapters_per_day;
        $streakDays = $readingPlan->streak_days;
        $breakDays = $readingPlan->break_days;
        
        $totalChapters = array_sum($this->oldTestamentBooks);
        
        // Calculate how many reading days we need (excluding break days)
        $totalReadingDays = ceil($totalChapters / $chaptersPerDay);
        
        // Calculate total days including break days
        $cycleLength = $streakDays + $breakDays;
        $totalCycles = ceil($totalReadingDays / $streakDays);
        $totalDays = $totalCycles * $cycleLength;
        
        $currentBook = array_key_first($this->oldTestamentBooks);
        $currentChapter = 1;
        $dayNumber = 1;
        
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
                continue;
            }
            
            // This is a reading day
            $startBook = $currentBook;
            $startChapter = $currentChapter;
            
            // Calculate end chapter and book
            $chaptersToRead = $chaptersPerDay;
            
            while ($chaptersToRead > 0 && $currentBook) {
                $bookChapters = $this->oldTestamentBooks[$currentBook];
                $remainingChaptersInBook = $bookChapters - $currentChapter + 1;
                
                if ($chaptersToRead >= $remainingChaptersInBook) {
                    // We'll finish this book and move to the next
                    $chaptersToRead -= $remainingChaptersInBook;
                    $currentChapter = 1;
                    
                    // Move to the next book
                    $keys = array_keys($this->oldTestamentBooks);
                    $currentIndex = array_search($currentBook, $keys);
                    $currentBook = isset($keys[$currentIndex + 1]) ? $keys[$currentIndex + 1] : null;
                } else {
                    // We won't finish this book
                    $currentChapter += $chaptersToRead;
                    $chaptersToRead = 0;
                }
            }
            
            $endBook = $currentBook ?: array_key_last($this->oldTestamentBooks);
            $endChapter = $currentBook ? $currentChapter - 1 : $this->oldTestamentBooks[array_key_last($this->oldTestamentBooks)];
            
            // Create the daily reading
            DailyReading::create([
                'reading_plan_id' => $readingPlan->id,
                'day_number' => $day,
                'book_start' => $startBook,
                'chapter_start' => $startChapter,
                'book_end' => $endBook,
                'chapter_end' => $endChapter,
                'is_break_day' => false,
            ]);
            
            // If we've reached the end of the Bible, break
            if (!$currentBook) {
                break;
            }
        }
    }
    
    /**
     * Create sample group messages
     */
    protected function createSampleMessages(ReadingPlan $readingPlan): void
    {
        GroupMessage::create([
            'reading_plan_id' => $readingPlan->id,
            'user_id' => 1, // Admin user
            'title' => 'Admin Message',
            'message' => 'Remember to submit your reading report by midnight!',
            'is_admin_message' => true,
            'created_at' => Carbon::now(),
        ]);
        
        GroupMessage::create([
            'reading_plan_id' => $readingPlan->id,
            'user_id' => 1,
            'title' => 'Group 3 Milestone',
            'message' => 'Our group reached 90% completion rate this week!',
            'is_admin_message' => false,
            'created_at' => Carbon::now()->subDay(),
        ]);
        
        GroupMessage::create([
            'reading_plan_id' => $readingPlan->id,
            'user_id' => 1,
            'title' => 'Admin Message',
            'message' => 'Discussion for Genesis 25-32 is now open in the forum.',
            'is_admin_message' => true,
            'created_at' => Carbon::now()->subDays(2),
        ]);
    }
}