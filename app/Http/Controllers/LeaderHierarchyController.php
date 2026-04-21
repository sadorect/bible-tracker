<?php

namespace App\Http\Controllers;

use App\Models\Hierarchy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaderHierarchyController extends Controller
{
    private const TYPE_LABELS = [
        'clan'    => 'Clan',
        'squad'   => 'Squad',
        'platoon' => 'Platoon',
        'batch'   => 'Batch',
        'team'    => 'Team',
    ];

    /**
     * Tree view scoped to the leader's own root hierarchy node.
     */
    public function tree()
    {
        /** @var User $user */
        $user = Auth::user();

        $root = $this->resolveLeaderRoot($user);

        if (! $root) {
            return view('leader.hierarchies.no-hierarchy');
        }

        // Load all nodes in this branch (root + descendants) in one query.
        $allIds    = $root->descendantIdsIncludingSelf();
        $all       = Hierarchy::with('leader')
            ->withCount(['children', 'members'])
            ->whereIn('id', $allIds)
            ->get()
            ->keyBy('id');

        // Wire in-memory children so we avoid N+1.
        $all->each(function ($h) use ($all) {
            $children = $all
                ->filter(fn ($c) => $c->parent_id == $h->id)
                ->sortBy('name')
                ->values();
            $h->setRelation('children', $children);
        });

        // Root of the scoped tree.
        $root = $all[$root->id];

        // Only show expand/collapse controls for types that exist in this branch.
        $presentTypes = $all->pluck('type')->unique()->values()->toArray();
        $typeLabels   = collect(self::TYPE_LABELS)->only($presentTypes)->all();

        return view('leader.hierarchies.tree', [
            'root'       => $root,
            'typeLabels' => $typeLabels,
            'totalCount' => $all->count(),
        ]);
    }

    /**
     * Detail view for a single node — only accessible if it is within the
     * leader's own branch. Accepts a wildcard path like `squad/platoon/batch/42`;
     * only the last segment (the ID) is used for lookup.
     */
    public function show(string $path)
    {
        /** @var User $user */
        $user = Auth::user();
        $root = $this->resolveLeaderRoot($user);

        $segments  = array_values(array_filter(explode('/', $path)));
        $id        = (int) last($segments);
        $hierarchy = Hierarchy::findOrFail($id);

        // Deny access to nodes outside the leader's branch.
        if (! $root || ! $root->descendantIdsIncludingSelf()->contains($hierarchy->id)) {
            abort(403, 'You do not have access to this hierarchy node.');
        }

        $hierarchy->load(['leader', 'parent.parent.parent', 'members']);

        $children = $hierarchy->children()
            ->with('leader')
            ->withCount(['children', 'members'])
            ->orderBy('name')
            ->get();

        // Build breadcrumb up to (but not above) the leader's root.
        $breadcrumb   = collect();
        $rootId       = $root->id;
        $currentCrumb = $hierarchy->parent;
        while ($currentCrumb && $currentCrumb->id !== $rootId) {
            $breadcrumb->prepend($currentCrumb);
            $currentCrumb = $currentCrumb->parent ?? null;
        }
        // Include the root itself in the breadcrumb if we aren't on it.
        if ($hierarchy->id !== $rootId) {
            $breadcrumb->prepend($root);
        }

        $totalDescendantMembers = User::whereIn(
            'hierarchy_id',
            $hierarchy->descendantIdsIncludingSelf()
        )->count();

        // Build a map of id => pretty URL for breadcrumb + children links.
        $urlPathMap = [];
        $pathSoFar  = '';
        foreach ($breadcrumb as $crumb) {
            $pathSoFar               .= ($pathSoFar ? '/' : '') . $crumb->type;
            $urlPathMap[$crumb->id]   = url('/my-hierarchy/' . $pathSoFar . '/' . $crumb->id);
        }
        $currentPath = $pathSoFar . ($pathSoFar ? '/' : '') . $hierarchy->type;
        foreach ($children as $child) {
            $urlPathMap[$child->id] = url('/my-hierarchy/' . $currentPath . '/' . $child->type . '/' . $child->id);
        }

        return view('leader.hierarchies.show', [
            'hierarchy'              => $hierarchy,
            'root'                   => $root,
            'children'               => $children,
            'breadcrumb'             => $breadcrumb,
            'typeLabels'             => self::TYPE_LABELS,
            'totalDescendantMembers' => $totalDescendantMembers,
            'urlPathMap'             => $urlPathMap,
        ]);
    }

    /**
     * Find the hierarchy node that this leader is the root of.
     */
    private function resolveLeaderRoot(User $user): ?Hierarchy
    {
        // Leaders are assigned via `leader_id` on the hierarchy node.
        return Hierarchy::where('leader_id', $user->id)->first();
    }
}
