<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ReadingPlan extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_RECRUITING = 'recruiting';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_ARCHIVED = 'archived';

    public const TYPE_NEW_TESTAMENT = 'new_testament';

    public const TYPE_OLD_TESTAMENT = 'old_testament';

    private const TYPE_DEFAULTS = [
        self::TYPE_NEW_TESTAMENT => [
            'label' => 'New Testament',
            'testament' => 'new',
            'chapters_per_day' => 9,
            'total_chapters' => 260,
            'streak_days' => 10,
            'break_days' => 1,
        ],
        self::TYPE_OLD_TESTAMENT => [
            'label' => 'Old Testament',
            'testament' => 'old',
            'chapters_per_day' => 8,
            'total_chapters' => 929,
            'streak_days' => 10,
            'break_days' => 1,
        ],
    ];

    protected $fillable = [
        'name',
        'type',
        'lifecycle_status',
        'description',
        'chapters_per_day',
        'streak_days',
        'break_days',
        'start_date',
        'end_date',
        'enrollment_starts_at',
        'enrollment_ends_at',
        'is_active',
        'additional_info',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'enrollment_starts_at' => 'datetime',
        'enrollment_ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $plan) {
            if (! $plan->lifecycle_status) {
                $plan->lifecycle_status = $plan->inferLegacyLifecycleStatus();
            }

            $plan->is_active = in_array($plan->lifecycle_status, self::liveStatuses(), true);
        });
    }

    public static function supportedTypes(): array
    {
        return array_keys(self::TYPE_DEFAULTS);
    }

    public static function lifecycleStatuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_RECRUITING,
            self::STATUS_ACTIVE,
            self::STATUS_CLOSED,
            self::STATUS_ARCHIVED,
        ];
    }

    public static function lifecycleStatusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_RECRUITING => 'Recruiting',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_ARCHIVED => 'Archived',
        ];
    }

    public static function liveStatuses(): array
    {
        return [
            self::STATUS_RECRUITING,
            self::STATUS_ACTIVE,
        ];
    }

    public static function publiclyVisibleStatuses(): array
    {
        return self::liveStatuses();
    }

    public static function typeConfigurations(): array
    {
        return self::TYPE_DEFAULTS;
    }

    public static function defaultsFor(string $type): array
    {
        return self::TYPE_DEFAULTS[$type] ?? [
            'label' => Str::of($type)->replace('_', ' ')->title()->toString(),
            'testament' => $type === self::TYPE_OLD_TESTAMENT ? 'old' : 'new',
            'chapters_per_day' => 8,
            'total_chapters' => 0,
            'streak_days' => 10,
            'break_days' => 1,
        ];
    }

    public static function scheduledDaysFor(int $totalChapters, int $chaptersPerDay, int $streakDays, int $breakDays): int
    {
        if ($totalChapters < 1 || $chaptersPerDay < 1) {
            return 0;
        }

        $readingDays = (int) ceil($totalChapters / $chaptersPerDay);

        if ($breakDays < 1 || $streakDays < 1) {
            return $readingDays;
        }

        $breakBlocks = intdiv(max($readingDays - 1, 0), $streakDays);

        return $readingDays + ($breakBlocks * $breakDays);
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->whereIn('lifecycle_status', self::liveStatuses());
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->whereIn('lifecycle_status', self::publiclyVisibleStatuses());
    }

    public function scopeAcceptingEnrollments(Builder $query, ?CarbonInterface $at = null): Builder
    {
        $at = $at ? Carbon::instance($at) : now();

        return $query
            ->publiclyVisible()
            ->where(function (Builder $inner) use ($at) {
                $inner->whereNull('enrollment_starts_at')
                    ->orWhere('enrollment_starts_at', '<=', $at);
            })
            ->where(function (Builder $inner) use ($at) {
                $inner->whereNull('enrollment_ends_at')
                    ->orWhere('enrollment_ends_at', '>=', $at);
            });
    }

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
            ->withPivot(['joined_date', 'current_participation_id', 'current_day', 'current_streak', 'completion_rate', 'is_active'])
            ->withTimestamps();
    }

    public function invites(): HasMany
    {
        return $this->hasMany(ReadingPlanInvite::class)->latest();
    }

    public function participations(): HasMany
    {
        return $this->hasMany(ReadingPlanParticipation::class);
    }

    /**
     * Get the group messages for the reading plan.
     */
    public function groupMessages(): HasMany
    {
        return $this->hasMany(GroupMessage::class);
    }

    public function trainingResources(): HasMany
    {
        return $this->hasMany(TrainingResource::class)->orderBy('sort_order')->orderBy('id');
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

    public function getTrainingDaysAttribute(): int
    {
        return $this->relationLoaded('trainingResources')
            ? $this->trainingResources->count()
            : $this->trainingResources()->count();
    }

    public function getTrainingEndDateAttribute(): ?Carbon
    {
        if (! $this->start_date || $this->training_days === 0) {
            return null;
        }

        return $this->start_date->copy()->addDays($this->training_days - 1);
    }

    public function getReadingStartDateAttribute(): ?Carbon
    {
        if (! $this->start_date) {
            return null;
        }

        return $this->start_date->copy()->addDays($this->training_days);
    }

    public function getJourneyDaysAttribute(): int
    {
        return $this->training_days + $this->duration_days;
    }

    public function getTypeLabelAttribute(): string
    {
        return self::defaultsFor($this->type)['label'];
    }

    public function getLifecycleStatusLabelAttribute(): string
    {
        return self::lifecycleStatusOptions()[$this->lifecycle_status] ?? Str::headline($this->lifecycle_status ?? 'draft');
    }

    public function getCadenceDescriptionAttribute(): string
    {
        $breakDescription = $this->break_days > 0
            ? ", refresh break after every {$this->streak_days} days"
            : '';

        return "{$this->chapters_per_day} chapters daily{$breakDescription}";
    }

    public function getRecommendedReadingDaysAttribute(): int
    {
        $defaults = self::defaultsFor($this->type);

        if ($defaults['total_chapters'] === 0 || $this->chapters_per_day < 1) {
            return 0;
        }

        return (int) ceil($defaults['total_chapters'] / $this->chapters_per_day);
    }

    public function getRecommendedTotalScheduledDaysAttribute(): int
    {
        return self::scheduledDaysFor(
            self::defaultsFor($this->type)['total_chapters'],
            $this->chapters_per_day,
            $this->streak_days,
            $this->break_days,
        );
    }

    public function isNewTestament(): bool
    {
        return $this->type === self::TYPE_NEW_TESTAMENT;
    }

    public function isOldTestament(): bool
    {
        return $this->type === self::TYPE_OLD_TESTAMENT;
    }

    public function isLive(): bool
    {
        return in_array($this->lifecycle_status, self::liveStatuses(), true);
    }

    public function isRecruiting(): bool
    {
        return $this->lifecycle_status === self::STATUS_RECRUITING;
    }

    public function isPubliclyVisible(): bool
    {
        return in_array($this->lifecycle_status, self::publiclyVisibleStatuses(), true);
    }

    public function acceptsEnrollment(?CarbonInterface $at = null): bool
    {
        $at = $at ? Carbon::instance($at) : now();

        if (! $this->isPubliclyVisible()) {
            return false;
        }

        if ($this->enrollment_starts_at && $at->lt($this->enrollment_starts_at)) {
            return false;
        }

        if ($this->enrollment_ends_at && $at->gt($this->enrollment_ends_at)) {
            return false;
        }

        return true;
    }

    public function isTrainingCompleteFor(User $user): bool
    {
        if ($this->training_days === 0) {
            return true;
        }

        $completedCount = $user->trainingCompletions()
            ->whereIn('training_resource_id', $this->trainingResources()->pluck('id'))
            ->count();

        return $completedCount >= $this->training_days;
    }

    public function canRecordReadings(User $user, ?CarbonInterface $date = null): bool
    {
        $date ??= Carbon::today();

        if (! $this->isTrainingCompleteFor($user)) {
            return false;
        }

        return ! $this->reading_start_date || $date->gte($this->reading_start_date);
    }

    public function expectedCurrentDay(?CarbonInterface $date = null): int
    {
        $date ??= Carbon::today();
        $readingStartDate = $this->reading_start_date ?? $this->start_date;

        if (! $readingStartDate) {
            return 1;
        }

        $daysSinceStart = $readingStartDate->diffInDays($date, false);

        return $daysSinceStart < 0 ? 1 : $daysSinceStart + 1;
    }

    public function syncScheduleDates(): void
    {
        if (! $this->start_date) {
            return;
        }

        $scheduledReadingDays = $this->dailyReadings()->exists()
            ? $this->dailyReadings()->count()
            : $this->recommended_total_scheduled_days;
        $totalJourneyDays = max($this->training_days + $scheduledReadingDays, 1);

        $this->forceFill([
            'end_date' => $this->start_date->copy()->addDays($totalJourneyDays - 1),
        ])->save();
    }

    private function inferLegacyLifecycleStatus(): string
    {
        if (! $this->is_active) {
            return self::STATUS_DRAFT;
        }

        $startDate = $this->start_date
            ? Carbon::parse($this->start_date)
            : null;

        if ($startDate && $startDate->isFuture()) {
            return self::STATUS_RECRUITING;
        }

        return self::STATUS_ACTIVE;
    }
}
