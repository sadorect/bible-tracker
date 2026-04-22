<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Hierarchy extends Model
{
    protected $fillable = ['name', 'type', 'leader_id', 'parent_id'];

    public function scopeOrdered($query)
    {
        return $query->orderByRaw("
            case type
                when 'clan' then 1
                when 'platoon' then 2
                when 'squad' then 3
                when 'batch' then 4
                when 'team' then 5
                else 6
            end
        ")->orderBy('name');
    }

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
        return User::whereIn('hierarchy_id', $this->descendantIdsIncludingSelf());
    }

    public function getActiveMembersToday()
    {
        return $this->getAllMembers()
            ->whereHas('readingProgress', function ($query) {
                $query->whereDate('completed_date', Carbon::today());
            });
    }

    public function descendantIdsIncludingSelf(): Collection
    {
        $children = $this->relationLoaded('children')
            ? $this->children
            : $this->children()->with('children')->get();

        $ids = collect([$this->id]);

        foreach ($children as $child) {
            $ids = $ids->merge($child->descendantIdsIncludingSelf());
        }

        return $ids->unique()->values();
    }

    public function descendantTeamsIncludingSelf(): Collection
    {
        return static::with(['parent', 'leader'])
            ->whereIn('id', $this->descendantIdsIncludingSelf())
            ->where('type', 'team')
            ->ordered()
            ->get();
    }

    public function displayPath(): string
    {
        $segments = collect();
        $current = $this;

        while ($current) {
            $segments->prepend($current->name);
            $current = $current->relationLoaded('parent')
                ? $current->parent
                : $current->parent()->first();
        }

        return $segments->implode(' / ');
    }

    public static function buildDisplayPaths(Collection $hierarchies): Collection
    {
        $namesById = $hierarchies->pluck('name', 'id');
        $parentsById = $hierarchies->pluck('parent_id', 'id');
        $paths = collect();

        $resolvePath = function (?int $hierarchyId) use (&$resolvePath, $namesById, $parentsById, $paths): string {
            if (! $hierarchyId || ! isset($namesById[$hierarchyId])) {
                return '';
            }

            if ($paths->has($hierarchyId)) {
                return $paths->get($hierarchyId);
            }

            $parentId = $parentsById[$hierarchyId] ?? null;
            $path = $parentId && isset($namesById[$parentId])
                ? $resolvePath($parentId).' / '.$namesById[$hierarchyId]
                : $namesById[$hierarchyId];

            $paths->put($hierarchyId, $path);

            return $path;
        };

        $hierarchies->each(fn (Hierarchy $hierarchy) => $resolvePath($hierarchy->id));

        return $paths;
    }
}
