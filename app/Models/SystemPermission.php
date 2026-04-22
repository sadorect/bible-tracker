<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SystemPermission extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'label',
        'group',
        'description',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(SystemRole::class, 'system_permission_role')
            ->withTimestamps();
    }
}
