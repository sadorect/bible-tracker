<?php

use App\Http\Middleware\Admin;
use App\Http\Middleware\RequirePasswordReset;
use App\Http\Middleware\TrackSiteVisit;
use App\Support\CloudflareProxies;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('reading:check-progress')->dailyAt('00:01');
        $schedule->command('automation:run-daily')->dailyAt('06:30');
        $schedule->command('site-visits:prune')->dailyAt('02:15');
    })
    ->withMiddleware(function (Middleware $middleware) {
        // The origin sits behind Cloudflare, so the real client IP arrives in
        // X-Forwarded-For. Only Cloudflare's own ranges are trusted: the origin is
        // still directly reachable, so trusting '*' would let anyone spoof the header.
        $middleware->trustProxies(
            at: CloudflareProxies::ranges(),
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->alias([
            'admin' => Admin::class,
        ]);
        $middleware->append(TrackSiteVisit::class);
        $middleware->appendToGroup('web', RequirePasswordReset::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
