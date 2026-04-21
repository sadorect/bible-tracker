<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hierarchy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminHierarchyController extends Controller
{
    private const TYPE_LABELS = [
        'clan' => 'Clan',
        'squad' => 'Squad',
        'platoon' => 'Platoon',
        'batch' => 'Batch',
        'team' => 'Team',
    ];

    public function tree()
    {
        // Load all hierarchies flat; build the tree structure in memory to avoid
        // deep eager-loading chains or N+1 queries.
        $all = Hierarchy::with('leader')
            ->withCount(['children', 'members'])
            ->get()
            ->keyBy('id');

        // Attach in-memory children to each node.
        $all->each(function ($h) use ($all) {
            $children = $all
                ->filter(fn ($c) => $c->parent_id == $h->id)
                ->sortBy('name')
                ->values();
            $h->setRelation('children', $children);
        });

        $roots = $all
            ->filter(fn ($h) => is_null($h->parent_id))
            ->sortBy('name')
            ->values();

        return view('admin.hierarchies.tree', [
            'roots'      => $roots,
            'typeLabels' => self::TYPE_LABELS,
            'totalCount' => $all->count(),
        ]);
    }

    public function show(Hierarchy $hierarchy)
    {
        $hierarchy->load(['leader', 'parent.parent.parent.parent', 'members']);

        $children = $hierarchy->children()
            ->with('leader')
            ->withCount(['children', 'members'])
            ->orderBy('name')
            ->get();

        // Build breadcrumb from loaded ancestors.
        $breadcrumb = collect();
        $current = $hierarchy->parent;
        while ($current) {
            $breadcrumb->prepend($current);
            $current = $current->parent ?? null;
        }

        $totalDescendantMembers = User::whereIn(
            'hierarchy_id',
            $hierarchy->descendantIdsIncludingSelf()
        )->count();

        return view('admin.hierarchies.show', [
            'hierarchy'              => $hierarchy,
            'children'               => $children,
            'breadcrumb'             => $breadcrumb,
            'typeLabels'             => self::TYPE_LABELS,
            'totalDescendantMembers' => $totalDescendantMembers,
        ]);
    }

    public function index()
    {
        $hierarchies = Hierarchy::with(['parent.parent', 'leader'])
            ->withCount(['children', 'members'])
            ->ordered()
            ->get();

        $stats = [
            'total' => $hierarchies->count(),
            'clans' => $hierarchies->where('type', 'clan')->count(),
            'squads' => $hierarchies->where('type', 'squad')->count(),
            'platoons' => $hierarchies->where('type', 'platoon')->count(),
            'batches' => $hierarchies->where('type', 'batch')->count(),
            'teams' => $hierarchies->where('type', 'team')->count(),
        ];

        $leaders = User::whereIn('role', User::leaderRoles())
            ->orderBy('name')
            ->get();

        $typeLabels = self::TYPE_LABELS;
        $recommendedParents = [
            'clan' => 'Top-level structure',
            'squad' => 'Usually placed under a clan',
            'platoon' => 'Usually placed under a squad',
            'batch' => 'Usually placed under a platoon',
            'team' => 'Usually placed under a batch',
        ];
        $expectedLeaderLabels = [
            'clan' => User::roleOptions()[User::ROLE_CLAN_LEADER],
            'squad' => User::roleOptions()[User::ROLE_SQUAD_LEADER],
            'platoon' => User::roleOptions()[User::ROLE_PLATOON_LEADER],
            'batch' => User::roleOptions()[User::ROLE_BATCH_LEADER],
            'team' => User::roleOptions()[User::ROLE_TEAM_LEADER],
        ];

        // Pre-compute display paths from the in-memory collection to avoid
        // O(n²) DB calls when nested loops render each hierarchy's path.
        $idToName = $hierarchies->pluck('name', 'id');
        $idToParent = $hierarchies->pluck('parent_id', 'id');
        $displayPaths = $hierarchies->mapWithKeys(function ($h) use ($idToName, $idToParent) {
            $segments = [];
            $currentId = $h->id;
            $visited = [];
            while ($currentId && !in_array($currentId, $visited)) {
                $visited[] = $currentId;
                array_unshift($segments, $idToName[$currentId]);
                $currentId = $idToParent[$currentId] ?? null;
            }
            return [$h->id => implode(' / ', $segments)];
        });

        return view('admin.hierarchies.index', compact(
            'hierarchies',
            'stats',
            'leaders',
            'typeLabels',
            'recommendedParents',
            'expectedLeaderLabels',
            'displayPaths',
        ));
    }

    public function store(Request $request)
    {
        [$validated, $leader] = $this->validateHierarchyData($request);

        DB::transaction(function () use ($validated, $leader) {
            $hierarchy = Hierarchy::create([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'parent_id' => $validated['parent_id'],
                'leader_id' => $leader->id,
            ]);

            $leader->update([
                'hierarchy_id' => $hierarchy->id,
            ]);
        });

        return redirect()->route('admin.hierarchies.index')
            ->with('success', 'Hierarchy created and leader assigned successfully.');
    }

    public function update(Request $request, Hierarchy $hierarchy)
    {
        [$validated, $leader] = $this->validateHierarchyData($request, $hierarchy);

        DB::transaction(function () use ($validated, $leader, $hierarchy) {
            $previousLeader = $hierarchy->leader;

            $hierarchy->update([
                'name' => $validated['name'],
                'parent_id' => $validated['parent_id'],
                'leader_id' => $leader->id,
            ]);

            if ($previousLeader && $previousLeader->id !== $leader->id && $previousLeader->hierarchy_id === $hierarchy->id) {
                $previousLeader->update([
                    'hierarchy_id' => null,
                ]);
            }

            $leader->update([
                'hierarchy_id' => $hierarchy->id,
            ]);
        });

        return redirect()->route('admin.hierarchies.index')
            ->with('success', "{$hierarchy->name} was updated successfully.");
    }

    private function validateHierarchyData(Request $request, ?Hierarchy $hierarchy = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:'.implode(',', array_keys(self::TYPE_LABELS))],
            'parent_id' => ['nullable', 'exists:hierarchies,id'],
            'leader_id' => ['required', 'exists:users,id'],
        ]);

        $parent = isset($validated['parent_id']) && $validated['parent_id']
            ? Hierarchy::findOrFail($validated['parent_id'])
            : null;
        $leader = User::findOrFail($validated['leader_id']);

        $expectedRole = $this->expectedLeaderRoleForType($validated['type']);

        if ($leader->role !== $expectedRole) {
            throw ValidationException::withMessages([
                'leader_id' => 'The selected leader role does not match this hierarchy type.',
            ]);
        }

        $existingLeadership = Hierarchy::where('leader_id', $leader->id)
            ->when($hierarchy, fn ($query) => $query->where('id', '!=', $hierarchy->id))
            ->first();

        if ($existingLeadership) {
            throw ValidationException::withMessages([
                'leader_id' => "{$leader->name} already leads {$existingLeadership->name}.",
            ]);
        }

        if ($parent && $hierarchy && $parent->id === $hierarchy->id) {
            throw ValidationException::withMessages([
                'parent_id' => 'A hierarchy cannot be its own parent.',
            ]);
        }

        if ($parent && $hierarchy && $hierarchy->descendantIdsIncludingSelf()->contains($parent->id)) {
            throw ValidationException::withMessages([
                'parent_id' => 'Please choose a parent outside this hierarchy branch.',
            ]);
        }

        if ($parent && ! $this->isAllowedParentType($validated['type'], $parent->type)) {
            throw ValidationException::withMessages([
                'parent_id' => 'That parent type does not fit the recommended structure for this level.',
            ]);
        }

        return [$validated, $leader];
    }

    private function expectedLeaderRoleForType(string $type): string
    {
        return match ($type) {
            'clan' => User::ROLE_CLAN_LEADER,
            'squad' => User::ROLE_SQUAD_LEADER,
            'platoon' => User::ROLE_PLATOON_LEADER,
            'batch' => User::ROLE_BATCH_LEADER,
            'team' => User::ROLE_TEAM_LEADER,
        };
    }

    private function isAllowedParentType(string $type, string $parentType): bool
    {
        return match ($type) {
            'clan' => false,
            'squad' => in_array($parentType, ['clan'], true),
            'platoon' => in_array($parentType, ['squad', 'clan'], true),
            'batch' => in_array($parentType, ['platoon', 'squad'], true),
            'team' => in_array($parentType, ['batch'], true),
            default => false,
        };
    }
}
