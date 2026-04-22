<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ReadingPlanInvite extends Model
{
    use HasFactory;

    protected $fillable = [
        'reading_plan_id',
        'created_by',
        'token',
        'label',
        'expires_at',
        'revoked_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $invite) {
            if (! $invite->token) {
                $invite->token = Str::random(40);
            }
        });
    }

    public function readingPlan(): BelongsTo
    {
        return $this->belongsTo(ReadingPlan::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participations(): HasMany
    {
        return $this->hasMany(ReadingPlanParticipation::class);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(?Carbon $at = null): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        $at ??= now();

        return $at->greaterThan($this->expires_at);
    }

    public function isUsable(?Carbon $at = null): bool
    {
        $plan = $this->relationLoaded('readingPlan')
            ? $this->readingPlan
            : $this->readingPlan()->first();

        return ! $this->isRevoked()
            && ! $this->isExpired($at)
            && $plan?->acceptsEnrollment($at);
    }

    public function enrollmentUrl(): string
    {
        return route('reading-plan-invites.show', $this->token);
    }
}
