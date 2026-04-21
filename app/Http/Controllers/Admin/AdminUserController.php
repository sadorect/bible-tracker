<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hierarchy;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->integer('per_page', 15);
        $allowedPerPage = [15, 25, 50, 100];

        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 15;
        }

        $query = User::with(['readingPlans', 'readingProgress', 'hierarchy.parent'])
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

        $users = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
        $readingPlans = ReadingPlan::all();
        $hierarchies = Hierarchy::with('parent')->ordered()->get();

        // Get summary statistics
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::whereNotNull('email_verified_at')->count(),
            'leaders' => User::whereIn('role', User::leaderRoles())->count(),
            'members' => User::where('role', User::ROLE_MEMBER)->count(),
            'roles' => User::roleOptions(),
        ];

        return view('admin.users.index', compact('users', 'readingPlans', 'hierarchies', 'stats', 'perPage', 'allowedPerPage'));
    }

    public function show(User $user)
    {
        $user->load([
            'hierarchy.parent',
            'readingPlans.dailyReadings',
            'readingProgress.dailyReading.readingPlan',
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

        return view('admin.users.create', compact('readingPlans', 'hierarchies', 'roleOptions'));
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
            'reading_plans' => ['array'],
            'reading_plans.*' => ['exists:reading_plans,id'],
        ]);

        $selectedHierarchy = $request->filled('hierarchy_id')
            ? Hierarchy::findOrFail($request->integer('hierarchy_id'))
            : null;

        $this->validateHierarchyRoleAlignment($request->input('role'), $selectedHierarchy);

        DB::transaction(function () use ($request, $selectedHierarchy) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone_number' => $request->filled('phone_number') ? $request->phone_number : null,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'hierarchy_id' => $selectedHierarchy?->id,
                'email_verified_at' => now(), // Auto-verify admin created users
            ]);

            $this->syncLeadershipAssignment($user, $selectedHierarchy, $request->role);

            // Attach reading plans if selected
            if ($request->filled('reading_plans')) {
                foreach ($request->reading_plans as $planId) {
                    $user->readingPlans()->attach($planId, [
                        'joined_date' => now()->toDateString(),
                        'current_day' => 1,
                    ]);
                }
            }
        });

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $readingPlans = ReadingPlan::all();
        $userPlanIds = $user->readingPlans->pluck('id')->toArray();
        $hierarchies = Hierarchy::with(['parent', 'leader'])->ordered()->get();
        $roleOptions = User::roleOptions();

        return view('admin.users.edit', compact('user', 'readingPlans', 'userPlanIds', 'hierarchies', 'roleOptions'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone_number' => ['nullable', 'string', 'max:255', 'unique:users,phone_number,'.$user->id],
            'role' => ['required', 'in:'.implode(',', User::assignableRoles())],
            'hierarchy_id' => ['nullable', 'exists:hierarchies,id'],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'reading_plans' => ['array'],
            'reading_plans.*' => ['exists:reading_plans,id'],
        ]);

        $selectedHierarchy = $request->filled('hierarchy_id')
            ? Hierarchy::findOrFail($request->integer('hierarchy_id'))
            : null;

        $this->validateHierarchyRoleAlignment($request->input('role'), $selectedHierarchy);
        $this->guardAgainstUnsafeLeadershipRemoval($user, $request->input('role'), $selectedHierarchy);

        DB::transaction(function () use ($request, $user, $selectedHierarchy) {
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone_number' => $request->filled('phone_number') ? $request->phone_number : null,
                'role' => $request->role,
                'hierarchy_id' => $selectedHierarchy?->id,
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);
            $user->refresh();
            $this->syncLeadershipAssignment($user, $selectedHierarchy, $request->role);

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

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => ['required', 'in:delete,assign_plan,remove_plan,change_role,assign_hierarchy,clear_hierarchy'],
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['exists:users,id'],
            'reading_plan_id' => ['required_if:action,assign_plan,remove_plan', 'exists:reading_plans,id'],
            'role' => ['required_if:action,change_role', 'in:'.implode(',', User::assignableRoles())],
            'hierarchy_id' => ['required_if:action,assign_hierarchy', 'nullable', 'exists:hierarchies,id'],
        ]);

        $users = User::whereIn('id', $request->user_ids)->get();
        $selectedHierarchy = $request->filled('hierarchy_id')
            ? Hierarchy::findOrFail($request->integer('hierarchy_id'))
            : null;

        switch ($request->action) {
            case 'delete':
                $users->each->delete();
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
                $message = 'Reading plan assigned to selected users.';
                break;

            case 'remove_plan':
                foreach ($users as $user) {
                    $user->readingPlans()->detach($request->reading_plan_id);
                }
                $message = 'Reading plan removed from selected users.';
                break;

            case 'change_role':
                $users->each(function ($user) use ($request) {
                    $user->update(['role' => $request->role]);
                });
                $message = 'Role updated for selected users.';
                break;

            case 'assign_hierarchy':
                DB::transaction(function () use ($users, $selectedHierarchy) {
                    foreach ($users as $user) {
                        $this->validateHierarchyRoleAlignment($user->role, $selectedHierarchy);
                        $this->guardAgainstUnsafeLeadershipRemoval($user, $user->role, $selectedHierarchy);

                        $user->update([
                            'hierarchy_id' => $selectedHierarchy?->id,
                        ]);

                        $user->refresh();
                        $this->syncLeadershipAssignment($user, $selectedHierarchy, $user->role);
                    }
                });
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
                $message = 'Group assignment cleared for selected users.';
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
}
