<?php

namespace App\Models;

use App\Models\User;
use App\Models\ReadingPlan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GroupMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'reading_plan_id',
        'user_id',
        'title',
        'message',
        'is_admin_message',
    ];

    protected $casts = [
        'is_admin_message' => 'boolean',
    ];

    public function readingPlan(): BelongsTo
    {
        return $this->belongsTo(ReadingPlan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
