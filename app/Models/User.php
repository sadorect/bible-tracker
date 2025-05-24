<?php

namespace App\Models;

use App\Models\Hierarchy;
use App\Models\ReadingPlan;
use App\Models\BibleChapter;
use App\Models\GroupMessage;
use App\Models\ReadingProgress;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name', 'email', 'phone_number', 'password', 'role', 'hierarchy_id'
    ];

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

    public function readingPlans(): BelongsToMany
    {
        return $this->belongsToMany(ReadingPlan::class, 'user_reading_plans')
                    ->withPivot(['joined_date', 'current_day', 'current_streak', 'completion_rate', 'is_active'])
                    ->withTimestamps();
    }

    
    /**
     * Check if the user is an admin.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isLeader()
    {
        return in_array($this->role, ['clan_leader', 'platoon_leader', 'squad_leader', 'batch_leader', 'team_leader']);
    }

    public function groupMessages(): HasMany
    {
        return $this->hasMany(GroupMessage::class);
    }
    
    public function canManageHierarchy()
    {
        return in_array($this->role, [
            'clan_leader',
            'platoon_leader',
            'squad_leader',
            'batch_leader',
            'team_leader'
        ]) || $this->isAdmin();
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