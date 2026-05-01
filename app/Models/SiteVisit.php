<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteVisit extends Model
{
    protected $fillable = [
        'session_id',
        'url',
        'ip_address',
        'user_agent',
        'user_id',
        'referrer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
