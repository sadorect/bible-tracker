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
        'chapters_per_day',
        'streak_days',
        'break_days',
        'start_date',
        'end_date',
        'is_active',
    ];


    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_reading_plans')
                    ->withPivot(['joined_date', 'current_day', 'current_streak', 'completion_rate'])
                    ->withTimestamps();
    }

    public function dailyReadings(): HasMany
    {
        return $this->hasMany(DailyReading::class);
    }

    public function groupMessages(): HasMany
    {
        return $this->hasMany(GroupMessage::class);
    }
}
