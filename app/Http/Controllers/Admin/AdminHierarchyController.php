<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hierarchy;
use App\Models\User;
use App\Services\Auditing\AuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminHierarchyController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {
    }

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
            'vacant' => $hierarchies->whereNull('leader_id')->count(),
        ];

        $leaders = User::whereIn('role', User::leaderRoles())
            ->orderBy('name')
            ->get();
        $promotableUsers = User::query()
            ->whereNotIn('role', [User::ROLE_ADMIN])
            ->whereDoesntHave('systemRoles', fn ($query) => $query->where('slug', 'super_admin'))
            ->orderBy('name')
            ->get()
            ->groupBy('hierarchy_id');
        $descendantTeams = $hierarchies->mapWithKeys(function (Hierarchy $hierarchy) {
            return [
                $hierarchy->id => Hierarchy::with(['parent'])
                    ->whereIn('id', $hierarchy->descendantTeamsIncludingSelf()->pluck('id'))
                    ->ordered()
                    ->get(),
            ];
        });
        $teamBalanceInsights = $this->buildTeamBalanceInsights($hierarchies);

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
            'promotableUsers',
            'descendantTeams',
            'teamBalanceInsights',
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

    public function promoteLeader(Request $request, Hierarchy $hierarchy)
    {
        if ($hierarchy->leader_id) {
            throw ValidationException::withMessages([
                'promote_user_id' => "Assign a replacement through the edit form before changing the leader for {$hierarchy->name}.",
            ]);
        }

        $validated = $request->validate([
            'promote_user_id' => ['required', 'exists:users,id'],
        ]);

        $user = User::query()->findOrFail($validated['promote_user_id']);

        if (Hierarchy::query()->where('leader_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'promote_user_id' => "{$user->name} already leads another hierarchy.",
            ]);
        }

        DB::transaction(function () use ($request, $hierarchy, $user) {
            $newRole = $this->expectedLeaderRoleForType($hierarchy->type);

            $user->update([
                'role' => $newRole,
                'hierarchy_id' => $hierarchy->id,
            ]);

            $hierarchy->update([
                'leader_id' => $user->id,
            ]);

            $this->auditLogger->log(
                'hierarchy.leader_promoted',
                $request->user(),
                $hierarchy->fresh('leader'),
                [
                    'promoted_user' => $user->name,
                    'new_role' => $user->role,
                    'hierarchy' => $hierarchy->displayPath(),
                ],
                "Promoted {$user->name} to lead {$hierarchy->name}.",
            );
        });

        return redirect()->route('admin.hierarchies.index')
            ->with('success', "{$user->name} is now leading {$hierarchy->name}.");
    }

    public function demoteLeader(Request $request, Hierarchy $hierarchy)
    {
        $leader = $hierarchy->leader;

        if (! $leader) {
            throw ValidationException::withMessages([
                'demote_target_team_id' => 'There is no leader assigned to this hierarchy.',
            ]);
        }

        $validated = $request->validate([
            'demote_target_team_id' => ['nullable', 'exists:hierarchies,id'],
        ]);

        $targetTeam = null;

        if ($hierarchy->type !== 'team') {
            if (empty($validated['demote_target_team_id'])) {
                throw ValidationException::withMessages([
                    'demote_target_team_id' => 'Choose a destination team for this leader before demoting them.',
                ]);
            }

            $targetTeam = Hierarchy::query()->findOrFail($validated['demote_target_team_id']);

            if ($targetTeam->type !== 'team' || ! $hierarchy->descendantIdsIncludingSelf()->contains($targetTeam->id)) {
                throw ValidationException::withMessages([
                    'demote_target_team_id' => 'The destination must be a team within this hierarchy branch.',
                ]);
            }
        } else {
            $targetTeam = $hierarchy;
        }

        DB::transaction(function () use ($request, $hierarchy, $leader, $targetTeam) {
            $hierarchy->update([
                'leader_id' => null,
            ]);

            $leader->update([
                'role' => User::ROLE_MEMBER,
                'hierarchy_id' => $targetTeam?->id,
            ]);

            $this->auditLogger->log(
                'hierarchy.leader_demoted',
                $request->user(),
                $leader->fresh('hierarchy.parent'),
                [
                    'former_hierarchy' => $hierarchy->displayPath(),
                    'target_team' => $targetTeam?->displayPath(),
                ],
                "Demoted {$leader->name} from leading {$hierarchy->name}.",
            );
        });

        return redirect()->route('admin.hierarchies.index')
            ->with('success', "{$leader->name} was demoted and released from {$hierarchy->name}.");
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

    private function buildTeamBalanceInsights(Collection $hierarchies): Collection
    {
        $teams = $hierarchies->where('type', 'team')->values();

        if ($teams->isEmpty()) {
            return collect();
        }

        $memberCounts = User::query()
            ->where('role', User::ROLE_MEMBER)
            ->whereIn('hierarchy_id', $teams->pluck('id'))
            ->selectRaw('hierarchy_id, count(*) as total')
            ->groupBy('hierarchy_id')
            ->pluck('total', 'hierarchy_id');

        return $hierarchies
            ->where('type', 'batch')
            ->map(function (Hierarchy $batch) use ($teams, $memberCounts) {
                $childTeams = $teams
                    ->where('parent_id', $batch->id)
                    ->map(function (Hierarchy $team) use ($memberCounts) {
                        return [
                            'team' => $team,
                            'member_count' => (int) ($memberCounts[$team->id] ?? 0),
                        ];
                    })
                    ->sortBy('member_count')
                    ->values();

                if ($childTeams->count() < 2) {
                    return null;
                }

                $lightest = $childTeams->first();
                $heaviest = $childTeams->last();
                $spread = $heaviest['member_count'] - $lightest['member_count'];

                if ($spread < 2) {
                    return null;
                }

                return [
                    'batch' => $batch,
                    'child_teams' => $childTeams,
                    'spread' => $spread,
                    'suggested_moves' => (int) floor($spread / 2),
                ];
            })
            ->filter()
            ->sortByDesc('spread')
            ->values();
    }
}
