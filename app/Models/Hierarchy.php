<?php
namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Hierarchy extends Model
{
    protected $fillable = ['name', 'type', 'leader_id', 'parent_id'];

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members()
    {
        return $this->hasMany(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Hierarchy::class, 'parent_id');
    }
  

    public function children()
    {
        return $this->hasMany(Hierarchy::class, 'parent_id');
    }

    public function squads()
    {
        return $this->children()->where('type', 'squad');
    }

    public function batches()
    {
        return $this->children()->where('type', 'batch');
    }

    public function teams()
    {
        return $this->children()->where('type', 'team');
    }
   
    public function descendants()
    {
        return $this->children()->with('descendants');
    }
    public function getAllMembers()
    {
        return User::whereIn('hierarchy_id', 
            $this->descendants()->pluck('id')->push($this->id)
        );
    }

    public function getActiveMembersToday()
    {
        return $this->getAllMembers()
            ->whereHas('readingProgress', function($query) {
                $query->where('completed_at', '>=', now()->startOfDay());
            });
    }
}
