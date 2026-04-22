<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageRecipient extends Model
{
    use HasFactory;

    public const EMAIL_STATUS_PENDING = 'pending';

    public const EMAIL_STATUS_SENT = 'sent';

    public const EMAIL_STATUS_FAILED = 'failed';

    public const EMAIL_STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'message_id',
        'recipient_id',
        'delivery_preference_snapshot',
        'rendered_subject',
        'rendered_body',
        'inbox_delivered_at',
        'email_status',
        'email_attempted_at',
        'emailed_at',
        'email_failure',
        'read_at',
    ];

    protected $casts = [
        'inbox_delivered_at' => 'datetime',
        'email_attempted_at' => 'datetime',
        'emailed_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function markAsRead(): void
    {
        if ($this->read_at) {
            return;
        }

        $this->forceFill([
            'read_at' => now(),
        ])->save();
    }
}
