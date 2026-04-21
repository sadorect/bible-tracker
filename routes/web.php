<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminHierarchyController;
use App\Http\Controllers\Admin\AdminReadingPlanController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\UserProgressController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LeaderHierarchyController;
use App\Http\Controllers\ReadingPlanController;
use App\Http\Controllers\ReadingProgressController;
use App\Http\Middleware\Admin;
use App\Livewire\ReadingHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        // Check if user is admin and redirect to admin dashboard
        if (Auth::user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('dashboard');
    })->name('dashboard');
    Route::get('/reading-progress', [ReadingProgressController::class, 'index'])->name('reading.progress');
    Route::get('/hierarchy/manage', [DashboardController::class, 'manageHierarchy'])->name('hierarchy.manage');
    Route::post('/hierarchy/manage/member/{member}', [DashboardController::class, 'updateManagedMember'])->name('hierarchy.members.update');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/progress/view', [ReadingProgressController::class, 'view'])->name('progress.view');
    Route::post('/quick-mark-complete', [ReadingProgressController::class, 'quickMark'])->name('reading.quick-mark');
    // Reading Plans routes
    Route::get('/reading-plans', [ReadingPlanController::class, 'index'])->name('reading-plans.index');
    Route::get('/reading-plans/{readingPlan}', [ReadingPlanController::class, 'show'])->name('reading-plans.show');
    Route::post('/reading-plans/{readingPlan}/join', [ReadingPlanController::class, 'join'])->name('reading-plans.join');
    Route::post('/reading-plans/{readingPlan}/leave', [ReadingPlanController::class, 'leave'])->name('reading-plans.leave');
    Route::post('/reading-plans/{readingPlan}/reset', [ReadingPlanController::class, 'resetProgress'])->name('reading-plans.reset');
    Route::post('/reading-plans/{readingPlan}/skip', [ReadingPlanController::class, 'skipToDay'])->name('reading-plans.skip');
    Route::get('/reading-plans/{readingPlan}/progress', [ReadingPlanController::class, 'viewProgress'])->name('reading-plans.progress');
    Route::get('/reading-history', ReadingHistory::class)->name('reading-history');

    // Leader hierarchy tree — scoped to the leader's own branch
    Route::get('/my-hierarchy', [LeaderHierarchyController::class, 'tree'])->name('leader.hierarchies.tree');
    Route::get('/my-hierarchy/{path}', [LeaderHierarchyController::class, 'show'])->where('path', '.+')->name('leader.hierarchies.show');
});
// Admin routes - All routes will have /admin prefix
Route::middleware(['auth', Admin::class])->prefix('admin')->name('admin.')->group(function () {
    // Admin dashboard - URL: /admin/dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // User management - URLs: /admin/users/*
    Route::resource('users', AdminUserController::class);
    Route::post('users/bulk-action', [AdminUserController::class, 'bulkAction'])->name('users.bulk-action');

    Route::get('hierarchies', [AdminHierarchyController::class, 'index'])->name('hierarchies.index');
    Route::get('hierarchies/tree', [AdminHierarchyController::class, 'tree'])->name('hierarchies.tree');
    Route::post('hierarchies', [AdminHierarchyController::class, 'store'])->name('hierarchies.store');
    Route::put('hierarchies/{hierarchy}', [AdminHierarchyController::class, 'update'])->name('hierarchies.update');
    Route::get('hierarchies/{hierarchy}', [AdminHierarchyController::class, 'show'])->name('hierarchies.show');

    // User Progress routes - URLs: /admin/progress/*
    Route::get('progress', [UserProgressController::class, 'index'])->name('progress.index');
    Route::get('progress/user/{user}', [UserProgressController::class, 'userDetail'])->name('progress.user');
    Route::get('progress/plan/{readingPlan}', [UserProgressController::class, 'planDetail'])->name('progress.plan');
    Route::get('progress/export', [UserProgressController::class, 'export'])->name('progress.export');

    // Reading plan management - URLs: /admin/reading-plans/*
    Route::resource('reading-plans', AdminReadingPlanController::class);
    Route::post('reading-plans/{readingPlan}/training-resources', [AdminReadingPlanController::class, 'storeTrainingResource'])
        ->name('reading-plans.training-resources.store');
    Route::delete('reading-plans/{readingPlan}/training-resources/{trainingResource}', [AdminReadingPlanController::class, 'destroyTrainingResource'])
        ->name('reading-plans.training-resources.destroy');

    // Additional admin routes can be added here
    Route::get('settings', function () {
        return view('admin.settings');
    })->name('settings');
});

require __DIR__.'/auth.php';
