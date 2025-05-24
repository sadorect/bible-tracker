<?php
namespace App\Models;

use App\Models\DailyReading;
use Illuminate\Database\Eloquent\Model;

class BibleChapter extends Model
{
     /**
     * Get chapters for a specific day and testament
     */
    /**
     * Get chapters for a specific day and testament
     */
    public static function getChaptersForDay($dayNumber, $testament)
    {
        return self::where('day_number', $dayNumber)
            ->where('testament', $testament)
            ->orderBy('id')
            ->get();
    }

    public static function getDayRange($dayNumber, $testament)
    {
        $chapters = self::where('day_number', $dayNumber)
            ->where('testament', $testament)
            ->orderBy('id')
            ->get();
            if ($chapters->isEmpty()) {
                return 'No readings';
            }
        return sprintf(
            '%s %d - %s %d',
            $chapters->first()->book_name,
            $chapters->first()->chapter_number,
            $chapters->last()->book_name,
            $chapters->last()->chapter_number
        );
    }

    public static function getTotalDays($testament)
    {
        return self::where('testament', $testament)
            ->max('day_number');
    }

    /**
     * Get the daily readings that include this chapter.
     */
    public function dailyReadings()
    {
        return $this->belongsToMany(DailyReading::class, 'daily_reading_chapters');
    }

     /**
     * Get the full name of the chapter (e.g., "Genesis 1")
     */
    public function getFullNameAttribute()
    {
        return "{$this->book_name} {$this->chapter_number}";
    }

    /**
     * Get the URL for this chapter
     */
    public function getUrlAttribute()
    {
        return route('bible.chapter', [$this->book_name, $this->chapter_number]);
    }
}