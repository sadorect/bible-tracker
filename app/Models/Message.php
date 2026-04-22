<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory;

    public const DIRECTION_DOWNWARD = 'downward';

    public const DIRECTION_UPWARD = 'upward';

    protected $fillable = [
        'sender_id',
        'message_template_id',
        'parent_message_id',
        'thread_root_id',
        'direction',
        'subject',
        'body',
        'targeting_filters',
        'targeting_snapshot',
    ];

    protected $casts = [
        'targeting_filters' => 'array',
        'targeting_snapshot' => 'array',
    ];

    public static function directionOptions(): array
    {
        return [
            self::DIRECTION_DOWNWARD,
            self::DIRECTION_UPWARD,
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'message_template_id');
    }

    public function parentMessage(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_message_id');
    }

    public function threadRoot(): BelongsTo
    {
        return $this->belongsTo(self::class, 'thread_root_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_message_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(MessageRecipient::class);
    }

    public function scopeRoots($query)
    {
        return $query->whereColumn('id', 'thread_root_id');
    }

    public function scopeInThread($query, self $threadRoot)
    {
        return $query->where('thread_root_id', $threadRoot->id);
    }

    public function isRoot(): bool
    {
        return $this->thread_root_id === $this->id;
    }
}
