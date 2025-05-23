<?php

use Admin\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\ReadingProgressController;
use App\Http\Controllers\ReadingPlanController;
use App\Http\Controllers\Admin\AdminReadingPlanController;
use App\Http\Middleware\Admin;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function() {
        return view('dashboard');
    })->name('dashboard');
    Route::get('/progress', [DashboardController::class, 'progress'])->name('progress');
    Route::get('/reading-progress', [ReadingProgressController::class, 'index'])->name('reading.progress');
    Route::get('/statistics', [DashboardController::class, 'statistics'])->name('statistics');
    Route::get('/hierarchy/manage', [DashboardController::class, 'manageHierarchy'])->name('hierarchy.manage');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/progress/view', [ReadingProgressController::class, 'view'])->name('progress.view');
    // Reading Plans routes
    Route::get('/reading-plans', [ReadingPlanController::class, 'index'])->name('reading-plans.index');
    Route::get('/reading-plans/{readingPlan}', [ReadingPlanController::class, 'show'])->name('reading-plans.show');
    Route::post('/reading-plans/{readingPlan}/join', [ReadingPlanController::class, 'join'])->name('reading-plans.join');
    Route::post('/reading-plans/{readingPlan}/leave', [ReadingPlanController::class, 'leave'])->name('reading-plans.leave');
    
});
// Admin routes
Route::middleware(['auth', \App\Http\Middleware\Admin::class])->prefix('admin')->name('admin.')->group(function () {
    // Admin dashboard
Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    // User management
    Route::resource('users', AdminUserController::class);
    
    // Reading plan management
    Route::resource('reading-plans', AdminReadingPlanController::class);
});

require __DIR__.'/auth.php';
