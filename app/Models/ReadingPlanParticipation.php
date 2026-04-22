<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReadingPlanParticipation extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_LEFT = 'left';
    public const STATUS_SWITCHED = 'switched';
    public const STATUS_RESTARTED = 'restarted';
    public const STATUS_COMPLETED = 'completed';

    public const SOURCE_DIRECT = 'direct';
    public const SOURCE_INVITE = 'invite';

    protected $fillable = [
        'user_id',
        'reading_plan_id',
        'reading_plan_invite_id',
        'participation_number',
        'join_source',
        'started_on',
        'ended_on',
        'status',
        'summary',
    ];

    protected $casts = [
        'started_on' => 'date',
        'ended_on' => 'date',
        'summary' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function readingPlan(): BelongsTo
    {
        return $this->belongsTo(ReadingPlan::class);
    }

    public function invite(): BelongsTo
    {
        return $this->belongsTo(ReadingPlanInvite::class, 'reading_plan_invite_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(ReadingProgress::class, 'reading_plan_participation_id');
    }
}
