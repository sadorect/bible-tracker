<?php
namespace App\Models;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ReadingProgress extends Model
{
    protected $fillable = [
        'user_id',
        'day_number',
        'testament',
        'chapters_range',
        'is_completed',
        'completed_at'
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function getCurrentProgress($userId, $testament)
    {
        return self::where('user_id', $userId)
            ->where('testament', $testament)
            ->where('is_completed', true)
            ->count();
    }

    public static function getNextDay($userId, $testament)
    {
        $lastCompleted = self::where('user_id', $userId)
            ->where('testament', $testament)
            ->where('is_completed', true)
            ->max('day_number');

        return ($lastCompleted ?? 0) + 1;
    }
}
