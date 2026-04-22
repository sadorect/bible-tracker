<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SystemRole extends Model
{
    public const SUPER_ADMIN = 'super_admin';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(SystemPermission::class, 'system_permission_role')
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'system_role_user')
            ->withTimestamps();
    }
}
