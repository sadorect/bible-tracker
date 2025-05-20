<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BibleChapter extends Model
{
    protected $fillable = [
        'book_name',
        'chapter_number',
        'day_number',
        'testament'
    ];

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
}