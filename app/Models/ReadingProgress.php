<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reading_plan_id',
        'reading_plan_participation_id',
        'daily_reading_id',
        'completed_date',
        'notes',
    ];

    protected $casts = [
        'completed_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function readingPlan(): BelongsTo
    {
        return $this->belongsTo(ReadingPlan::class);
    }

    public function participation(): BelongsTo
    {
        return $this->belongsTo(ReadingPlanParticipation::class, 'reading_plan_participation_id');
    }

    public function dailyReading(): BelongsTo
    {
        return $this->belongsTo(DailyReading::class);
    }
}
