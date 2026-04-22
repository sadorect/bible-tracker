<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'actor_id',
        'event',
        'subject_type',
        'subject_id',
        'subject_label',
        'description',
        'metadata',
        'route_name',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function eventLabel(): string
    {
        return Str::of($this->event)
            ->replace('.', ' ')
            ->headline()
            ->toString();
    }
}
