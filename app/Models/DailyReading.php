<?php

namespace App\Models;

use App\Models\ReadingPlan;
use App\Models\ReadingProgress;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DailyReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'reading_plan_id',
        'day_number',
        'book_start',
        'chapter_start',
        'book_end',
        'chapter_end',
        'is_break_day',
    ];

    protected $casts = [
        'is_break_day' => 'boolean',
    ];

    public function readingPlan(): BelongsTo
    {
        return $this->belongsTo(ReadingPlan::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(ReadingProgress::class);
    }

    public function getReadingRangeAttribute(): string
    {
        if ($this->is_break_day) {
            return 'Break Day';
        }
        
        if ($this->book_start === $this->book_end) {
            return "{$this->book_start} {$this->chapter_start}-{$this->chapter_end}";
        }
        
        return "{$this->book_start} {$this->chapter_start} - {$this->book_end} {$this->chapter_end}";
    }
}
