<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ReadingPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['readingPlans', 'readingProgress'])
            ->withCount(['readingPlans', 'readingProgress']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
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
            $query->whereHas('readingPlans', function($q) use ($request) {
                $q->where('reading_plans.id', $request->reading_plan);
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);
        $readingPlans = ReadingPlan::all();

        // Get summary statistics
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::whereNotNull('email_verified_at')->count(),
            'leaders' => User::where('role', 'leader')->count(),
            'members' => User::where('role', 'member')->count(),
            'roles' => ['member', 'leader', 'admin'],
        ];

        return view('admin.users.index', compact('users', 'readingPlans', 'stats'));
    }

    public function show(User $user)
    {
        $user->load([
            'readingPlans.dailyReadings',
            'readingProgress.dailyReading.readingPlan'
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
        return view('admin.users.create', compact('readingPlans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:member,leader,admin'],
            'reading_plans' => ['array'],
            'reading_plans.*' => ['exists:reading_plans,id'],
        ]);

        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'email_verified_at' => now(), // Auto-verify admin created users
            ]);

            // Attach reading plans if selected
            if ($request->filled('reading_plans')) {
                foreach ($request->reading_plans as $planId) {
                    $user->readingPlans()->attach($planId, [
                        'joined_at' => now(),
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
        
        return view('admin.users.edit', compact('user', 'readingPlans', 'userPlanIds'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role' => ['required', 'in:member,leader,admin'],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'reading_plans' => ['array'],
            'reading_plans.*' => ['exists:reading_plans,id'],
        ]);

        DB::transaction(function () use ($request, $user) {
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'role' => $request->role,
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            // Sync reading plans
            if ($request->has('reading_plans')) {
                $syncData = [];
                foreach ($request->reading_plans as $planId) {
                    // Preserve existing pivot data or set defaults for new plans
                    $existingPivot = $user->readingPlans()->where('reading_plans.id', $planId)->first();
                    $syncData[$planId] = $existingPivot ? 
                        $existingPivot->pivot->toArray() : 
                        ['joined_at' => now(), 'current_day' => 1];
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
            'action' => ['required', 'in:delete,assign_plan,remove_plan,change_role'],
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['exists:users,id'],
            'reading_plan_id' => ['required_if:action,assign_plan,remove_plan', 'exists:reading_plans,id'],
            'role' => ['required_if:action,change_role', 'in:member,leader,admin'],
        ]);

        $users = User::whereIn('id', $request->user_ids)->get();

        switch ($request->action) {
            case 'delete':
                $users->each->delete();
                $message = 'Selected users deleted successfully.';
                break;

            case 'assign_plan':
                foreach ($users as $user) {
                    $user->readingPlans()->syncWithoutDetaching([
                        $request->reading_plan_id => [
                            'joined_at' => now(),
                            'joined_date' => now(),
                            'current_day' => 1,
                        ]
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
                $users->each(function($user) use ($request) {
                    $user->update(['role' => $request->role]);
                });
                $message = 'Role updated for selected users.';
                break;
        }

        return redirect()->route('admin.users.index')
            ->with('success', $message);
    }

    private function calculateCompletionRate(User $user)
    {
        $totalReadings = $user->readingPlans->sum(function($plan) {
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
            ->map(function($date) {
                return \Carbon\Carbon::parse($date);
            });

        if ($progress->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $currentDate = \Carbon\Carbon::today();

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
            ->map(function($date) {
                return \Carbon\Carbon::parse($date);
            });

        if ($progress->isEmpty()) {
            return 0;
        }

        $longestStreak = 1;
        $currentStreak = 1;

        for ($i = 1; $i < $progress->count(); $i++) {
            if ($progress[$i]->diffInDays($progress[$i-1]) === 1) {
                $currentStreak++;
                $longestStreak = max($longestStreak, $currentStreak);
            } else {
                $currentStreak = 1;
            }
        }

        return $longestStreak;
    }
}
