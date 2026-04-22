<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hierarchy;
use App\Models\ReadingPlan;
use App\Models\SystemRole;
use App\Models\User;
use App\Services\Auditing\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AdminUserController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->integer('per_page', 15);
        $allowedPerPage = [15, 25, 50, 100];

        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 15;
        }

        $query = User::with(['hierarchy.parent'])
            ->withCount(['readingPlans', 'readingProgress']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->whereNotNull('email_verified_at');
            } elseif ($request->status === 'inactive') {
                $query->whereNull('email_verified_at');
            }
        }

        // Filter by reading plan enrollment
        if ($request->filled('reading_plan')) {
            $query->whereHas('readingPlans', function ($q) use ($request) {
                $q->where('reading_plans.id', $request->reading_plan);
            });
        }

        if ($request->filled('hierarchy_id')) {
            $query->where('hierarchy_id', $request->integer('hierarchy_id'));
        }

        $users = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
        $readingPlans = ReadingPlan::query()->orderBy('name')->get();
        $hierarchies = Hierarchy::with('parent')->ordered()->get();
        $hierarchyDisplayPaths = Hierarchy::buildDisplayPaths($hierarchies);
        $prefillTargetTeamIds = collect($request->input('target_team_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();
        $prefillSourceTeam = $request->filled('source_team_id')
            ? Hierarchy::query()->find($request->integer('source_team_id'))
            : null;
        $prefillSuggestedMoveCount = max($request->integer('suggested_move_count'), 0);

        // Get summary statistics
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::whereNotNull('email_verified_at')->count(),
            'leaders' => User::whereIn('role', User::leaderRoles())->count(),
            'members' => User::where('role', User::ROLE_MEMBER)->count(),
            'roles' => User::roleOptions(),
        ];
        $multipleLegacyAdmins = User::where('role', User::ROLE_ADMIN)->count() > 1;

        return view('admin.users.index', compact(
            'users',
            'readingPlans',
            'hierarchies',
            'hierarchyDisplayPaths',
            'stats',
            'perPage',
            'allowedPerPage',
            'prefillTargetTeamIds',
            'prefillSourceTeam',
            'prefillSuggestedMoveCount',
            'multipleLegacyAdmins',
        ));
    }

    public function show(User $user)
    {
        $user->load([
            'hierarchy.parent',
            'readingPlans.dailyReadings',
            'readingProgress.dailyReading.readingPlan',
            'systemRoles.permissions',
        ]);

        // Get user's reading statistics
        $readingStats = [
            'total_plans' => $user->readingPlans->count(),
            'completed_readings' => $user->readingProgress->count(),
            'completion_rate' => $this->calculateCompletionRate($user),
            'current_streak' => $this->calculateCurrentStreak($user),
            'longest_streak' => $this->calculateLongestStreak($user),
        ];

        // Get recent activity
        $recentActivity = $user->readingProgress()
            ->with('dailyReading.readingPlan')
            ->orderBy('completed_date', 'desc')
            ->limit(10)
            ->get();

        return view('admin.users.show', compact('user', 'readingStats', 'recentActivity'));
    }

    public function create()
    {
        $readingPlans = ReadingPlan::all();
        $hierarchies = Hierarchy::with(['parent', 'leader'])->ordered()->get();
        $roleOptions = User::roleOptions();
        $deliveryOptions = User::messageDeliveryOptions();
        $systemRoles = SystemRole::query()->with('permissions')->orderBy('name')->get();

        return view('admin.users.create', compact('readingPlans', 'hierarchies', 'roleOptions', 'deliveryOptions', 'systemRoles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone_number' => ['nullable', 'string', 'max:255', 'unique:users,phone_number'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:'.implode(',', User::assignableRoles())],
            'hierarchy_id' => ['nullable', 'exists:hierarchies,id'],
            'message_delivery_preference' => ['nullable', 'in:'.implode(',', array_keys(User::messageDeliveryOptions()))],
            'message_delivery_preference_locked' => ['nullable', 'boolean'],
            'reading_plans' => ['array'],
            'reading_plans.*' => ['exists:reading_plans,id'],
            'system_role_ids' => ['array'],
            'system_role_ids.*' => ['exists:system_roles,id'],
        ]);

        $selectedHierarchy = $request->filled('hierarchy_id')
            ? Hierarchy::findOrFail($request->integer('hierarchy_id'))
            : null;

        $this->validateHierarchyRoleAlignment($request->input('role'), $selectedHierarchy);
        $this->guardAgainstOccupiedLeadershipAssignment(null, $request->input('role'), $selectedHierarchy);

        $createdUser = null;

        DB::transaction(function () use ($request, $selectedHierarchy, &$createdUser) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone_number' => $request->filled('phone_number') ? $request->phone_number : null,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'hierarchy_id' => $selectedHierarchy?->id,
                'message_delivery_preference' => $request->input('message_delivery_preference') ?: null,
                'message_delivery_preference_locked' => $request->boolean('message_delivery_preference_locked'),
                'email_verified_at' => now(), // Auto-verify admin created users
            ]);

            $this->syncLeadershipAssignment($user, $selectedHierarchy, $request->role);
            $user->systemRoles()->sync($request->input('system_role_ids', []));

            // Attach reading plans if selected
            if ($request->filled('reading_plans')) {
                foreach ($request->reading_plans as $planId) {
                    $user->readingPlans()->attach($planId, [
                        'joined_date' => now()->toDateString(),
                        'current_day' => 1,
                    ]);
                }
            }

            $createdUser = $user->fresh(['hierarchy.parent', 'systemRoles']);
        });

        if ($createdUser) {
            $this->auditLogger->log(
                'users.created',
                $request->user(),
                $createdUser,
                [
                    'role' => $createdUser->role,
                    'hierarchy' => $createdUser->hierarchy?->displayPath(),
                    'system_roles' => $createdUser->systemRoles->pluck('name')->all(),
                ],
                "Created {$createdUser->name}.",
            );
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $readingPlans = ReadingPlan::all();
        $userPlanIds = $user->readingPlans->pluck('id')->toArray();
        $hierarchies = Hierarchy::with(['parent', 'leader'])->ordered()->get();
        $roleOptions = User::roleOptions();
        $deliveryOptions = User::messageDeliveryOptions();
        $systemRoles = SystemRole::query()->with('permissions')->orderBy('name')->get();
        $userSystemRoleIds = $user->systemRoles()->pluck('system_roles.id')->toArray();

        return view('admin.users.edit', compact('user', 'readingPlans', 'userPlanIds', 'hierarchies', 'roleOptions', 'deliveryOptions', 'systemRoles', 'userSystemRoleIds'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone_number' => ['nullable', 'string', 'max:255', 'unique:users,phone_number,'.$user->id],
            'role' => ['required', 'in:'.implode(',', User::assignableRoles())],
            'hierarchy_id' => ['nullable', 'exists:hierarchies,id'],
            'message_delivery_preference' => ['nullable', 'in:'.implode(',', array_keys(User::messageDeliveryOptions()))],
            'message_delivery_preference_locked' => ['nullable', 'boolean'],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'reading_plans' => ['array'],
            'reading_plans.*' => ['exists:reading_plans,id'],
            'system_role_ids' => ['array'],
            'system_role_ids.*' => ['exists:system_roles,id'],
        ]);

        $selectedHierarchy = $request->filled('hierarchy_id')
            ? Hierarchy::findOrFail($request->integer('hierarchy_id'))
            : null;

        $this->validateHierarchyRoleAlignment($request->input('role'), $selectedHierarchy);
        $this->guardAgainstUnsafeLeadershipRemoval($user, $request->input('role'), $selectedHierarchy);
        $this->guardAgainstRemovingFinalLegacyAdmin($user, $request->input('role'));
        $this->guardAgainstOccupiedLeadershipAssignment($user, $request->input('role'), $selectedHierarchy);

        $originalState = [
            'role' => $user->role,
            'hierarchy' => $user->hierarchy?->displayPath(),
            'message_delivery_preference' => $user->message_delivery_preference,
            'system_roles' => $user->systemRoles()->pluck('system_roles.name')->all(),
        ];

        DB::transaction(function () use ($request, $user, $selectedHierarchy) {
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone_number' => $request->filled('phone_number') ? $request->phone_number : null,
                'role' => $request->role,
                'hierarchy_id' => $selectedHierarchy?->id,
                'message_delivery_preference' => $request->input('message_delivery_preference') ?: null,
                'message_delivery_preference_locked' => $request->boolean('message_delivery_preference_locked'),
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);
            $user->refresh();
            $this->syncLeadershipAssignment($user, $selectedHierarchy, $request->role);
            $user->systemRoles()->sync($request->input('system_role_ids', []));

            // Sync reading plans
            if ($request->has('reading_plans')) {
                $syncData = [];
                foreach ($request->reading_plans as $planId) {
                    // Preserve existing pivot data or set defaults for new plans
                    $existingPivot = $user->readingPlans()->where('reading_plans.id', $planId)->first();
                    $syncData[$planId] = $existingPivot ?
                        $existingPivot->pivot->toArray() :
                        ['joined_date' => now()->toDateString(), 'current_day' => 1];
                }
                $user->readingPlans()->sync($syncData);
            } else {
                $user->readingPlans()->detach();
            }
        });

        $user->refresh()->load(['hierarchy.parent', 'systemRoles']);
        $this->auditLogger->log(
            'users.updated',
            $request->user(),
            $user,
            [
                'previous_role' => $originalState['role'],
                'new_role' => $user->role,
                'previous_hierarchy' => $originalState['hierarchy'],
                'new_hierarchy' => $user->hierarchy?->displayPath(),
                'previous_delivery_preference' => $originalState['message_delivery_preference'],
                'new_delivery_preference' => $user->message_delivery_preference,
                'previous_system_roles' => $originalState['system_roles'],
                'new_system_roles' => $user->systemRoles->pluck('name')->all(),
            ],
            "Updated {$user->name}.",
        );

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        // Prevent deletion of the last admin
        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Cannot delete the last admin user.');
        }

        $user->load('hierarchy.parent');
        $user->delete();

        $this->auditLogger->log(
            'users.deleted',
            request()->user(),
            $user,
            [
                'role' => $user->role,
                'hierarchy' => $user->hierarchy?->displayPath(),
            ],
            "Deleted {$user->name}.",
        );

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => ['required', 'in:delete,assign_plan,remove_plan,change_role,assign_hierarchy,clear_hierarchy,distribute_evenly,promote_current_assignment,demote_from_leadership'],
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['exists:users,id'],
            'reading_plan_id' => ['required_if:action,assign_plan,remove_plan', 'exists:reading_plans,id'],
            'role' => ['required_if:action,change_role', 'in:'.implode(',', User::assignableRoles())],
            'hierarchy_id' => ['required_if:action,assign_hierarchy', 'nullable', 'exists:hierarchies,id'],
            'target_team_ids' => ['required_if:action,distribute_evenly', 'array'],
            'target_team_ids.*' => ['exists:hierarchies,id'],
        ]);

        $users = User::whereIn('id', $request->user_ids)->get();
        $selectedHierarchy = $request->filled('hierarchy_id')
            ? Hierarchy::findOrFail($request->integer('hierarchy_id'))
            : null;

        switch ($request->action) {
            case 'delete':
                $deletedNames = $users->pluck('name')->all();
                $users->each->delete();
                $this->auditLogger->log(
                    'users.bulk_deleted',
                    $request->user(),
                    null,
                    [
                        'count' => count($deletedNames),
                        'users' => $deletedNames,
                    ],
                    'Deleted users from the directory bulk workflow.',
                );
                $message = 'Selected users deleted successfully.';
                break;

            case 'assign_plan':
                foreach ($users as $user) {
                    $user->readingPlans()->syncWithoutDetaching([
                        $request->reading_plan_id => [
                            'joined_date' => now()->toDateString(),
                            'current_day' => 1,
                        ],
                    ]);
                }
                $this->auditLogger->log(
                    'users.plans_assigned',
                    $request->user(),
                    ReadingPlan::query()->find($request->reading_plan_id),
                    [
                        'count' => $users->count(),
                        'users' => $users->pluck('name')->all(),
                    ],
                    'Assigned a reading plan through the directory bulk workflow.',
                );
                $message = 'Reading plan assigned to selected users.';
                break;

            case 'remove_plan':
                foreach ($users as $user) {
                    $user->readingPlans()->detach($request->reading_plan_id);
                }
                $this->auditLogger->log(
                    'users.plans_removed',
                    $request->user(),
                    ReadingPlan::query()->find($request->reading_plan_id),
                    [
                        'count' => $users->count(),
                        'users' => $users->pluck('name')->all(),
                    ],
                    'Removed a reading plan through the directory bulk workflow.',
                );
                $message = 'Reading plan removed from selected users.';
                break;

            case 'change_role':
                $users->each(function ($user) use ($request) {
                    $selectedHierarchy = $user->hierarchy()->first();
                    $this->validateHierarchyRoleAlignment($request->role, $selectedHierarchy);
                    $this->guardAgainstUnsafeLeadershipRemoval($user, $request->role, $selectedHierarchy);
                    $this->guardAgainstRemovingFinalLegacyAdmin($user, $request->role);
                    $this->guardAgainstOccupiedLeadershipAssignment($user, $request->role, $selectedHierarchy);
                    $user->update(['role' => $request->role]);
                    $user->refresh();
                    $this->syncLeadershipAssignment($user, $selectedHierarchy, $request->role);
                });
                $this->auditLogger->log(
                    'users.roles_changed',
                    $request->user(),
                    null,
                    [
                        'new_role' => $request->role,
                        'count' => $users->count(),
                        'users' => $users->pluck('name')->all(),
                    ],
                    'Changed user roles through the directory bulk workflow.',
                );
                $message = 'Role updated for selected users.';
                break;

            case 'assign_hierarchy':
                DB::transaction(function () use ($users, $selectedHierarchy) {
                    foreach ($users as $user) {
                        $this->validateHierarchyRoleAlignment($user->role, $selectedHierarchy);
                        $this->guardAgainstUnsafeLeadershipRemoval($user, $user->role, $selectedHierarchy);
                        $this->guardAgainstOccupiedLeadershipAssignment($user, $user->role, $selectedHierarchy);

                        $user->update([
                            'hierarchy_id' => $selectedHierarchy?->id,
                        ]);

                        $user->refresh();
                        $this->syncLeadershipAssignment($user, $selectedHierarchy, $user->role);
                    }
                });
                $this->auditLogger->log(
                    'users.hierarchy_assigned',
                    $request->user(),
                    $selectedHierarchy,
                    [
                        'count' => $users->count(),
                        'users' => $users->pluck('name')->all(),
                    ],
                    'Moved users into a hierarchy through the directory bulk workflow.',
                );
                $message = 'Selected users moved into the chosen group.';
                break;

            case 'clear_hierarchy':
                DB::transaction(function () use ($users) {
                    foreach ($users as $user) {
                        $this->guardAgainstUnsafeLeadershipRemoval($user, $user->role, null);

                        $user->update([
                            'hierarchy_id' => null,
                        ]);
                    }
                });
                $this->auditLogger->log(
                    'users.hierarchy_cleared',
                    $request->user(),
                    null,
                    [
                        'count' => $users->count(),
                        'users' => $users->pluck('name')->all(),
                    ],
                    'Cleared hierarchy assignments through the directory bulk workflow.',
                );
                $message = 'Group assignment cleared for selected users.';
                break;

            case 'distribute_evenly':
                $teams = Hierarchy::query()
                    ->withCount('members')
                    ->whereIn('id', $request->input('target_team_ids', []))
                    ->get();

                $this->validateDistributionTargets($users, $teams);

                DB::transaction(function () use ($users, $teams, $request) {
                    $balancedTeams = $teams->sortBy([
                        ['members_count', 'asc'],
                        ['name', 'asc'],
                    ])->values();

                    foreach ($users as $user) {
                        $team = $balancedTeams->sortBy([
                            ['members_count', 'asc'],
                            ['name', 'asc'],
                        ])->first();
                        $previousHierarchy = $user->hierarchy;

                        $user->update([
                            'hierarchy_id' => $team->id,
                        ]);

                        $team->members_count++;

                        $this->auditLogger->log(
                            'hierarchy.member_rebalanced',
                            $request->user(),
                            $user->fresh('hierarchy.parent'),
                            [
                                'previous_hierarchy' => $previousHierarchy?->displayPath(),
                                'new_hierarchy' => $team->displayPath(),
                            ],
                            "Moved {$user->name} to {$team->name} through balanced team distribution.",
                        );
                    }
                });

                $message = 'Selected members were distributed evenly across the chosen teams.';
                break;

            case 'promote_current_assignment':
                DB::transaction(function () use ($users, $request) {
                    foreach ($users as $user) {
                        $this->promoteUserIntoCurrentAssignment($user, $request->user());
                    }
                });

                $message = 'Selected users were promoted into the vacant leadership slots for their current assignments.';
                break;

            case 'demote_from_leadership':
                DB::transaction(function () use ($users, $request) {
                    foreach ($users as $user) {
                        $this->demoteLeaderFromDirectory($user, $request->user());
                    }
                });

                $message = 'Selected leaders were demoted safely through the directory workflow.';
                break;
        }

        return redirect()->route('admin.users.index')
            ->with('success', $message);
    }

    private function calculateCompletionRate(User $user)
    {
        $totalReadings = $user->readingPlans->sum(function ($plan) {
            return $plan->dailyReadings->count();
        });

        if ($totalReadings === 0) {
            return 0;
        }

        $completedReadings = $user->readingProgress->count();

        return round(($completedReadings / $totalReadings) * 100, 1);
    }

    private function calculateCurrentStreak(User $user)
    {
        $progress = $user->readingProgress()
            ->orderBy('completed_date', 'desc')
            ->pluck('completed_date')
            ->map(function ($date) {
                return Carbon::parse($date);
            });

        if ($progress->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $currentDate = Carbon::today();

        foreach ($progress as $date) {
            if ($date->isSameDay($currentDate) || $date->isSameDay($currentDate->subDay())) {
                $streak++;
                $currentDate = $date;
            } else {
                break;
            }
        }

        return $streak;
    }

    private function calculateLongestStreak(User $user)
    {
        $progress = $user->readingProgress()
            ->orderBy('completed_date', 'asc')
            ->pluck('completed_date')
            ->map(function ($date) {
                return Carbon::parse($date);
            });

        if ($progress->isEmpty()) {
            return 0;
        }

        $longestStreak = 1;
        $currentStreak = 1;

        for ($i = 1; $i < $progress->count(); $i++) {
            if ($progress[$i]->diffInDays($progress[$i - 1]) === 1) {
                $currentStreak++;
                $longestStreak = max($longestStreak, $currentStreak);
            } else {
                $currentStreak = 1;
            }
        }

        return $longestStreak;
    }

    private function validateHierarchyRoleAlignment(string $role, ?Hierarchy $hierarchy): void
    {
        $expectedHierarchyType = User::hierarchyTypeForRole($role);

        if (in_array($role, User::leaderRoles(), true) && ! $hierarchy) {
            throw ValidationException::withMessages([
                'hierarchy_id' => 'Please choose the hierarchy this leader should oversee.',
            ]);
        }

        if (! $hierarchy) {
            return;
        }

        if ($role === User::ROLE_MEMBER && $hierarchy->type !== 'team') {
            throw ValidationException::withMessages([
                'hierarchy_id' => 'Members should be assigned to a team.',
            ]);
        }

        if ($expectedHierarchyType && $hierarchy->type !== $expectedHierarchyType) {
            throw ValidationException::withMessages([
                'hierarchy_id' => 'The selected hierarchy does not match this role.',
            ]);
        }
    }

    private function syncLeadershipAssignment(User $user, ?Hierarchy $hierarchy, string $role): void
    {
        if (! $hierarchy || ! in_array($role, User::leaderRoles(), true)) {
            return;
        }

        if (User::hierarchyTypeForRole($role) !== $hierarchy->type) {
            return;
        }

        $hierarchy->update([
            'leader_id' => $user->id,
        ]);
    }

    private function guardAgainstOccupiedLeadershipAssignment(?User $user, string $role, ?Hierarchy $hierarchy): void
    {
        if (! $hierarchy || ! in_array($role, User::leaderRoles(), true)) {
            return;
        }

        if ($hierarchy->leader_id && $hierarchy->leader_id !== $user?->id) {
            throw ValidationException::withMessages([
                'hierarchy_id' => "Assign a replacement through the hierarchy workflow before placing another leader into {$hierarchy->name}.",
            ]);
        }
    }

    private function guardAgainstUnsafeLeadershipRemoval(User $user, string $newRole, ?Hierarchy $newHierarchy): void
    {
        $currentLeadershipHierarchy = $user->currentLeadershipHierarchy();

        if (! $currentLeadershipHierarchy || $currentLeadershipHierarchy->leader_id !== $user->id) {
            return;
        }

        $staysLeaderOfSameHierarchy = in_array($newRole, User::leaderRoles(), true)
            && $newHierarchy
            && $newHierarchy->id === $currentLeadershipHierarchy->id
            && User::hierarchyTypeForRole($newRole) === $currentLeadershipHierarchy->type;

        if ($staysLeaderOfSameHierarchy) {
            return;
        }

        throw ValidationException::withMessages([
            'role' => "Assign a replacement leader to {$currentLeadershipHierarchy->name} before moving or demoting this user.",
        ]);
    }

    private function guardAgainstRemovingFinalLegacyAdmin(User $user, string $newRole): void
    {
        if ($user->role !== User::ROLE_ADMIN || $newRole === User::ROLE_ADMIN) {
            return;
        }

        if (User::query()->where('role', User::ROLE_ADMIN)->count() > 1) {
            return;
        }

        throw ValidationException::withMessages([
            'role' => 'Assign another legacy admin before changing the final admin account to a different hierarchy role.',
        ]);
    }

    private function validateDistributionTargets($users, $teams): void
    {
        if ($teams->count() < 2) {
            throw ValidationException::withMessages([
                'target_team_ids' => 'Choose at least two teams to distribute users across.',
            ]);
        }

        if ($teams->contains(fn (Hierarchy $hierarchy) => $hierarchy->type !== 'team')) {
            throw ValidationException::withMessages([
                'target_team_ids' => 'Balanced distribution only works across teams.',
            ]);
        }

        if ($users->contains(fn (User $user) => $user->role !== User::ROLE_MEMBER)) {
            throw ValidationException::withMessages([
                'user_ids' => 'Only members can be distributed evenly across teams.',
            ]);
        }

        if ($teams->pluck('parent_id')->unique()->count() > 1) {
            throw ValidationException::withMessages([
                'target_team_ids' => 'Choose teams from the same batch when distributing members evenly.',
            ]);
        }
    }

    private function promoteUserIntoCurrentAssignment(User $user, User $actor): void
    {
        if ($user->role === User::ROLE_ADMIN) {
            throw ValidationException::withMessages([
                'user_ids' => 'Legacy admin accounts cannot be promoted through the bulk directory workflow.',
            ]);
        }

        $hierarchy = $user->hierarchy()->first();

        if (! $hierarchy) {
            throw ValidationException::withMessages([
                'user_ids' => "{$user->name} is not assigned to a hierarchy that can be promoted.",
            ]);
        }

        if ($hierarchy->leader_id) {
            throw ValidationException::withMessages([
                'user_ids' => "{$hierarchy->name} already has a leader assigned.",
            ]);
        }

        if (Hierarchy::query()->where('leader_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'user_ids' => "{$user->name} already leads another hierarchy.",
            ]);
        }

        $newRole = $this->expectedLeaderRoleForHierarchy($hierarchy);

        $user->update([
            'role' => $newRole,
            'hierarchy_id' => $hierarchy->id,
        ]);

        $hierarchy->update([
            'leader_id' => $user->id,
        ]);

        $this->auditLogger->log(
            'hierarchy.leader_promoted_from_directory',
            $actor,
            $hierarchy->fresh('leader'),
            [
                'promoted_user' => $user->name,
                'new_role' => $newRole,
                'hierarchy' => $hierarchy->displayPath(),
            ],
            "Promoted {$user->name} into leadership for {$hierarchy->name} from the user directory.",
        );
    }

    private function demoteLeaderFromDirectory(User $user, User $actor): void
    {
        $leadHierarchy = $user->currentLeadershipHierarchy();

        if (! $leadHierarchy || $leadHierarchy->leader_id !== $user->id) {
            throw ValidationException::withMessages([
                'user_ids' => "{$user->name} is not the current leader of an assigned hierarchy.",
            ]);
        }

        $targetTeam = $leadHierarchy->type === 'team'
            ? $leadHierarchy
            : $leadHierarchy->descendantTeamsIncludingSelf()->sortBy([
                [fn (Hierarchy $hierarchy) => $hierarchy->members()->count(), 'asc'],
                ['name', 'asc'],
            ])->first();

        if (! $targetTeam) {
            throw ValidationException::withMessages([
                'user_ids' => "{$leadHierarchy->name} has no descendant team available for a safe demotion.",
            ]);
        }

        $leadHierarchy->update([
            'leader_id' => null,
        ]);

        $user->update([
            'role' => User::ROLE_MEMBER,
            'hierarchy_id' => $targetTeam->id,
        ]);

        $this->auditLogger->log(
            'hierarchy.leader_demoted_from_directory',
            $actor,
            $user->fresh('hierarchy.parent'),
            [
                'former_hierarchy' => $leadHierarchy->displayPath(),
                'target_team' => $targetTeam->displayPath(),
            ],
            "Demoted {$user->name} from {$leadHierarchy->name} through the user directory.",
        );
    }

    private function expectedLeaderRoleForHierarchy(Hierarchy $hierarchy): string
    {
        return match ($hierarchy->type) {
            'clan' => User::ROLE_CLAN_LEADER,
            'squad' => User::ROLE_SQUAD_LEADER,
            'platoon' => User::ROLE_PLATOON_LEADER,
            'batch' => User::ROLE_BATCH_LEADER,
            default => User::ROLE_TEAM_LEADER,
        };
    }
}
