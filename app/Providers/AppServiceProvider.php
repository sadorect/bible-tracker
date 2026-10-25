<?php

namespace App\Providers;

use App\Models\Message;
use App\Models\SystemRole;
use App\Models\User;
use App\Policies\MessagePolicy;
use App\Support\SystemAccess;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Message::class, MessagePolicy::class);

        foreach (SystemAccess::permissionSlugs() as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermissionTo($permission));
        }

        Gate::define('send-downward-messages', fn (User $user) => $user->isAdmin() || $user->isLeader());
        Gate::define('send-upward-messages', fn (User $user) => ! $user->isAdmin());
        Gate::define('manage-message-templates', fn (User $user) => $user->hasPermissionTo('messages.manage_templates'));

        // "Login as" is deliberately narrower than users.manage: only a super
        // admin (or a legacy admin account) may take over another session.
        Gate::define('impersonate-users', fn (User $user) => $user->isAdmin() || $user->hasSystemRole(SystemRole::SUPER_ADMIN));

        // Log slow queries in development
        if (app()->environment('local')) {
            DB::listen(function (QueryExecuted $query) {
                if ($query->time > 100) { // Log queries taking more than 100ms
                    Log::warning('Slow query detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time.'ms',
                    ]);
                }
            });
        }

        // Set default string length for older MySQL versions
        Schema::defaultStringLength(191);
    }
}
