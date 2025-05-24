<?php

namespace App\Models;

use App\Models\User;
use App\Models\DailyReading;
use App\Models\GroupMessage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ReadingPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'chapters_per_day',
        'streak_days',
        'break_days',
        'start_date',
        'end_date',
        'is_active',
        'additional_info',
    ];


    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the daily readings for the reading plan.
     */
    public function dailyReadings(): HasMany
    {
        return $this->hasMany(DailyReading::class);
    }

    /**
     * Get the users that are following this reading plan.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_reading_plans')
                    ->withPivot(['joined_date', 'current_day', 'current_streak', 'completion_rate', 'is_active'])
                    ->withTimestamps();
    }

    /**
     * Get the group messages for the reading plan.
     */
    public function groupMessages(): HasMany
    {
        return $this->hasMany(GroupMessage::class);
    }

    /**
     * Get the reading progress records for the reading plan.
     */
    public function readingProgress()
    {
        return $this->hasMany(ReadingProgress::class);
    }

    /**
     * Get all Bible chapters associated with this reading plan.
     */
    public function bibleChapters()
    {
        return $this->hasManyThrough(
            BibleChapter::class,
            DailyReading::class,
            'reading_plan_id', // Foreign key on daily_readings table
            'id', // Foreign key on bible_chapters table (via pivot)
            'id', // Local key on reading_plans table
            'id' // Local key on daily_readings table
        );
    }

    /**
     * Get the duration in days of the reading plan.
     */
    public function getDurationDaysAttribute()
    {
        return $this->dailyReadings()->count();
    }
}
