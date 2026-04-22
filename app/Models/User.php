<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MEMBER = 'member';

    public const ROLE_CLAN_LEADER = 'clan_leader';

    public const ROLE_PLATOON_LEADER = 'platoon_leader';

    public const ROLE_SQUAD_LEADER = 'squad_leader';

    public const ROLE_BATCH_LEADER = 'batch_leader';

    public const ROLE_TEAM_LEADER = 'team_leader';

    public const MESSAGE_DELIVERY_INBOX = 'inbox';

    public const MESSAGE_DELIVERY_EMAIL = 'email';

    public const MESSAGE_DELIVERY_BOTH = 'both';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name', 'email', 'phone_number', 'password', 'role', 'hierarchy_id',
        'message_delivery_preference', 'message_delivery_preference_locked',
    ];

    public static function roleOptions(): array
    {
        return [
            self::ROLE_MEMBER => 'Member',
            self::ROLE_TEAM_LEADER => 'Team Leader',
            self::ROLE_BATCH_LEADER => 'Batch Leader',
            self::ROLE_PLATOON_LEADER => 'Platoon Leader',
            self::ROLE_SQUAD_LEADER => 'Squad Leader',
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_CLAN_LEADER => 'Clan Leader',
        ];
    }

    public static function messageDeliveryOptions(): array
    {
        return [
            self::MESSAGE_DELIVERY_BOTH => 'Inbox + Email',
            self::MESSAGE_DELIVERY_INBOX => 'Inbox Only',
            self::MESSAGE_DELIVERY_EMAIL => 'Email Only',
        ];
    }

    public static function assignableRoles(): array
    {
        return array_keys(self::roleOptions());
    }

    public static function leaderRoles(): array
    {
        return [
            self::ROLE_CLAN_LEADER,
            self::ROLE_PLATOON_LEADER,
            self::ROLE_SQUAD_LEADER,
            self::ROLE_BATCH_LEADER,
            self::ROLE_TEAM_LEADER,
        ];
    }

    public static function hierarchyTypeForRole(string $role): ?string
    {
        return match ($role) {
            self::ROLE_MEMBER, self::ROLE_TEAM_LEADER => 'team',
            self::ROLE_BATCH_LEADER => 'batch',
            self::ROLE_PLATOON_LEADER => 'platoon',
            self::ROLE_SQUAD_LEADER => 'squad',
            self::ROLE_CLAN_LEADER => 'clan',
            default => null,
        };
    }

    public function roleLabel(): string
    {
        return self::roleOptions()[$this->role] ?? ucwords(str_replace('_', ' ', $this->role));
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'message_delivery_preference_locked' => 'boolean',
        ];
    }

    public function hierarchy()
    {
        return $this->belongsTo(Hierarchy::class);
    }

    public function platoon()
    {
        return $this->hasOne(Hierarchy::class, 'leader_id')->where('type', 'platoon');
    }

    public function squad()
    {
        return $this->hasOne(Hierarchy::class, 'leader_id')->where('type', 'squad');
    }

    public function batch()
    {
        return $this->hasOne(Hierarchy::class, 'leader_id')->where('type', 'batch');
    }

    public function team()
    {
        return $this->hasOne(Hierarchy::class, 'leader_id')->where('type', 'team');
    }

    public function readingProgress()
    {
        return $this->hasMany(ReadingProgress::class);
    }

    public function trainingCompletions(): HasMany
    {
        return $this->hasMany(TrainingCompletion::class);
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function readingPlanParticipations(): HasMany
    {
        return $this->hasMany(ReadingPlanParticipation::class);
    }

    public function receivedMessageRecipients(): HasMany
    {
        return $this->hasMany(MessageRecipient::class, 'recipient_id');
    }

    public function readingPlans(): BelongsToMany
    {
        return $this->belongsToMany(ReadingPlan::class, 'user_reading_plans')
            ->withPivot(['joined_date', 'current_participation_id', 'current_day', 'current_streak', 'completion_rate', 'is_active'])
            ->withTimestamps();
    }

    public function currentParticipationForPlan(int|ReadingPlan $plan): ?ReadingPlanParticipation
    {
        $planId = $plan instanceof ReadingPlan ? $plan->id : $plan;

        $participationId = $this->currentParticipationIdForPlan($planId);

        if (! $participationId) {
            return null;
        }

        return ReadingPlanParticipation::query()->find($participationId);
    }

    public function currentParticipationIdForPlan(int|ReadingPlan $plan): ?int
    {
        $planId = $plan instanceof ReadingPlan ? $plan->id : $plan;

        if ($this->relationLoaded('readingPlans')) {
            $loadedPlan = $this->readingPlans->first(fn (ReadingPlan $readingPlan) => $readingPlan->id === $planId);

            if ($loadedPlan?->pivot?->current_participation_id) {
                return (int) $loadedPlan->pivot->current_participation_id;
            }
        }

        $value = $this->readingPlans()
            ->where('reading_plan_id', $planId)
            ->value('user_reading_plans.current_participation_id');

        return $value ? (int) $value : null;
    }

    public function currentActiveParticipation(): ?ReadingPlanParticipation
    {
        $activePlan = $this->activeReadingPlan();

        if (! $activePlan || ! $activePlan->pivot?->current_participation_id) {
            return null;
        }

        return ReadingPlanParticipation::query()->find($activePlan->pivot->current_participation_id);
    }

    public function activeReadingPlan(): ?ReadingPlan
    {
        return $this->readingPlans()
            ->wherePivot('is_active', true)
            ->first();
    }

    public function activeReadingPlanFromLoaded(): ?ReadingPlan
    {
        if (! $this->relationLoaded('readingPlans')) {
            return $this->activeReadingPlan();
        }

        return $this->readingPlans->first(fn (ReadingPlan $plan) => (bool) $plan->pivot?->is_active);
    }

    public function hasCompletedPlan(ReadingPlan $readingPlan): bool
    {
        $requiredReadings = $readingPlan->dailyReadings()
            ->where('is_break_day', false)
            ->count();

        if ($requiredReadings === 0) {
            return false;
        }

        $completedReadings = $this->readingProgress()
            ->where('reading_plan_id', $readingPlan->id)
            ->distinct('daily_reading_id')
            ->count('daily_reading_id');

        return $completedReadings >= $requiredReadings;
    }

    public function hasCompletedPlanType(string $type): bool
    {
        return $this->readingPlans()
            ->where('type', $type)
            ->get()
            ->contains(fn (ReadingPlan $readingPlan) => $this->hasCompletedPlan($readingPlan));
    }

    public function currentLeadershipHierarchy(): ?Hierarchy
    {
        $expectedType = self::hierarchyTypeForRole($this->role);

        if ($expectedType) {
            $assignedHierarchy = $this->relationLoaded('hierarchy')
                ? $this->hierarchy
                : $this->hierarchy()->first();

            if ($assignedHierarchy && $assignedHierarchy->type === $expectedType) {
                return $assignedHierarchy;
            }
        }

        return Hierarchy::where('leader_id', $this->id)->first();
    }

    /**
     * Check if the user is an admin.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isLeader()
    {
        return in_array($this->role, self::leaderRoles(), true);
    }

    public function groupMessages(): HasMany
    {
        return $this->hasMany(GroupMessage::class);
    }

    public function canManageHierarchy()
    {
        return $this->isLeader() || $this->isAdmin();
    }

    public function unreadInboxCount(): int
    {
        return $this->receivedMessageRecipients()
            ->whereNull('read_at')
            ->count();
    }

    public function messageDeliveryPreferenceLabel(): ?string
    {
        if (! $this->message_delivery_preference) {
            return null;
        }

        return self::messageDeliveryOptions()[$this->message_delivery_preference] ?? null;
    }

    /**
     * Get all Bible chapters that the user has read.
     */
    public function readBibleChapters()
    {
        return $this->hasManyThrough(
            BibleChapter::class,
            ReadingProgress::class,
            'user_id', // Foreign key on reading_progress table
            'id', // Foreign key on bible_chapters table (via pivot)
            'id', // Local key on users table
            'daily_reading_id' // Local key on reading_progress table
        );
    }
}
