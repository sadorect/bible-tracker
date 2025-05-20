<?php
namespace App\Models;

use App\Models\User;
use App\Models\Hierarchy;
use Illuminate\Database\Eloquent\Model;

class Clan extends Model
{
    protected $fillable = ['name', 'leader_id'];

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function platoons()
    {
        return $this->hasMany(Hierarchy::class)->where('type', 'platoon');
    }

    public function getAllMembers()
    {
        return User::whereIn('hierarchy_id', 
            $this->platoons()->with('descendants')->get()
                ->pluck('descendants.*.id')
                ->flatten()
        );
    }
}
