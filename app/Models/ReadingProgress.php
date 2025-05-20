<?php

namespace App\Models;

use App\Models\User;
use App\Models\ReadingPlan;
use App\Models\DailyReading;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReadingProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reading_plan_id',
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

    public function dailyReading(): BelongsTo
    {
        return $this->belongsTo(DailyReading::class);
    }
}
