<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminAutomationController;
use App\Http\Controllers\Admin\AdminHierarchyController;
use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Admin\AdminMessagingSettingsController;
use App\Http\Controllers\Admin\AdminReadingPlanController;
use App\Http\Controllers\Admin\AdminSystemRoleController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\UserProgressController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MessageCenterController;
use App\Http\Controllers\NotificationCenterController;
use App\Http\Controllers\LeaderHierarchyController;
use App\Http\Controllers\LeaderMemberRecordController;
use App\Http\Controllers\ReadingPlanInviteController;
use App\Http\Controllers\ReadingPlanController;
use App\Http\Controllers\ReadingProgressController;
use App\Http\Controllers\UserManualController;
use App\Http\Middleware\Admin;
use App\Livewire\ReadingHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/enroll/{token}', [ReadingPlanInviteController::class, 'show'])->name('reading-plan-invites.show');
Route::get('/enroll/{token}/login', [ReadingPlanInviteController::class, 'beginLogin'])->name('reading-plan-invites.login');
Route::get('/enroll/{token}/register-fresh', [ReadingPlanInviteController::class, 'beginRegisterFresh'])->name('reading-plan-invites.register-fresh');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        // Check if user is admin and redirect to admin dashboard
        if (Auth::user()->canAccessAdminPanel()) {
            return redirect()->route('admin.dashboard');
        }

        return view('dashboard');
    })->name('dashboard');
    Route::get('/reading-progress', [ReadingProgressController::class, 'index'])->name('reading.progress');
    Route::get('/hierarchy/manage', [DashboardController::class, 'manageHierarchy'])->name('hierarchy.manage');
    Route::post('/hierarchy/manage/member/{member}', [DashboardController::class, 'updateManagedMember'])->name('hierarchy.members.update');
    Route::get('/hierarchy/manage/member/{member}', [LeaderMemberRecordController::class, 'show'])->name('hierarchy.members.show');
    Route::get('/hierarchy/manage/member/{member}/participations/{participation}', [LeaderMemberRecordController::class, 'participation'])->name('hierarchy.members.participations.show');
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
    Route::get('/messages', [MessageCenterController::class, 'inbox'])->name('messages.index');
    Route::get('/messages/inbox', [MessageCenterController::class, 'inbox'])->name('messages.inbox');
    Route::get('/messages/sent', [MessageCenterController::class, 'sent'])->name('messages.sent');
    Route::get('/messages/compose', [MessageCenterController::class, 'compose'])->name('messages.compose');
    Route::post('/messages/compose/preview', [MessageCenterController::class, 'preview'])->name('messages.preview');
    Route::post('/messages', [MessageCenterController::class, 'store'])->name('messages.store');
    Route::patch('/messages/{message}/archive', [MessageCenterController::class, 'archive'])->name('messages.archive');
    Route::patch('/messages/{message}/trash', [MessageCenterController::class, 'trash'])->name('messages.trash');
    Route::patch('/messages/{message}/restore', [MessageCenterController::class, 'restore'])->name('messages.restore');
    Route::get('/messages/{message}', [MessageCenterController::class, 'show'])->name('messages.show');
    Route::get('/notifications', [NotificationCenterController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [NotificationCenterController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationCenterController::class, 'markAsRead'])->name('notifications.read');
    Route::get('/manual', [UserManualController::class, 'index'])->name('manual.index');
    Route::get('/manual/{guide}', [UserManualController::class, 'show'])->name('manual.show');
    Route::post('/enroll/{token}/accept', [ReadingPlanInviteController::class, 'accept'])->name('reading-plan-invites.accept');

    // Leader hierarchy tree — scoped to the leader's own branch
    Route::get('/my-hierarchy', [LeaderHierarchyController::class, 'tree'])->name('leader.hierarchies.tree');
    Route::get('/my-hierarchy/{path}', [LeaderHierarchyController::class, 'show'])->where('path', '.+')->name('leader.hierarchies.show');
});
// Admin routes - All routes will have /admin prefix
Route::middleware(['auth', Admin::class])->prefix('admin')->name('admin.')->group(function () {
    // Admin dashboard - URL: /admin/dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])->middleware('can:dashboard.view')->name('dashboard');

    // User management - URLs: /admin/users/*
    Route::resource('users', AdminUserController::class)->middleware('can:users.manage');
    Route::post('users/bulk-action', [AdminUserController::class, 'bulkAction'])->middleware('can:users.manage')->name('users.bulk-action');

    Route::get('hierarchies', [AdminHierarchyController::class, 'index'])->middleware('can:hierarchies.manage')->name('hierarchies.index');
    Route::get('hierarchies/tree', [AdminHierarchyController::class, 'tree'])->middleware('can:hierarchies.manage')->name('hierarchies.tree');
    Route::get('hierarchies/{hierarchy}/resolve-vacancy', [AdminHierarchyController::class, 'showVacancyResolution'])->middleware('can:hierarchies.manage')->name('hierarchies.resolve-vacancy');
    Route::post('hierarchies/{hierarchy}/resolve-vacancy', [AdminHierarchyController::class, 'resolveVacancy'])->middleware('can:hierarchies.manage')->name('hierarchies.resolve-vacancy.submit');
    Route::get('hierarchies/workflows/migration/preview', [AdminHierarchyController::class, 'previewMigration'])->middleware('can:hierarchies.manage')->name('hierarchies.migration.preview');
    Route::post('hierarchies/workflows/migration', [AdminHierarchyController::class, 'executeMigration'])->middleware('can:hierarchies.manage')->name('hierarchies.migration.execute');
    Route::get('hierarchies/workflows/merge/preview', [AdminHierarchyController::class, 'previewMerge'])->middleware('can:hierarchies.manage')->name('hierarchies.merge.preview');
    Route::post('hierarchies/workflows/merge', [AdminHierarchyController::class, 'executeMerge'])->middleware('can:hierarchies.manage')->name('hierarchies.merge.execute');
    Route::post('hierarchies', [AdminHierarchyController::class, 'store'])->middleware('can:hierarchies.manage')->name('hierarchies.store');
    Route::put('hierarchies/{hierarchy}', [AdminHierarchyController::class, 'update'])->middleware('can:hierarchies.manage')->name('hierarchies.update');
    Route::post('hierarchies/{hierarchy}/promote-leader', [AdminHierarchyController::class, 'promoteLeader'])->middleware('can:hierarchies.manage')->name('hierarchies.promote-leader');
    Route::post('hierarchies/{hierarchy}/demote-leader', [AdminHierarchyController::class, 'demoteLeader'])->middleware('can:hierarchies.manage')->name('hierarchies.demote-leader');
    Route::get('hierarchies/{hierarchy}', [AdminHierarchyController::class, 'show'])->middleware('can:hierarchies.manage')->name('hierarchies.show');

    // User Progress routes - URLs: /admin/progress/*
    Route::get('progress', [UserProgressController::class, 'index'])->middleware('can:progress.view')->name('progress.index');
    Route::get('progress/user/{user}', [UserProgressController::class, 'userDetail'])->middleware('can:progress.view')->name('progress.user');
    Route::get('progress/plan/{readingPlan}', [UserProgressController::class, 'planDetail'])->middleware('can:progress.view')->name('progress.plan');
    Route::get('progress/export', [UserProgressController::class, 'export'])->middleware('can:progress.export')->name('progress.export');
    Route::post('progress/presets', [UserProgressController::class, 'storePreset'])->middleware('can:progress.view')->name('progress.presets.store');
    Route::delete('progress/presets/{reportPreset}', [UserProgressController::class, 'destroyPreset'])->middleware('can:progress.view')->name('progress.presets.destroy');
    Route::get('audits', [AdminAuditLogController::class, 'index'])->middleware('can:audits.view')->name('audits.index');
    Route::get('audits/export', [AdminAuditLogController::class, 'export'])->middleware('can:audits.view')->name('audits.export');
    Route::get('automation', [AdminAutomationController::class, 'index'])->middleware('can:automation.manage')->name('automation.index');
    Route::put('automation', [AdminAutomationController::class, 'update'])->middleware('can:automation.manage')->name('automation.update');
    Route::post('automation/run-now', [AdminAutomationController::class, 'runNow'])->middleware('can:automation.manage')->name('automation.run-now');

    // Reading plan management - URLs: /admin/reading-plans/*
    Route::resource('reading-plans', AdminReadingPlanController::class)->middleware('can:plans.manage');
    Route::post('reading-plans/{readingPlan}/training-resources', [AdminReadingPlanController::class, 'storeTrainingResource'])
        ->middleware('can:plans.manage')
        ->name('reading-plans.training-resources.store');
    Route::delete('reading-plans/{readingPlan}/training-resources/{trainingResource}', [AdminReadingPlanController::class, 'destroyTrainingResource'])
        ->middleware('can:plans.manage')
        ->name('reading-plans.training-resources.destroy');
    Route::post('reading-plans/{readingPlan}/invites', [AdminReadingPlanController::class, 'storeInvite'])
        ->middleware('can:plans.manage')
        ->name('reading-plans.invites.store');
    Route::delete('reading-plans/{readingPlan}/invites/{readingPlanInvite}', [AdminReadingPlanController::class, 'revokeInvite'])
        ->middleware('can:plans.manage')
        ->name('reading-plans.invites.revoke');
    Route::put('reading-plans/settings/lifecycle', [AdminReadingPlanController::class, 'updateLifecycleSettings'])
        ->middleware('can:plans.manage')
        ->name('reading-plans.settings.update');

    Route::get('messages/settings', [AdminMessagingSettingsController::class, 'index'])->middleware('can:messages.manage_templates')->name('messages.settings');
    Route::put('messages/settings', [AdminMessagingSettingsController::class, 'update'])->middleware('can:messages.manage_templates')->name('messages.settings.update');
    Route::post('messages/templates', [AdminMessagingSettingsController::class, 'storeTemplate'])->middleware('can:messages.manage_templates')->name('messages.templates.store');
    Route::put('messages/templates/{messageTemplate}', [AdminMessagingSettingsController::class, 'updateTemplate'])->middleware('can:messages.manage_templates')->name('messages.templates.update');
    Route::delete('messages/templates/{messageTemplate}', [AdminMessagingSettingsController::class, 'destroyTemplate'])->middleware('can:messages.manage_templates')->name('messages.templates.destroy');

    Route::get('system-roles', [AdminSystemRoleController::class, 'index'])->middleware('can:system_roles.manage')->name('system-roles.index');
    Route::post('system-roles', [AdminSystemRoleController::class, 'store'])->middleware('can:system_roles.manage')->name('system-roles.store');
    Route::put('system-roles/{systemRole}', [AdminSystemRoleController::class, 'update'])->middleware('can:system_roles.manage')->name('system-roles.update');
    Route::delete('system-roles/{systemRole}', [AdminSystemRoleController::class, 'destroy'])->middleware('can:system_roles.manage')->name('system-roles.destroy');

    Route::redirect('settings', 'messages/settings')->name('settings');
});

require __DIR__.'/auth.php';
