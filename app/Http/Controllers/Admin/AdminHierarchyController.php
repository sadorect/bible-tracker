<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hierarchy;
use App\Models\User;
use App\Services\Auditing\AuditLogger;
use App\Services\Hierarchy\HierarchyWorkflowService;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminHierarchyController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly HierarchyWorkflowService $workflowService,
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
        $descendantTeams = $this->descendantTeamsByHierarchy($hierarchies);
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
        $displayPaths = Hierarchy::buildDisplayPaths($hierarchies);

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

    private function descendantTeamsByHierarchy(Collection $hierarchies): Collection
    {
        $hierarchiesById = $hierarchies->keyBy('id');
        $childrenByParentId = $hierarchies->groupBy('parent_id');
        $teamIdsByHierarchyId = [];

        $resolveTeamIds = function (int $hierarchyId) use (&$resolveTeamIds, &$teamIdsByHierarchyId, $childrenByParentId, $hierarchiesById): Collection {
            if (array_key_exists($hierarchyId, $teamIdsByHierarchyId)) {
                return $teamIdsByHierarchyId[$hierarchyId];
            }

            $hierarchy = $hierarchiesById->get($hierarchyId);
            $teamIds = collect($hierarchy && $hierarchy->type === 'team' ? [$hierarchyId] : []);

            foreach ($childrenByParentId->get($hierarchyId, collect()) as $child) {
                $teamIds = $teamIds->merge($resolveTeamIds($child->id));
            }

            return $teamIdsByHierarchyId[$hierarchyId] = $teamIds->unique()->values();
        };

        return $hierarchies->mapWithKeys(function (Hierarchy $hierarchy) use ($hierarchies, $resolveTeamIds) {
            $teamIds = $resolveTeamIds($hierarchy->id);

            return [
                $hierarchy->id => $hierarchies
                    ->whereIn('id', $teamIds)
                    ->where('type', 'team')
                    ->values(),
            ];
        });
    }

    public function previewMigration(Request $request)
    {
        $validated = $request->validate([
            'source_hierarchy_id' => ['required', 'exists:hierarchies,id'],
            'destination_parent_id' => ['required', 'exists:hierarchies,id'],
        ]);

        $source = Hierarchy::query()->findOrFail($validated['source_hierarchy_id']);
        $destinationParent = Hierarchy::query()->findOrFail($validated['destination_parent_id']);
        $preview = $this->workflowService->previewHorizontalMigration($source, $destinationParent);

        return view('admin.hierarchies.workflows.migration-preview', [
            'preview' => $preview,
            'typeLabels' => self::TYPE_LABELS,
        ]);
    }

    public function showVacancyResolution(Hierarchy $hierarchy)
    {
        $hierarchy->load(['parent.parent.parent.parent', 'members']);

        return view('admin.hierarchies.resolve-vacancy', [
            'hierarchy' => $hierarchy,
            'typeLabels' => self::TYPE_LABELS,
            'assignableLeaders' => $this->assignableLeadersForVacancy($hierarchy),
            'promotableMembers' => $this->promotableMembersForVacancy($hierarchy),
        ]);
    }

    public function resolveVacancy(Request $request, Hierarchy $hierarchy)
    {
        if ($hierarchy->leader_id) {
            throw ValidationException::withMessages([
                'leader_id' => "{$hierarchy->name} already has a leader assigned.",
            ]);
        }

        $validated = $request->validate([
            'resolution' => ['required', 'in:assign,promote'],
            'leader_id' => ['nullable', 'exists:users,id'],
            'promote_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $result = DB::transaction(function () use ($request, $hierarchy, $validated) {
            if ($validated['resolution'] === 'assign') {
                $leader = User::query()->findOrFail($validated['leader_id']);
                $this->validateAssignableLeaderForVacancy($leader, $hierarchy);

                $hierarchy->update([
                    'leader_id' => $leader->id,
                ]);

                $leader->update([
                    'hierarchy_id' => $hierarchy->id,
                ]);

                $this->auditLogger->log(
                    'hierarchy.vacancy_resolved_by_assignment',
                    $request->user(),
                    $hierarchy->fresh('leader'),
                    [
                        'assigned_leader' => $leader->name,
                        'hierarchy' => $hierarchy->displayPath(),
                    ],
                    "Assigned {$leader->name} to resolve the vacancy in {$hierarchy->name}.",
                );

                return "{$leader->name} is now leading {$hierarchy->name}.";
            }

            $candidate = User::query()->findOrFail($validated['promote_user_id']);

            if ((int) $candidate->hierarchy_id !== $hierarchy->id) {
                throw ValidationException::withMessages([
                    'promote_user_id' => 'Choose someone already assigned to this hierarchy for direct promotion.',
                ]);
            }

            if (Hierarchy::query()->where('leader_id', $candidate->id)->exists()) {
                throw ValidationException::withMessages([
                    'promote_user_id' => "{$candidate->name} already leads another hierarchy.",
                ]);
            }

            $candidate->update([
                'role' => $this->expectedLeaderRoleForType($hierarchy->type),
                'hierarchy_id' => $hierarchy->id,
            ]);

            $hierarchy->update([
                'leader_id' => $candidate->id,
            ]);

            $this->auditLogger->log(
                'hierarchy.vacancy_resolved_by_promotion',
                $request->user(),
                $hierarchy->fresh('leader'),
                [
                    'promoted_user' => $candidate->name,
                    'new_role' => $candidate->role,
                    'hierarchy' => $hierarchy->displayPath(),
                ],
                "Promoted {$candidate->name} to resolve the vacancy in {$hierarchy->name}.",
            );

            return "{$candidate->name} was promoted into {$hierarchy->name}.";
        });

        return redirect()->route('admin.hierarchies.show', $hierarchy)
            ->with('success', $result);
    }

    public function executeMigration(Request $request)
    {
        $validated = $request->validate([
            'source_hierarchy_id' => ['required', 'exists:hierarchies,id'],
            'destination_parent_id' => ['required', 'exists:hierarchies,id'],
        ]);

        $source = Hierarchy::query()->findOrFail($validated['source_hierarchy_id']);
        $destinationParent = Hierarchy::query()->findOrFail($validated['destination_parent_id']);

        $result = DB::transaction(fn () => $this->workflowService->executeHorizontalMigration($source, $destinationParent));

        $this->auditLogger->log(
            'hierarchy.branch_migrated',
            $request->user(),
            $result['source'],
            [
                'previous_parent' => $result['previous_parent']?->displayPath(),
                'destination_parent' => $result['destination_parent']->displayPath(),
                'previous_path' => $result['previous_path'],
                'new_path' => $result['new_path'],
                'branch_members' => $result['summary']['branch_members'],
                'branch_leaders' => $result['summary']['branch_leaders'],
                'descendant_groups' => $result['summary']['descendant_groups'],
            ],
            "Moved {$result['source']->name} under {$result['destination_parent']->name}.",
        );

        return redirect()->route('admin.hierarchies.index')
            ->with('success', "{$result['source']->name} was moved under {$result['destination_parent']->name}.");
    }

    public function previewMerge(Request $request)
    {
        $validated = $request->validate([
            'source_hierarchy_id' => ['required', 'exists:hierarchies,id'],
            'target_hierarchy_id' => ['required', 'exists:hierarchies,id'],
        ]);

        $source = Hierarchy::query()->findOrFail($validated['source_hierarchy_id']);
        $target = Hierarchy::query()->findOrFail($validated['target_hierarchy_id']);
        $preview = $this->workflowService->previewSiblingMerge($source, $target);

        return view('admin.hierarchies.workflows.merge-preview', [
            'preview' => $preview,
            'typeLabels' => self::TYPE_LABELS,
        ]);
    }

    public function executeMerge(Request $request)
    {
        $validated = $request->validate([
            'source_hierarchy_id' => ['required', 'exists:hierarchies,id'],
            'target_hierarchy_id' => ['required', 'exists:hierarchies,id'],
            'merged_leader_id' => ['nullable', 'integer'],
            'source_leader_disposition' => ['nullable', 'in:descendant_team,unassign'],
            'source_leader_team_id' => ['nullable', 'integer'],
            'target_leader_disposition' => ['nullable', 'in:descendant_team,unassign'],
            'target_leader_team_id' => ['nullable', 'integer'],
        ]);

        $source = Hierarchy::query()->findOrFail($validated['source_hierarchy_id']);
        $target = Hierarchy::query()->findOrFail($validated['target_hierarchy_id']);

        $result = DB::transaction(fn () => $this->workflowService->executeSiblingMerge($source, $target, $validated));

        $this->auditLogger->log(
            'hierarchy.sibling_merged',
            $request->user(),
            $result['target'],
            [
                'source_name' => $result['source_name'],
                'target_path' => $result['target']->displayPath(),
                'merged_leader_id' => $result['merged_leader_id'],
                'source_leader_disposition' => $result['source_leader_plan']['disposition'],
                'source_leader_team_id' => $result['source_leader_plan']['team_id'],
                'target_leader_disposition' => $result['target_leader_plan']['disposition'],
                'target_leader_team_id' => $result['target_leader_plan']['team_id'],
                'source_branch_members' => $result['summary']['source_branch_members'],
                'source_descendant_groups' => $result['summary']['source_descendant_groups'],
            ],
            "Merged {$result['source_name']} into {$result['target']->name}.",
        );

        return redirect()->route('admin.hierarchies.index')
            ->with('success', "{$result['source_name']} was merged into {$result['target']->name}.");
    }

    public function store(Request $request)
    {
        [$validated, $leader] = $this->validateHierarchyData($request);

        $createdHierarchy = null;

        DB::transaction(function () use ($validated, $leader, &$createdHierarchy) {
            $hierarchy = Hierarchy::create([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'parent_id' => $validated['parent_id'],
                'leader_id' => $leader->id,
            ]);

            $leader->update([
                'hierarchy_id' => $hierarchy->id,
            ]);

            $createdHierarchy = $hierarchy->fresh(['parent', 'leader']);
        });

        if ($createdHierarchy) {
            $this->auditLogger->log(
                'hierarchy.created',
                $request->user(),
                $createdHierarchy,
                [
                    'type' => $createdHierarchy->type,
                    'parent' => $createdHierarchy->parent?->displayPath(),
                    'leader' => $createdHierarchy->leader?->name,
                ],
                "Created {$createdHierarchy->name}.",
            );
        }

        return redirect()->route('admin.hierarchies.index')
            ->with('success', 'Hierarchy created and leader assigned successfully.');
    }

    public function update(Request $request, Hierarchy $hierarchy)
    {
        [$validated, $leader] = $this->validateHierarchyData($request, $hierarchy);
        $previousState = [
            'name' => $hierarchy->name,
            'parent' => $hierarchy->parent?->displayPath(),
            'leader' => $hierarchy->leader?->name,
        ];

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

        $hierarchy->refresh()->load(['parent', 'leader']);
        $this->auditLogger->log(
            'hierarchy.updated',
            $request->user(),
            $hierarchy,
            [
                'previous_name' => $previousState['name'],
                'new_name' => $hierarchy->name,
                'previous_parent' => $previousState['parent'],
                'new_parent' => $hierarchy->parent?->displayPath(),
                'previous_leader' => $previousState['leader'],
                'new_leader' => $hierarchy->leader?->name,
            ],
            "Updated {$hierarchy->name}.",
        );

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

    private function assignableLeadersForVacancy(Hierarchy $hierarchy): Collection
    {
        return User::query()
            ->where('role', $this->expectedLeaderRoleForType($hierarchy->type))
            ->whereDoesntHave('systemRoles', fn ($query) => $query->where('slug', 'super_admin'))
            ->orderBy('name')
            ->get()
            ->reject(fn (User $user) => Hierarchy::query()->where('leader_id', $user->id)->exists())
            ->values();
    }

    private function promotableMembersForVacancy(Hierarchy $hierarchy): Collection
    {
        return User::query()
            ->where('hierarchy_id', $hierarchy->id)
            ->where('role', '!=', User::ROLE_ADMIN)
            ->whereDoesntHave('systemRoles', fn ($query) => $query->where('slug', 'super_admin'))
            ->orderBy('name')
            ->get()
            ->reject(fn (User $user) => Hierarchy::query()->where('leader_id', $user->id)->exists())
            ->values();
    }

    private function validateAssignableLeaderForVacancy(User $leader, Hierarchy $hierarchy): void
    {
        if ($leader->role !== $this->expectedLeaderRoleForType($hierarchy->type)) {
            throw ValidationException::withMessages([
                'leader_id' => 'The selected leader does not match the hierarchy type.',
            ]);
        }

        if (Hierarchy::query()->where('leader_id', $leader->id)->exists()) {
            throw ValidationException::withMessages([
                'leader_id' => "{$leader->name} already leads another hierarchy.",
            ]);
        }
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
